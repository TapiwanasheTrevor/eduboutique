<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportProducts extends Command
{
    protected $signature = 'products:import {file : Path to JSON file}';
    protected $description = 'Import products from JSON file (upsert based on ID)';

    public function handle()
    {
        $file = $this->argument('file');

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

        DB::beginTransaction();

        try {
            foreach ($data as $item) {
                try {
                    $existing = Product::find($item['id']);

                    $productData = [
                        'id' => $item['id'],
                        'title' => $item['title'],
                        'slug' => $item['slug'],
                        'description' => $item['description'],
                        'price_zwl' => $item['price_zwl'] ?? 0,
                        'price_usd' => $item['price_usd'] ?? 0,
                        'category_id' => $item['category_id'],
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
                        $existing->update($productData);
                        $updated++;
                    } else {
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

            DB::commit();

            $bar->finish();
            $this->newLine(2);

            $this->info("Import complete!");
            $this->info("  - New products: {$imported}");
            $this->info("  - Updated: {$updated}");
            $this->info("  - Errors: {$errors}");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }
    }
}
