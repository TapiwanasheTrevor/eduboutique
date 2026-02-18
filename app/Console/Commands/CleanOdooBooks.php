<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class CleanOdooBooks extends Command
{
    protected $signature = 'odoo:clean-books
                            {--export : Export all books to CSV before cleaning}
                            {--analyze : Only analyze duplicates without making changes}
                            {--clean : Actually remove duplicates from Odoo}
                            {--keep=newest : Which duplicate to keep: newest, oldest, or highest_stock}';

    protected $description = 'Download books from Odoo, identify duplicates, and optionally clean them up';

    protected $url;
    protected $db;
    protected $username;
    protected $password;
    protected $uid;
    protected $sessionId;
    protected $allProducts = [];
    protected $duplicates = [];

    public function handle()
    {
        $this->info('=== Odoo Books Catalog Cleaner ===');
        $this->newLine();

        $this->url = config('odoo.url');
        $this->db = config('odoo.database');
        $this->username = config('odoo.username');
        $this->password = config('odoo.password');

        try {
            // Connect to Odoo using JSON-RPC (no xmlrpc extension needed)
            $this->connectToOdoo();
            $this->info('Connected to Odoo successfully');
        } catch (\Exception $e) {
            $this->error('Failed to connect to Odoo: ' . $e->getMessage());
            return 1;
        }

        // Fetch all products
        $this->info('Fetching all products from Odoo...');
        $this->fetchAllProducts();

        $this->info("Total products fetched: " . count($this->allProducts));
        $this->newLine();

        // Analyze duplicates
        $this->info('Analyzing duplicates...');
        $this->analyzeDuplicates();

        // Export to CSV
        if ($this->option('export') || $this->option('analyze')) {
            $this->exportToCSV();
        }

        // Show duplicate analysis
        $this->showDuplicateAnalysis();

        // Clean duplicates if requested
        if ($this->option('clean') && !empty($this->duplicates)) {
            $this->cleanDuplicates();
        }

        return 0;
    }

    /**
     * Make a JSON-RPC call to Odoo with session cookie
     */
    protected function jsonRpc($endpoint, $method, $params, $withSession = true)
    {
        $http = Http::connectTimeout(60)
            ->timeout(180)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ]);

        // Include session cookie if we have one
        if ($withSession && $this->sessionId) {
            $http = $http->withCookies([
                'session_id' => $this->sessionId,
            ], parse_url($this->url, PHP_URL_HOST));
        }

        $response = $http->post("{$this->url}{$endpoint}", [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => rand(1, 999999999),
        ]);

        if (!$response->successful()) {
            throw new \Exception('HTTP error: ' . $response->status());
        }

        // Extract and save session cookie
        $cookies = $response->cookies();
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'session_id') {
                $this->sessionId = $cookie->getValue();
            }
        }

        $data = $response->json();

        if (isset($data['error'])) {
            $errorMsg = $data['error']['message'] ?? 'Unknown error';
            if (isset($data['error']['data']['message'])) {
                $errorMsg .= ': ' . $data['error']['data']['message'];
            }
            throw new \Exception('Odoo error: ' . $errorMsg);
        }

        return $data['result'] ?? null;
    }

    protected function connectToOdoo()
    {
        // Authenticate using JSON-RPC (this sets the session cookie)
        $result = $this->jsonRpc('/web/session/authenticate', 'call', [
            'db' => $this->db,
            'login' => $this->username,
            'password' => $this->password,
        ], false);

        if (!$result || !isset($result['uid']) || !$result['uid']) {
            throw new \Exception('Odoo authentication failed - invalid credentials');
        }

        $this->uid = $result['uid'];
        $this->info("Authenticated as UID: {$this->uid}");
    }

    /**
     * Call Odoo model method
     */
    protected function callOdoo($model, $method, $args = [], $kwargs = [])
    {
        return $this->jsonRpc('/web/dataset/call_kw', 'call', [
            'model' => $model,
            'method' => $method,
            'args' => $args,
            'kwargs' => $kwargs,
        ]);
    }

    protected function fetchAllProducts()
    {
        // Fetch products WITHOUT images first (images are too large and slow down the request)
        $fields = [
            'id',
            'name',
            'display_name',
            'default_code',      // SKU/ISBN
            'list_price',
            'standard_price',
            'qty_available',
            'virtual_available',
            'description',
            'description_sale',
            'categ_id',
            'create_date',
            'write_date',
            'active',
            'sale_ok',
            'barcode',
            'weight',
            'volume',
            'image_128',         // Small thumbnail only (for checking if image exists)
        ];

        // Fetch products in batches to handle large catalogs
        $offset = 0;
        $batchSize = 50; // Smaller batch size for reliability
        $allProducts = [];

        $this->output->write('Fetching');

        do {
            try {
                $products = $this->callOdoo('product.product', 'search_read', [
                    [['sale_ok', '=', true]],  // domain
                ], [
                    'fields' => $fields,
                    'limit' => $batchSize,
                    'offset' => $offset,
                    'order' => 'name asc',
                ]);

                if (!empty($products) && is_array($products)) {
                    $allProducts = array_merge($allProducts, $products);
                    $this->output->write('.');
                }
            } catch (\Exception $e) {
                $this->warn("\nBatch at offset {$offset} failed: " . $e->getMessage());
                // Try to continue with next batch
            }

            $offset += $batchSize;
        } while (!empty($products) && is_array($products) && count($products) === $batchSize);

        $this->newLine();
        $this->allProducts = $allProducts;
    }

    protected function analyzeDuplicates()
    {
        // Group by normalized title
        $byTitle = [];
        $byISBN = [];
        $byBarcode = [];

        foreach ($this->allProducts as $product) {
            $normalizedTitle = $this->normalizeTitle($product['name']);

            // Group by title
            if (!isset($byTitle[$normalizedTitle])) {
                $byTitle[$normalizedTitle] = [];
            }
            $byTitle[$normalizedTitle][] = $product;

            // Group by ISBN/default_code if present
            if (!empty($product['default_code'])) {
                $code = trim($product['default_code']);
                if (!isset($byISBN[$code])) {
                    $byISBN[$code] = [];
                }
                $byISBN[$code][] = $product;
            }

            // Group by barcode if present
            if (!empty($product['barcode'])) {
                $barcode = trim($product['barcode']);
                if (!isset($byBarcode[$barcode])) {
                    $byBarcode[$barcode] = [];
                }
                $byBarcode[$barcode][] = $product;
            }
        }

        // Find duplicates (more than 1 product with same identifier)
        $this->duplicates = [
            'by_title' => array_filter($byTitle, fn($group) => count($group) > 1),
            'by_isbn' => array_filter($byISBN, fn($group) => count($group) > 1),
            'by_barcode' => array_filter($byBarcode, fn($group) => count($group) > 1),
        ];
    }

    protected function normalizeTitle($title)
    {
        // Normalize for comparison
        $title = mb_strtolower($title);
        $title = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $title); // Keep letters and numbers
        $title = preg_replace('/\s+/', ' ', $title);
        $title = trim($title);
        return $title;
    }

    protected function showDuplicateAnalysis()
    {
        $this->newLine();
        $this->info('=== DUPLICATE ANALYSIS ===');
        $this->newLine();

        // Title duplicates
        $titleDupes = $this->duplicates['by_title'];
        $this->info("Duplicates by Title: " . count($titleDupes) . " groups");

        if (!empty($titleDupes)) {
            $this->table(
                ['Title (Normalized)', 'Count', 'IDs'],
                collect($titleDupes)->map(function ($group, $title) {
                    return [
                        Str::limit($title, 50),
                        count($group),
                        implode(', ', array_column($group, 'id')),
                    ];
                })->take(20)->toArray()
            );

            if (count($titleDupes) > 20) {
                $this->warn("... and " . (count($titleDupes) - 20) . " more duplicate groups");
            }
        }

        $this->newLine();

        // ISBN duplicates
        $isbnDupes = $this->duplicates['by_isbn'];
        $this->info("Duplicates by ISBN/SKU: " . count($isbnDupes) . " groups");

        if (!empty($isbnDupes)) {
            $this->table(
                ['ISBN/SKU', 'Count', 'Titles'],
                collect($isbnDupes)->map(function ($group, $isbn) {
                    return [
                        $isbn,
                        count($group),
                        Str::limit(implode(' | ', array_unique(array_column($group, 'name'))), 60),
                    ];
                })->take(20)->toArray()
            );
        }

        $this->newLine();

        // Barcode duplicates
        $barcodeDupes = $this->duplicates['by_barcode'];
        $this->info("Duplicates by Barcode: " . count($barcodeDupes) . " groups");

        // Summary
        $this->newLine();
        $this->info('=== SUMMARY ===');
        $totalDupeProducts = 0;
        foreach ($titleDupes as $group) {
            $totalDupeProducts += count($group) - 1; // Count extras only
        }
        $this->line("Total products: " . count($this->allProducts));
        $this->line("Unique titles: " . (count($this->allProducts) - $totalDupeProducts));
        $this->line("Duplicate entries to remove: " . $totalDupeProducts);
    }

    protected function exportToCSV()
    {
        $this->info('Exporting to CSV...');

        $timestamp = now()->format('Y-m-d_His');

        // Export all products
        $allCsvPath = "odoo_exports/all_books_{$timestamp}.csv";
        $this->exportProductsToCSV($this->allProducts, $allCsvPath, 'all');

        // Export duplicates only
        $duplicateProducts = [];
        foreach ($this->duplicates['by_title'] as $group) {
            foreach ($group as $product) {
                $product['_duplicate_group'] = $this->normalizeTitle($product['name']);
                $duplicateProducts[] = $product;
            }
        }

        if (!empty($duplicateProducts)) {
            $dupesCsvPath = "odoo_exports/duplicate_books_{$timestamp}.csv";
            $this->exportProductsToCSV($duplicateProducts, $dupesCsvPath, 'duplicates');
        }

        // Export clean catalog (one of each)
        $cleanProducts = [];
        $seenTitles = [];
        $keepStrategy = $this->option('keep');

        foreach ($this->allProducts as $product) {
            $normalizedTitle = $this->normalizeTitle($product['name']);

            if (!isset($seenTitles[$normalizedTitle])) {
                $seenTitles[$normalizedTitle] = $product;
            } else {
                // Decide which to keep based on strategy
                $existing = $seenTitles[$normalizedTitle];
                $keepNew = false;

                switch ($keepStrategy) {
                    case 'newest':
                        $keepNew = strtotime($product['write_date']) > strtotime($existing['write_date']);
                        break;
                    case 'oldest':
                        $keepNew = strtotime($product['create_date']) < strtotime($existing['create_date']);
                        break;
                    case 'highest_stock':
                        $keepNew = ($product['qty_available'] ?? 0) > ($existing['qty_available'] ?? 0);
                        break;
                }

                if ($keepNew) {
                    $seenTitles[$normalizedTitle] = $product;
                }
            }
        }

        $cleanProducts = array_values($seenTitles);
        $cleanCsvPath = "odoo_exports/clean_catalog_{$timestamp}.csv";
        $this->exportProductsToCSV($cleanProducts, $cleanCsvPath, 'clean');

        $this->newLine();
        $this->info('CSV files exported to storage/app/odoo_exports/');
    }

    protected function exportProductsToCSV($products, $path, $type)
    {
        $csvData = [];

        // Headers
        $headers = [
            'odoo_id',
            'name',
            'normalized_title',
            'default_code_isbn',
            'barcode',
            'list_price',
            'cost_price',
            'qty_available',
            'virtual_available',
            'category_id',
            'category_name',
            'description',
            'has_image',
            'image_url',
            'create_date',
            'write_date',
            'active',
            'sale_ok',
        ];

        if ($type === 'duplicates') {
            $headers[] = 'duplicate_group';
        }

        $csvData[] = $headers;

        foreach ($products as $product) {
            $categoryName = '';
            if (!empty($product['categ_id']) && is_array($product['categ_id'])) {
                $categoryName = $product['categ_id'][1] ?? '';
            }

            $hasImage = !empty($product['image_128']) && $product['image_128'] !== false;

            // Build Odoo image URL
            $imageUrl = $hasImage
                ? config('odoo.url') . '/web/image/product.product/' . $product['id'] . '/image_1920'
                : '';

            $row = [
                $product['id'],
                $product['name'],
                $this->normalizeTitle($product['name']),
                $product['default_code'] ?? '',
                $product['barcode'] ?? '',
                $product['list_price'] ?? 0,
                $product['standard_price'] ?? 0,
                $product['qty_available'] ?? 0,
                $product['virtual_available'] ?? 0,
                is_array($product['categ_id']) ? $product['categ_id'][0] : ($product['categ_id'] ?? ''),
                $categoryName,
                Str::limit($product['description_sale'] ?? $product['description'] ?? '', 500),
                $hasImage ? 'Yes' : 'No',
                $imageUrl,
                $product['create_date'] ?? '',
                $product['write_date'] ?? '',
                $product['active'] ? 'Yes' : 'No',
                $product['sale_ok'] ? 'Yes' : 'No',
            ];

            if ($type === 'duplicates') {
                $row[] = $product['_duplicate_group'] ?? '';
            }

            $csvData[] = $row;
        }

        // Write CSV
        $csv = '';
        foreach ($csvData as $row) {
            $csv .= $this->arrayToCsvLine($row) . "\n";
        }

        Storage::put($path, $csv);

        $this->info("  → {$type}: " . count($products) . " products → storage/app/{$path}");
    }

    protected function arrayToCsvLine($array)
    {
        $escaped = array_map(function ($field) {
            $field = str_replace('"', '""', $field ?? '');
            if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
                return '"' . $field . '"';
            }
            return $field;
        }, $array);

        return implode(',', $escaped);
    }

    protected function cleanDuplicates()
    {
        $this->newLine();
        $this->warn('=== CLEANING DUPLICATES ===');

        if (!$this->confirm('This will DELETE duplicate products from Odoo. Are you sure?')) {
            $this->info('Cleanup cancelled.');
            return;
        }

        $keepStrategy = $this->option('keep');
        $this->info("Strategy: Keep the {$keepStrategy} product in each duplicate group");
        $this->newLine();

        $deleted = 0;
        $errors = 0;

        foreach ($this->duplicates['by_title'] as $title => $group) {
            // Sort group based on strategy
            usort($group, function ($a, $b) use ($keepStrategy) {
                switch ($keepStrategy) {
                    case 'newest':
                        return strtotime($b['write_date']) - strtotime($a['write_date']);
                    case 'oldest':
                        return strtotime($a['create_date']) - strtotime($b['create_date']);
                    case 'highest_stock':
                        return ($b['qty_available'] ?? 0) - ($a['qty_available'] ?? 0);
                    default:
                        return 0;
                }
            });

            // Keep the first one, delete the rest
            $keep = array_shift($group);
            $this->line("Keeping: [{$keep['id']}] {$keep['name']}");

            foreach ($group as $toDelete) {
                try {
                    $result = $this->callOdoo('product.product', 'unlink', [[$toDelete['id']]]);
                    $this->info("  Deleted: [{$toDelete['id']}] {$toDelete['name']}");
                    $deleted++;
                } catch (\Exception $e) {
                    $this->error("  Failed to delete [{$toDelete['id']}]: " . $e->getMessage());
                    $errors++;
                }
            }
        }

        $this->newLine();
        $this->info("Cleanup complete: {$deleted} products deleted, {$errors} errors");
    }
}
