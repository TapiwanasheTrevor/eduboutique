<?php

namespace App\Jobs;

use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\OdooService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SyncProductsFromOdoo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    public $timeout = 300;

    /**
     * Execute the job.
     */
    public function handle(OdooService $odoo): void
    {
        try {
            Log::info('Starting product sync from Odoo');

            // Prevent observer from dispatching SyncProductToOdoo for each update
            ProductObserver::$syncingFromOdoo = true;

            // No need to fetch image_1920 — images are served directly from Odoo
            $products = $odoo->search(
                'product.product',
                [['sale_ok', '=', true]],
                [
                    'name',
                    'default_code',
                    'list_price',
                    'qty_available',
                    'description_sale',
                    'categ_id',
                ]
            );

            Log::info('Found ' . count($products) . ' products in Odoo');

            $odooIds = [];

            foreach ($products as $odooProduct) {
                $this->syncProduct($odooProduct);
                $odooIds[] = $odooProduct['id'];
            }

            // Remove orphaned products that no longer exist in Odoo
            $orphaned = Product::whereNotNull('odoo_product_id')
                ->whereNotIn('odoo_product_id', $odooIds)
                ->count();

            if ($orphaned > 0) {
                Product::whereNotNull('odoo_product_id')
                    ->whereNotIn('odoo_product_id', $odooIds)
                    ->delete();
                Log::info("Removed {$orphaned} orphaned products no longer in Odoo");
            }

            // Clear stale cover_image paths pointing to deleted local files
            Product::whereNotNull('cover_image')
                ->where('cover_image', '!=', '')
                ->where('cover_image', 'not like', 'http%')
                ->whereNotNull('odoo_product_id')
                ->update(['cover_image' => null]);

            ProductObserver::$syncingFromOdoo = false;

            Log::info('Product sync completed successfully');

        } catch (\Exception $e) {
            ProductObserver::$syncingFromOdoo = false;
            Log::error('Product sync failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Sync a single product from Odoo to Laravel.
     */
    protected function syncProduct(array $odooProduct): void
    {
        try {
            $slug = Str::slug($odooProduct['name']);

            // Append Odoo ID to slug if another product already uses it
            $existing = Product::where('slug', $slug)
                ->where('odoo_product_id', '!=', $odooProduct['id'])
                ->exists();

            if ($existing) {
                $slug = $slug . '-' . $odooProduct['id'];
            }

            $name = $odooProduct['name'];
            $categName = is_array($odooProduct['categ_id'] ?? false) ? ($odooProduct['categ_id'][1] ?? '') : '';

            $product = Product::updateOrCreate(
                ['odoo_product_id' => $odooProduct['id']],
                [
                    'title' => $name,
                    'slug' => $slug,
                    'price_usd' => $odooProduct['list_price'] ?? 0,
                    'stock_quantity' => $odooProduct['qty_available'] ?? 0,
                    'stock_status' => $this->determineStockStatus($odooProduct['qty_available'] ?? 0),
                    'description' => $odooProduct['description_sale'] ?? '',
                    'syllabus' => $this->detectSyllabus($name),
                    'level' => $this->detectLevel($name, $categName),
                    'odoo_synced_at' => now(),
                ]
            );

            Log::debug('Synced product: ' . $product->title, [
                'product_id' => $product->id,
                'odoo_product_id' => $odooProduct['id']
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync product: ' . ($odooProduct['name'] ?? 'Unknown'), [
                'odoo_product_id' => $odooProduct['id'] ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Determine stock status based on quantity.
     */
    protected function determineStockStatus(int|float $quantity): string
    {
        if ($quantity > 10) {
            return 'in_stock';
        }

        if ($quantity > 0) {
            return 'low_stock';
        }

        return 'out_of_stock';
    }

    /**
     * Detect syllabus from product name.
     */
    protected function detectSyllabus(string $name): string
    {
        $lower = strtolower($name);

        if (str_contains($lower, 'cambridge') || str_contains($lower, 'igcse')) {
            return 'Cambridge';
        }

        if (str_contains($lower, 'zimsec')) {
            return 'ZIMSEC';
        }

        // Common Cambridge publishers/series
        $cambridgeKeywords = ['oxford', 'longman', 'hodder', 'collins', 'edexcel', 'cie'];
        foreach ($cambridgeKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return 'Cambridge';
            }
        }

        // Common ZIMSEC publishers/series
        $zimsecKeywords = [
            'step ahead', 'pepukai', 'nhaka', 'zph', 'mambo', 'priority',
            'gramsol', 'ventures', 'plusone', 'plus one', 'turn-up', 'turn up',
            'cps ', 'asifunde', 'new trends', 'new general math',
        ];
        foreach ($zimsecKeywords as $keyword) {
            if (str_contains($lower, $keyword)) {
                return 'ZIMSEC';
            }
        }

        return 'Other';
    }

    /**
     * Detect level from product name and Odoo category.
     */
    protected function detectLevel(string $name, string $categName): string
    {
        $lower = strtolower($name);
        $categLower = strtolower($categName);

        // Check Odoo category first
        if (str_contains($categLower, 'a level') || str_contains($categLower, 'as/a')) {
            return 'A-Level';
        }
        if (str_contains($categLower, 'o level') || str_contains($categLower, 'igcse/o')) {
            return 'O-Level';
        }
        if ($categLower === 'primary') {
            return 'Primary';
        }
        if (str_contains($categLower, 'igcse')) {
            return 'IGCSE';
        }

        // Check product name
        if (preg_match('/\ba[\s-]?level\b/i', $name) || preg_match('/\bas[\s-]?level\b/i', $name)) {
            return 'A-Level';
        }
        if (preg_match('/\bo[\s-]?level\b/i', $name)) {
            return 'O-Level';
        }
        if (preg_match('/\bigcse\b/i', $name)) {
            return 'IGCSE';
        }
        if (preg_match('/\b(grade\s*[1-7]|primary)\b/i', $name)) {
            return 'Primary';
        }
        if (preg_match('/\becd\b/i', $name)) {
            return 'ECD';
        }
        if (preg_match('/\bas\s/i', $lower) || str_contains($lower, ' as ')) {
            return 'A-Level';
        }
        // Form-based levels (F1-F4 = O-Level, F5-F6 = A-Level)
        if (preg_match('/\bF\s*[5-6]\b/', $name)) {
            return 'A-Level';
        }
        if (preg_match('/\bF\s*[1-4]\b/', $name)) {
            return 'O-Level';
        }

        return 'Other';
    }
}
