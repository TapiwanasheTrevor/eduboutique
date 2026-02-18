<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MigrateOdooCatalog extends Command
{
    protected $signature = 'odoo:migrate
                            {--wipe : Wipe all existing products from Odoo}
                            {--upload : Upload new products from Sage inventory}
                            {--dry-run : Show what would be done without making changes}
                            {--force : Skip confirmation prompts}
                            {--source= : Path to source JSON file (default: storage/app/sage_inventory.json)}';

    protected $description = 'Migrate product catalog from Sage Evolution to Odoo';

    protected $url;
    protected $db;
    protected $username;
    protected $password;
    protected $uid;
    protected $sessionId;

    public function handle()
    {
        $this->info('=== Odoo Catalog Migration Tool ===');
        $this->newLine();

        $this->url = config('odoo.url');
        $this->db = config('odoo.database');
        $this->username = config('odoo.username');
        $this->password = config('odoo.password');

        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        try {
            $this->connectToOdoo();
            $this->info('Connected to Odoo successfully');
        } catch (\Exception $e) {
            $this->error('Failed to connect to Odoo: ' . $e->getMessage());
            return 1;
        }

        // Wipe existing products if requested
        if ($this->option('wipe')) {
            $this->wipeProducts($isDryRun);
        }

        // Upload new products if requested
        if ($this->option('upload')) {
            $this->uploadProducts($isDryRun);
        }

        if (!$this->option('wipe') && !$this->option('upload')) {
            $this->warn('No action specified. Use --wipe, --upload, or both.');
            $this->line('  --wipe    : Delete all existing products from Odoo');
            $this->line('  --upload  : Upload products from Sage inventory');
            $this->line('  --dry-run : Preview without making changes');
        }

        return 0;
    }

    protected function jsonRpc($endpoint, $method, $params, $withSession = true)
    {
        $http = Http::connectTimeout(60)
            ->timeout(180)
            ->withHeaders([
                'Content-Type' => 'application/json',
            ]);

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

    protected function callOdoo($model, $method, $args = [], $kwargs = [])
    {
        return $this->jsonRpc('/web/dataset/call_kw', 'call', [
            'model' => $model,
            'method' => $method,
            'args' => $args,
            'kwargs' => $kwargs,
        ]);
    }

    protected function wipeProducts($isDryRun)
    {
        $this->newLine();
        $this->warn('=== WIPING EXISTING PRODUCTS ===');

        // First, get all product IDs
        $this->info('Fetching existing products...');

        $productIds = $this->callOdoo('product.product', 'search', [
            [['sale_ok', '=', true]],  // domain - only saleable products
        ]);

        $count = count($productIds);
        $this->info("Found {$count} products to delete");

        if ($count === 0) {
            $this->info('No products to delete.');
            return;
        }

        if ($isDryRun) {
            $this->warn("DRY RUN: Would delete {$count} products");
            return;
        }

        if (!$this->option('force') && !$this->confirm("This will DELETE {$count} products from Odoo. Are you absolutely sure?")) {
            $this->info('Wipe cancelled.');
            return;
        }

        // Delete in batches to avoid timeout
        $batchSize = 50;
        $deleted = 0;
        $errors = 0;

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        foreach (array_chunk($productIds, $batchSize) as $batch) {
            try {
                $this->callOdoo('product.product', 'unlink', [$batch]);
                $deleted += count($batch);
            } catch (\Exception $e) {
                $errors += count($batch);
                $this->newLine();
                $this->error("Failed to delete batch: " . $e->getMessage());
            }
            $progressBar->advance(count($batch));
        }

        $progressBar->finish();
        $this->newLine();
        $this->newLine();
        $this->info("Wipe complete: {$deleted} deleted, {$errors} errors");
    }

    protected function uploadProducts($isDryRun)
    {
        $this->newLine();
        $this->info('=== UPLOADING NEW PRODUCTS ===');

        // Load source data
        $sourcePath = $this->option('source') ?: storage_path('app/sage_inventory.json');

        if (!file_exists($sourcePath)) {
            $this->error("Source file not found: {$sourcePath}");
            $this->line('Run the Excel extraction first or specify --source=path/to/file.json');
            return;
        }

        $products = json_decode(file_get_contents($sourcePath), true);
        $count = count($products);

        $this->info("Loaded {$count} products from: {$sourcePath}");

        if ($isDryRun) {
            $this->warn("DRY RUN: Would upload {$count} products");
            $this->newLine();
            $this->info('Sample products:');
            foreach (array_slice($products, 0, 10) as $p) {
                $this->line("  - {$p['name']} | Qty: {$p['quantity']} | \${$p['price_usd']}");
            }
            return;
        }

        if (!$this->option('force') && !$this->confirm("This will CREATE {$count} products in Odoo. Continue?")) {
            $this->info('Upload cancelled.');
            return;
        }

        // Get or create a default category
        $categoryId = $this->getOrCreateCategory('Books');

        $created = 0;
        $errors = 0;
        $errorList = [];

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        foreach ($products as $product) {
            try {
                $productData = $this->prepareProductData($product, $categoryId);
                $newId = $this->callOdoo('product.product', 'create', [$productData]);
                $created++;
            } catch (\Exception $e) {
                $errors++;
                $errorList[] = [
                    'name' => $product['name'],
                    'error' => $e->getMessage(),
                ];
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->newLine();

        $this->info("Upload complete: {$created} created, {$errors} errors");

        if (!empty($errorList)) {
            $this->newLine();
            $this->warn('Errors:');
            foreach (array_slice($errorList, 0, 20) as $err) {
                $this->error("  - {$err['name']}: {$err['error']}");
            }
            if (count($errorList) > 20) {
                $this->warn('  ... and ' . (count($errorList) - 20) . ' more errors');
            }
        }
    }

    protected function getOrCreateCategory($name)
    {
        // Search for existing category
        $categories = $this->callOdoo('product.category', 'search_read', [
            [['name', '=', $name]],
        ], [
            'fields' => ['id', 'name'],
            'limit' => 1,
        ]);

        if (!empty($categories)) {
            $this->info("Using existing category: {$name} (ID: {$categories[0]['id']})");
            return $categories[0]['id'];
        }

        // Create new category
        $categoryId = $this->callOdoo('product.category', 'create', [[
            'name' => $name,
        ]]);

        $this->info("Created new category: {$name} (ID: {$categoryId})");
        return $categoryId;
    }

    protected function prepareProductData($product, $categoryId)
    {
        $name = trim($product['name']);

        // Generate a slug-like default_code
        $defaultCode = Str::slug($name);
        if (strlen($defaultCode) > 64) {
            $defaultCode = substr($defaultCode, 0, 64);
        }

        // Determine stock status
        $qty = $product['quantity'] ?? 0;
        $stockStatus = 'out_of_stock';
        if ($qty > 10) {
            $stockStatus = 'in_stock';
        } elseif ($qty > 0) {
            $stockStatus = 'low_stock';
        }

        return [
            'name' => $name,
            'default_code' => $defaultCode,
            'list_price' => $product['price_usd'] ?? 0,
            'categ_id' => $categoryId,
            'sale_ok' => true,
            'purchase_ok' => true,
            'type' => 'consu',  // Consumable/Storable
            'detailed_type' => 'consu',
            // Note: qty_available is computed field, can't set directly
            // Stock would need to be updated via stock.quant or inventory adjustments
        ];
    }
}
