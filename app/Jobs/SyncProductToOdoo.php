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
use Illuminate\Support\Facades\Storage;

class SyncProductToOdoo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Product $product;

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
            Log::info('Starting product sync to Odoo', [
                'product_id' => $this->product->id,
                'product_title' => $this->product->title
            ]);

            // Prepare product data for Odoo
            $productData = [
                'name' => $this->product->title,
                'default_code' => $this->product->isbn ?? $this->product->slug,
                'list_price' => $this->product->price_usd,
                'description_sale' => strip_tags($this->product->description),
                'sale_ok' => true,
                'purchase_ok' => false,
                'type' => 'product', // 'product' for stockable, 'service' for non-stockable
            ];

            // Add stock quantity
            if ($this->product->stock_quantity > 0) {
                $productData['qty_available'] = $this->product->stock_quantity;
            }

            // Sync category if available
            if ($this->product->category_id && $this->product->category) {
                $categoryId = $this->syncCategory($odoo);
                if ($categoryId) {
                    $productData['categ_id'] = $categoryId;
                }
            }

            // Sync product image if available
            if ($this->product->cover_image) {
                $imageBase64 = $this->getImageBase64();
                if ($imageBase64) {
                    $productData['image_1920'] = $imageBase64;
                }
            }

            // Create or update product in Odoo
            if ($this->product->odoo_product_id) {
                // Update existing product
                $odoo->update('product.product', $this->product->odoo_product_id, $productData);

                Log::info('Updated product in Odoo', [
                    'product_id' => $this->product->id,
                    'odoo_product_id' => $this->product->odoo_product_id
                ]);
            } else {
                // Create new product
                $odooProductId = $odoo->create('product.product', $productData);

                // Update local product with Odoo ID
                $this->product->update([
                    'odoo_product_id' => $odooProductId,
                ]);

                Log::info('Created product in Odoo', [
                    'product_id' => $this->product->id,
                    'odoo_product_id' => $odooProductId
                ]);
            }

            // Update sync timestamp
            $this->product->update([
                'odoo_synced_at' => now(),
            ]);

            Log::info('Product synced successfully to Odoo', [
                'product_id' => $this->product->id,
                'odoo_product_id' => $this->product->odoo_product_id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync product to Odoo: ' . $e->getMessage(), [
                'product_id' => $this->product->id,
                'product_title' => $this->product->title,
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Sync category to Odoo and return its ID.
     */
    protected function syncCategory(OdooService $odoo): ?int
    {
        try {
            $category = $this->product->category;

            // If category already has Odoo ID, return it
            if ($category->odoo_category_id) {
                return $category->odoo_category_id;
            }

            // Search for existing category by name
            $categories = $odoo->search(
                'product.category',
                [['name', '=', $category->name]],
                ['id', 'name']
            );

            if (!empty($categories)) {
                $odooCategoryId = $categories[0]['id'];

                // Update local category with Odoo ID
                $category->update(['odoo_category_id' => $odooCategoryId]);

                return $odooCategoryId;
            }

            // Create new category in Odoo
            $categoryData = [
                'name' => $category->name,
            ];

            $odooCategoryId = $odoo->create('product.category', $categoryData);

            // Update local category with Odoo ID
            $category->update(['odoo_category_id' => $odooCategoryId]);

            Log::info('Created category in Odoo', [
                'category_id' => $category->id,
                'odoo_category_id' => $odooCategoryId
            ]);

            return $odooCategoryId;

        } catch (\Exception $e) {
            Log::error('Failed to sync category to Odoo', [
                'category_id' => $this->product->category_id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Get product image as base64 string.
     */
    protected function getImageBase64(): ?string
    {
        try {
            $imagePath = $this->product->cover_image;

            // Check if image exists
            if (!Storage::disk('public')->exists($imagePath)) {
                Log::warning('Product image not found', [
                    'product_id' => $this->product->id,
                    'image_path' => $imagePath
                ]);
                return null;
            }

            // Get image content
            $imageContent = Storage::disk('public')->get($imagePath);

            // Convert to base64
            $base64Image = base64_encode($imageContent);

            return $base64Image;

        } catch (\Exception $e) {
            Log::error('Failed to get product image as base64', [
                'product_id' => $this->product->id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}
