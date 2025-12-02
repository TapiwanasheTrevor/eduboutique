<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportMissingProducts extends Command
{
    protected $signature = 'products:import-missing
                            {--source=https://eduboutique.fly.dev : Source URL}
                            {--json= : Path to JSON file with products to import}
                            {--download-covers : Download cover images}
                            {--dry-run : Show what would be imported without actually importing}';

    protected $description = 'Import missing products from Fly.io or a JSON file';

    public function handle(): int
    {
        $jsonPath = $this->option('json') ?: '/tmp/missing_products.json';
        $sourceUrl = $this->option('source');
        $downloadCovers = $this->option('download-covers');
        $dryRun = $this->option('dry-run');

        if (!file_exists($jsonPath)) {
            $this->error("JSON file not found: {$jsonPath}");
            return 1;
        }

        $products = json_decode(file_get_contents($jsonPath), true);

        if (!$products) {
            $this->error('Failed to parse JSON file');
            return 1;
        }

        $this->info("Found " . count($products) . " products to import");

        if ($dryRun) {
            $this->warn('DRY RUN - No changes will be made');
        }

        // Get or create default category
        $defaultCategory = Category::firstOrCreate(
            ['slug' => 'books'],
            ['name' => 'Books']
        );

        $bar = $this->output->createProgressBar(count($products));
        $bar->start();

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($products as $productData) {
            try {
                // Check if product already exists
                $exists = Product::where('title', $productData['title'])->exists();

                if ($exists) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                if ($dryRun) {
                    $this->newLine();
                    $this->line("Would import: {$productData['title']}");
                    $imported++;
                    $bar->advance();
                    continue;
                }

                // Download cover image if requested
                $coverPath = null;
                if ($downloadCovers && !empty($productData['cover_image'])) {
                    $coverPath = $this->downloadCover($sourceUrl, $productData['cover_image']);
                }

                // Create the product
                $product = Product::create([
                    'title' => $productData['title'],
                    'slug' => Str::slug($productData['title']) . '-' . Str::random(4),
                    'author' => $productData['author'] ?? null,
                    'isbn' => $productData['isbn'] ?? null,
                    'description' => $productData['description'] ?? null,
                    'price' => $productData['price_usd'] ?? 0,
                    'stock_quantity' => $productData['stock_quantity'] ?? 0,
                    'cover_image' => $coverPath ?? $productData['cover_image'],
                    'category_id' => $this->findCategoryId($productData['category_id'] ?? null) ?? $defaultCategory->id,
                    'is_active' => $productData['featured'] ?? true,
                    'is_featured' => $productData['featured'] ?? false,
                    'syllabus' => $productData['syllabus'] ?? null,
                    'level' => $productData['level'] ?? null,
                    'subject' => $productData['subject'] ?? null,
                    'publisher' => $productData['publisher'] ?? null,
                ]);

                $imported++;
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Error importing {$productData['title']}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Import completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Imported', $imported],
                ['Skipped (already exists)', $skipped],
                ['Errors', $errors],
            ]
        );

        return 0;
    }

    private function downloadCover(string $sourceUrl, string $coverPath): ?string
    {
        try {
            $url = rtrim($sourceUrl, '/') . $coverPath;
            $response = Http::timeout(30)->get($url);

            if (!$response->successful()) {
                return null;
            }

            // Determine new path
            $filename = basename($coverPath);
            $newPath = 'products/' . Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . '.' . pathinfo($filename, PATHINFO_EXTENSION);

            Storage::disk('public')->put($newPath, $response->body());

            return $newPath;
        } catch (\Exception $e) {
            $this->warn("Failed to download cover: {$coverPath}");
            return null;
        }
    }

    private function findCategoryId(?string $categoryId): ?string
    {
        if (!$categoryId) {
            return null;
        }

        $category = Category::find($categoryId);
        return $category?->id;
    }
}
