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

    public $timeout = 300;

    /**
     * Execute the job.
     */
    public function handle(OdooService $odoo): void
    {
        try {
            Log::info('Starting stock levels sync from Odoo');

            // Fetch all stock levels from Odoo in one batch call
            $odooProducts = $odoo->search(
                'product.product',
                [['sale_ok', '=', true]],
                ['qty_available']
            );

            // Index by Odoo product ID for fast lookup
            $stockByOdooId = collect($odooProducts)->keyBy('id');

            Log::info('Fetched stock levels from Odoo', ['count' => $stockByOdooId->count()]);

            $successCount = 0;
            $failCount = 0;

            // Update local products in chunks
            Product::whereNotNull('odoo_product_id')->chunk(200, function ($products) use ($stockByOdooId, &$successCount, &$failCount) {
                foreach ($products as $product) {
                    $odooData = $stockByOdooId->get($product->odoo_product_id);

                    if (!$odooData) {
                        $failCount++;
                        continue;
                    }

                    $quantity = $odooData['qty_available'] ?? 0;

                    $product->update([
                        'stock_quantity' => $quantity,
                        'stock_status' => $this->determineStockStatus($quantity),
                        'odoo_synced_at' => now(),
                    ]);

                    $successCount++;
                }
            });

            Log::info('Stock levels sync completed', [
                'success' => $successCount,
                'failed' => $failCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Stock levels sync failed: ' . $e->getMessage());
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
