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

class SyncStockToOdoo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Product $product;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    /**
     * Create a new job instance.
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Execute the job.
     */
    public function handle(OdooService $odoo): void
    {
        try {
            // Skip if product is not linked to Odoo
            if (!$this->product->odoo_product_id) {
                Log::info('Product not linked to Odoo, skipping stock sync', [
                    'product_id' => $this->product->id,
                    'title' => $this->product->title
                ]);
                return;
            }

            Log::info('Starting stock sync to Odoo', [
                'product_id' => $this->product->id,
                'odoo_product_id' => $this->product->odoo_product_id,
                'stock_quantity' => $this->product->stock_quantity
            ]);

            // Update stock quantity in Odoo
            // Note: In Odoo, stock is typically managed through stock.quant or inventory adjustments
            // For simple cases, we can update the product template's qty_available (read-only)
            // or create a stock.inventory adjustment

            // First, try to find the product's stock quant location
            $stockQuants = $odoo->search(
                'stock.quant',
                [['product_id', '=', $this->product->odoo_product_id]],
                ['id', 'quantity', 'location_id']
            );

            if (!empty($stockQuants)) {
                // Update existing stock quant
                foreach ($stockQuants as $quant) {
                    // Only update internal stock locations (not virtual locations)
                    $odoo->update('stock.quant', $quant['id'], [
                        'quantity' => $this->product->stock_quantity,
                    ]);

                    Log::info('Updated stock quant in Odoo', [
                        'quant_id' => $quant['id'],
                        'quantity' => $this->product->stock_quantity
                    ]);
                    break; // Only update the first quant
                }
            } else {
                // Create inventory adjustment if no quant exists
                $this->createInventoryAdjustment($odoo);
            }

            // Update the sync timestamp
            $this->product->update([
                'odoo_synced_at' => now(),
            ]);

            Log::info('Stock sync to Odoo completed', [
                'product_id' => $this->product->id,
                'odoo_product_id' => $this->product->odoo_product_id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync stock to Odoo: ' . $e->getMessage(), [
                'product_id' => $this->product->id,
                'odoo_product_id' => $this->product->odoo_product_id,
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Create an inventory adjustment in Odoo.
     */
    protected function createInventoryAdjustment(OdooService $odoo): void
    {
        try {
            // Get the default stock location (WH/Stock)
            $locations = $odoo->search(
                'stock.location',
                [
                    ['usage', '=', 'internal'],
                    ['name', '=', 'Stock']
                ],
                ['id', 'name']
            );

            if (empty($locations)) {
                Log::warning('No stock location found in Odoo for inventory adjustment');
                return;
            }

            $locationId = $locations[0]['id'];

            // Create stock quant directly (Odoo 14+)
            $quantId = $odoo->create('stock.quant', [
                'product_id' => $this->product->odoo_product_id,
                'location_id' => $locationId,
                'quantity' => $this->product->stock_quantity,
            ]);

            Log::info('Created stock quant in Odoo', [
                'quant_id' => $quantId,
                'product_id' => $this->product->odoo_product_id,
                'quantity' => $this->product->stock_quantity
            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to create inventory adjustment in Odoo', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage()
            ]);
            // Don't throw - this is a secondary operation
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SyncStockToOdoo job failed permanently', [
            'product_id' => $this->product->id,
            'odoo_product_id' => $this->product->odoo_product_id,
            'error' => $exception->getMessage()
        ]);
    }
}
