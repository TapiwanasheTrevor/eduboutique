<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportProducts extends Command
{
    protected $signature = 'products:import {file : Path to JSON file} {--force : Force overwrite all products}';
    protected $description = 'Import products from JSON file (upsert based on slug)';

    public function handle()
    {
        $file = $this->argument('file');
        $force = $this->option('force');

        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $data = json_decode(file_get_contents($file), true);

        if (!$data) {
            $this->error('Invalid JSON file');
            return 1;
        }

        $this->info("Importing " . count($data) . " products...");

        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        $imported = 0;
        $updated = 0;
        $errors = 0;

        foreach ($data as $item) {
            try {
                // Try to find by slug first (more reliable than ID since IDs may differ)
                $existing = Product::where('slug', $item['slug'])->first();

                $productData = [
                    'title' => $item['title'],
                    'slug' => $item['slug'],
                    'description' => $item['description'],
                    'price_zwl' => $item['price_zwl'] ?? 0,
                    'price_usd' => $item['price_usd'] ?? 0,
                    'category_id' => null, // Skip category_id as it may not match remote DB
                    'syllabus' => $item['syllabus'] ?? 'Other',
                    'level' => $item['level'] ?? 'Primary',
                    'subject' => $item['subject'],
                    'publisher' => $item['publisher'],
                    'isbn' => $item['isbn'],
                    'item_code' => $item['item_code'],
                    'author' => $item['author'],
                    'cover_image' => $item['cover_image'],
                    'stock_status' => $item['stock_status'] ?? 'in_stock',
                    'stock_quantity' => $item['stock_quantity'] ?? 0,
                    'featured' => $item['featured'] ?? false,
                ];

                if ($existing) {
                    // Only update if force flag is set or if local data has more info
                    if ($force || ($existing->price_usd == 0 && $item['price_usd'] > 0)) {
                        $existing->update($productData);
                        $updated++;
                    }
                } else {
                    // Create new product with the original ID if possible
                    $productData['id'] = $item['id'];
                    Product::create($productData);
                    $imported++;
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->warn("Error importing {$item['title']}: " . $e->getMessage());
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Import complete!");
        $this->info("  - New products: {$imported}");
        $this->info("  - Updated: {$updated}");
        $this->info("  - Errors: {$errors}");

        return 0;
    }
}
