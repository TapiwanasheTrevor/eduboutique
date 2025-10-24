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

    /**
     * Execute the job.
     */
    public function handle(OdooService $odoo): void
    {
        try {
            Log::info('Starting product sync from Odoo');

            // Search for products where sale_ok = true
            $products = $odoo->search(
                'product.product',
                [['sale_ok', '=', true]], // Only products available for sale
                [
                    'name',
                    'default_code',
                    'list_price',
                    'qty_available',
                    'description_sale',
                    'image_1920',
                    'categ_id',
                ]
            );

            Log::info('Found ' . count($products) . ' products in Odoo');

            foreach ($products as $odooProduct) {
                $this->syncProduct($odooProduct);
            }

            Log::info('Product sync completed successfully');

        } catch (\Exception $e) {
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
            $product = Product::updateOrCreate(
                ['odoo_product_id' => $odooProduct['id']],
                [
                    'title' => $odooProduct['name'],
                    'slug' => Str::slug($odooProduct['name']),
                    'price_usd' => $odooProduct['list_price'] ?? 0,
                    'stock_quantity' => $odooProduct['qty_available'] ?? 0,
                    'stock_status' => $this->determineStockStatus($odooProduct['qty_available'] ?? 0),
                    'description' => $odooProduct['description_sale'] ?? '',
                    'odoo_synced_at' => now(),
                ]
            );

            // Handle image sync if needed
            if (isset($odooProduct['image_1920']) && !empty($odooProduct['image_1920'])) {
                $this->syncProductImage($product, $odooProduct['image_1920']);
            }

            Log::info('Synced product: ' . $product->title, [
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
     * Sync product image from Odoo.
     */
    protected function syncProductImage(Product $product, string $base64Image): void
    {
        try {
            // Decode base64 image
            $imageData = base64_decode($base64Image);

            if ($imageData === false) {
                Log::warning('Failed to decode image for product: ' . $product->title);
                return;
            }

            // Generate unique filename
            $filename = 'products/' . $product->slug . '-' . time() . '.jpg';

            // Store the image
            \Storage::disk('public')->put($filename, $imageData);

            // Update product with image path
            $product->update([
                'cover_image' => $filename
            ]);

            Log::info('Synced image for product: ' . $product->title);

        } catch (\Exception $e) {
            Log::error('Failed to sync image for product: ' . $product->title, [
                'error' => $e->getMessage()
            ]);
        }
    }
}
