<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\OdooService;
use Illuminate\Console\Command;
use Exception;

class UpdateOdooInternalRefs extends Command
{
    protected $signature = 'odoo:update-refs {--dry-run : Show what would be updated without making changes}';
    protected $description = 'Update Odoo product internal references with corrected slugs';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn("DRY RUN MODE - No changes will be made\n");
        }

        try {
            $odoo = new OdooService();
        } catch (Exception $e) {
            $this->error('Failed to connect to Odoo: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Get products that have been synced to Odoo
        $products = Product::whereNotNull('odoo_product_id')->get();

        $this->info("Found {$products->count()} products synced to Odoo");
        $this->newLine();

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $this->output->progressStart($products->count());

        foreach ($products as $product) {
            $this->output->progressAdvance();

            // The internal reference should be item_code first, then ISBN, then slug
            $newRef = $product->item_code ?? $product->isbn ?? $product->slug;

            // Skip if no odoo_product_id
            if (!$product->odoo_product_id) {
                $skipped++;
                continue;
            }

            if (!$isDryRun) {
                try {
                    $odoo->update('product.product', $product->odoo_product_id, [
                        'default_code' => $newRef,
                    ]);
                    $updated++;
                } catch (Exception $e) {
                    $errors++;
                    if ($errors <= 5) {
                        $this->newLine();
                        $this->error("Failed to update {$product->title}: " . $e->getMessage());
                    }
                }
            } else {
                $updated++;
                if ($updated <= 20) {
                    $this->newLine();
                    $this->line("  <comment>{$product->title}</comment>");
                    $this->line("    Internal Ref: <info>{$newRef}</info>");
                }
            }
        }

        $this->output->progressFinish();

        $this->newLine();
        $this->info("Summary:");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Products updated', $updated],
                ['Skipped (no Odoo ID)', $skipped],
                ['Errors', $errors],
            ]
        );

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
