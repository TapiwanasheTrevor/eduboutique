<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportCatalogBooks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'books:import-catalog {--dry-run : Show what would be imported without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import books from the books.json file in public/library/updates';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $catalogPath = public_path('library/updates/books.json');

        if (!File::exists($catalogPath)) {
            $this->error("Catalog file not found at: {$catalogPath}");
            return Command::FAILURE;
        }

        $catalogData = json_decode(File::get($catalogPath), true);

        if (!$catalogData) {
            $this->error("Failed to parse catalog JSON file");
            return Command::FAILURE;
        }

        $this->info("Found " . count($catalogData) . " books in catalog");

        $isDryRun = $this->option('dry-run');
        if ($isDryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }

        // Get or create a "Books" category
        $bookCategory = Category::firstOrCreate(
            ['name' => 'Books'],
            [
                'slug' => 'books',
                'description' => 'Educational books for all levels',
            ]
        );

        $importedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $missingImageCount = 0;

        $progressBar = $this->output->createProgressBar(count($catalogData));
        $progressBar->start();

        foreach ($catalogData as $bookData) {
            $progressBar->advance();

            // Fix cover image path - convert relative path to public path
            $coverImage = $this->fixCoverImagePath($bookData['cover_image'] ?? '');

            // Check if image exists
            $imagePath = public_path('library/updates/' . ltrim($coverImage, '/'));
            if (!File::exists($imagePath)) {
                $missingImageCount++;
                $this->newLine();
                $this->warn("  Image not found: {$coverImage}");
                continue;
            }

            // Generate a unique slug if title is duplicated
            $slug = $bookData['slug'] ?? Str::slug($bookData['title']);
            $originalSlug = $slug;

            if ($isDryRun) {
                $this->newLine();
                $this->info("  Would import: {$bookData['title']}");
                $importedCount++;
                continue;
            }

            // Check if product already exists by slug
            $existingProduct = Product::where('slug', $slug)->first();

            if ($existingProduct) {
                // Check if cover_image is different to avoid updating the same record
                if ($existingProduct->cover_image === '/library/updates/' . $coverImage) {
                    $skippedCount++;
                    continue;
                }

                // Generate a unique slug for the new entry
                $counter = 1;
                while (Product::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            try {
                $product = Product::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'title' => $bookData['title'],
                        'description' => $bookData['description'] ?? '',
                        'price_zwl' => $bookData['price_zwl'] ?? 0,
                        'price_usd' => $bookData['price_usd'] ?? 0,
                        'category_id' => $bookCategory->id,
                        'syllabus' => $bookData['syllabus'] ?? 'Other',
                        'level' => $bookData['level'] ?? 'Other',
                        'subject' => $bookData['subject'] ?? null,
                        'publisher' => $bookData['publisher'] ?? null,
                        'isbn' => $bookData['isbn'] ?? null,
                        'author' => $bookData['author'] ?? 'Unknown',
                        'cover_image' => '/library/updates/' . $coverImage,
                        'stock_status' => $bookData['stock_status'] ?? 'in_stock',
                        'stock_quantity' => $bookData['stock_quantity'] ?? 10,
                        'featured' => $bookData['featured'] ?? false,
                    ]
                );

                if ($product->wasRecentlyCreated) {
                    $importedCount++;
                } else {
                    $updatedCount++;
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("  Error importing: {$bookData['title']} - " . $e->getMessage());
                $skippedCount++;
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("Import Summary:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Imported (new)', $importedCount],
                ['Updated (existing)', $updatedCount],
                ['Skipped (duplicates)', $skippedCount],
                ['Missing images', $missingImageCount],
                ['Total processed', count($catalogData)],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Fix the cover image path from relative to proper format
     */
    private function fixCoverImagePath(string $path): string
    {
        // Remove leading ./ if present
        $path = ltrim($path, './');

        // Remove leading / if present
        $path = ltrim($path, '/');

        return $path;
    }
}
