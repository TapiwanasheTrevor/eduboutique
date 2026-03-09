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

            $product = Product::updateOrCreate(
                ['odoo_product_id' => $odooProduct['id']],
                [
                    'title' => $odooProduct['name'],
                    'slug' => $slug,
                    'price_usd' => $odooProduct['list_price'] ?? 0,
                    'stock_quantity' => $odooProduct['qty_available'] ?? 0,
                    'stock_status' => $this->determineStockStatus($odooProduct['qty_available'] ?? 0),
                    'description' => $odooProduct['description_sale'] ?? '',
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
}
