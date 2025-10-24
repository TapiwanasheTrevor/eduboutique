<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\OdooService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncStockLevelsFromOdoo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(OdooService $odoo): void
    {
        try {
            Log::info('Starting stock levels sync from Odoo');

            // Get all products that have odoo_product_id
            $products = Product::whereNotNull('odoo_product_id')->get();

            Log::info('Found ' . $products->count() . ' products to sync stock levels');

            $successCount = 0;
            $failCount = 0;

            foreach ($products as $product) {
                try {
                    $this->syncStockLevel($odoo, $product);
                    $successCount++;
                } catch (\Exception $e) {
                    $failCount++;
                    Log::error('Failed to sync stock level for product: ' . $product->title, [
                        'product_id' => $product->id,
                        'odoo_product_id' => $product->odoo_product_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            Log::info('Stock levels sync completed', [
                'success' => $successCount,
                'failed' => $failCount,
                'total' => $products->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Stock levels sync failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Sync stock level for a single product.
     */
    protected function syncStockLevel(OdooService $odoo, Product $product): void
    {
        try {
            // Read product data from Odoo
            $odooProduct = $odoo->read(
                'product.product',
                [$product->odoo_product_id],
                ['qty_available']
            );

            if (empty($odooProduct)) {
                Log::warning('Product not found in Odoo', [
                    'product_id' => $product->id,
                    'odoo_product_id' => $product->odoo_product_id
                ]);
                return;
            }

            $quantity = $odooProduct[0]['qty_available'] ?? 0;

            // Update product stock in Laravel
            $product->update([
                'stock_quantity' => $quantity,
                'stock_status' => $this->determineStockStatus($quantity),
                'odoo_synced_at' => now(),
            ]);

            Log::debug('Synced stock level for product: ' . $product->title, [
                'product_id' => $product->id,
                'odoo_product_id' => $product->odoo_product_id,
                'quantity' => $quantity,
                'status' => $product->stock_status
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync stock level for product', [
                'product_id' => $product->id,
                'odoo_product_id' => $product->odoo_product_id,
                'error' => $e->getMessage()
            ]);

            throw $e;
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
