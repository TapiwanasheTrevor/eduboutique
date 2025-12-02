<?php

namespace App\Observers;

use App\Models\Product;
use App\Jobs\SyncProductToOdoo;
use App\Jobs\SyncStockToOdoo;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    /**
     * Flag to prevent sync loops.
     * When syncing FROM Odoo, we set this to avoid re-syncing TO Odoo.
     */
    public static bool $syncingFromOdoo = false;

    /**
     * Fields that trigger a full product sync to Odoo.
     */
    protected array $syncableFields = [
        'title',
        'description',
        'price_usd',
        'price_zwl',
        'stock_quantity',
        'stock_status',
        'cover_image',
        'isbn',
        'author',
        'publisher',
    ];

    /**
     * Fields that indicate an Odoo-originated update (should not re-sync)
     */
    protected array $odooOnlyFields = [
        'odoo_product_id',
        'odoo_synced_at',
    ];

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        // Skip if we're currently syncing from Odoo
        if (self::$syncingFromOdoo) {
            Log::debug('Skipping sync to Odoo - created during Odoo import', [
                'product_id' => $product->id
            ]);
            return;
        }

        // Sync new products to Odoo
        Log::info('Product created, dispatching sync to Odoo', [
            'product_id' => $product->id,
            'title' => $product->title
        ]);

        SyncProductToOdoo::dispatch($product);
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        // Skip if we're currently syncing from Odoo
        if (self::$syncingFromOdoo) {
            Log::debug('Skipping sync to Odoo - updated during Odoo import', [
                'product_id' => $product->id
            ]);
            return;
        }

        // Check if only Odoo-related fields changed (sync metadata update)
        $allChangedFields = array_keys($product->getChanges());
        $nonOdooChanges = array_diff($allChangedFields, $this->odooOnlyFields);

        if (empty($nonOdooChanges)) {
            Log::debug('Skipping sync - only Odoo metadata fields changed', [
                'product_id' => $product->id
            ]);
            return;
        }

        // Check if any syncable fields were changed
        $changedFields = array_intersect($nonOdooChanges, $this->syncableFields);

        if (empty($changedFields)) {
            return;
        }

        Log::info('Product updated, dispatching sync to Odoo', [
            'product_id' => $product->id,
            'title' => $product->title,
            'changed_fields' => $changedFields
        ]);

        // If only stock changed, use the lighter stock sync job
        if (count($changedFields) === 1 && in_array('stock_quantity', $changedFields)) {
            SyncStockToOdoo::dispatch($product);
        } else {
            // Full product sync for other changes
            SyncProductToOdoo::dispatch($product);
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        // Optionally handle deletion in Odoo
        // For now, just log it - Odoo products are typically archived, not deleted
        Log::info('Product deleted locally', [
            'product_id' => $product->id,
            'title' => $product->title,
            'odoo_product_id' => $product->odoo_product_id
        ]);
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        // Re-sync restored products
        SyncProductToOdoo::dispatch($product);
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        Log::warning('Product force deleted', [
            'product_id' => $product->id,
            'odoo_product_id' => $product->odoo_product_id
        ]);
    }
}
