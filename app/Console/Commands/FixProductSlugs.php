<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class FixProductSlugs extends Command
{
    protected $signature = 'products:fix-slugs {--dry-run : Show what would be changed without making changes}';
    protected $description = 'Regenerate product slugs from titles';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn("DRY RUN MODE - No changes will be made\n");
        }

        $products = Product::all();
        $fixed = 0;
        $existingSlugs = [];

        $this->output->progressStart($products->count());

        foreach ($products as $product) {
            $this->output->progressAdvance();

            // Generate clean slug from title
            $newSlug = Str::slug($product->title);

            // Skip if empty
            if (empty($newSlug)) {
                $newSlug = 'product-' . $product->id;
            }

            // Handle duplicates by appending counter
            $baseSlug = $newSlug;
            $counter = 1;
            while (in_array($newSlug, $existingSlugs) ||
                   Product::where('slug', $newSlug)->where('id', '!=', $product->id)->exists()) {
                $newSlug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $existingSlugs[] = $newSlug;

            if ($product->slug !== $newSlug) {
                if ($isDryRun && $fixed < 20) {
                    $this->newLine();
                    $this->line("  <comment>{$product->title}</comment>");
                    $this->line("    Slug: {$product->slug} -> <info>{$newSlug}</info>");
                }

                if (!$isDryRun) {
                    $product->update(['slug' => $newSlug]);
                }
                $fixed++;
            }
        }

        $this->output->progressFinish();

        $this->newLine();
        $this->info("Fixed {$fixed} product slugs");

        return Command::SUCCESS;
    }
}
