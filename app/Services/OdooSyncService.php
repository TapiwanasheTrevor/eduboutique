<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\Inquiry;
use App\Models\OdooSyncLog;
use App\Observers\ProductObserver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OdooSyncService
{
    protected OdooService $odoo;

    /**
     * Conflict resolution strategy:
     * - 'odoo_wins': Odoo data takes precedence
     * - 'local_wins': Local data takes precedence
     * - 'newest_wins': Most recently updated data wins
     */
    protected string $conflictStrategy = 'newest_wins';

    /**
     * Fields to sync for products
     */
    protected array $productSyncFields = [
        'name',
        'default_code',
        'list_price',
        'qty_available',
        'description_sale',
        'image_1920',
        'categ_id',
        'write_date',
    ];

    public function __construct(OdooService $odoo)
    {
        $this->odoo = $odoo;
    }

    /**
     * Set conflict resolution strategy
     */
    public function setConflictStrategy(string $strategy): self
    {
        $this->conflictStrategy = $strategy;
        return $this;
    }

    /**
     * Full bidirectional sync of products
     */
    public function syncProducts(): array
    {
        $stats = [
            'pulled' => 0,
            'pushed' => 0,
            'skipped' => 0,
            'conflicts' => 0,
            'errors' => 0,
        ];

        DB::beginTransaction();

        try {
            // Step 1: Pull products from Odoo
            $pullStats = $this->pullProductsFromOdoo();
            $stats['pulled'] = $pullStats['synced'];
            $stats['skipped'] += $pullStats['skipped'];
            $stats['conflicts'] += $pullStats['conflicts'];

            // Step 2: Push local-only products to Odoo
            $pushStats = $this->pushProductsToOdoo();
            $stats['pushed'] = $pushStats['synced'];
            $stats['skipped'] += $pushStats['skipped'];
            $stats['errors'] += $pushStats['errors'];

            DB::commit();

            Log::info('Product sync completed', $stats);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Product sync failed: ' . $e->getMessage());
            throw $e;
        }

        return $stats;
    }

    /**
     * Pull products from Odoo to local database
     */
    public function pullProductsFromOdoo(): array
    {
        $stats = ['synced' => 0, 'skipped' => 0, 'conflicts' => 0];

        Log::info('Pulling products from Odoo...');

        // Set flag to prevent sync loops
        ProductObserver::$syncingFromOdoo = true;

        try {
            // Get all saleable products from Odoo
            $odooProducts = $this->odoo->search(
                'product.product',
                [['sale_ok', '=', true]],
                $this->productSyncFields
            );

            Log::info('Found ' . count($odooProducts) . ' products in Odoo');

            foreach ($odooProducts as $odooProduct) {
                $result = $this->syncProductFromOdoo($odooProduct);

                if ($result === 'synced') {
                    $stats['synced']++;
                } elseif ($result === 'skipped') {
                    $stats['skipped']++;
                } elseif ($result === 'conflict') {
                    $stats['conflicts']++;
                }
            }
        } finally {
            // Always reset the flag
            ProductObserver::$syncingFromOdoo = false;
        }

        return $stats;
    }

    /**
     * Push local products to Odoo
     */
    public function pushProductsToOdoo(): array
    {
        $stats = ['synced' => 0, 'skipped' => 0, 'errors' => 0];

        Log::info('Pushing local products to Odoo...');

        // Get products that don't have an Odoo ID yet
        $localOnlyProducts = Product::whereNull('odoo_product_id')->get();

        Log::info('Found ' . $localOnlyProducts->count() . ' local-only products');

        foreach ($localOnlyProducts as $product) {
            try {
                $this->pushProductToOdoo($product);
                $stats['synced']++;
            } catch (\Exception $e) {
                Log::error('Failed to push product: ' . $product->title, [
                    'error' => $e->getMessage()
                ]);
                $stats['errors']++;
            }
        }

        return $stats;
    }

    /**
     * Sync a single product from Odoo with conflict detection
     */
    protected function syncProductFromOdoo(array $odooProduct): string
    {
        $odooId = $odooProduct['id'];
        $odooUpdatedAt = isset($odooProduct['write_date'])
            ? Carbon::parse($odooProduct['write_date'])
            : now();

        // Check if product exists locally
        $localProduct = Product::where('odoo_product_id', $odooId)->first();

        // Also check by name/slug for potential duplicates
        if (!$localProduct) {
            $slug = Str::slug($odooProduct['name']);
            $localProduct = Product::where('slug', $slug)->first();

            // If found by slug but no odoo_id, this is a duplicate scenario
            if ($localProduct && !$localProduct->odoo_product_id) {
                Log::info('Found local product by slug, linking to Odoo', [
                    'product' => $localProduct->title,
                    'odoo_id' => $odooId
                ]);
            }
        }

        if ($localProduct) {
            // Product exists - check for conflicts
            return $this->handleProductConflict($localProduct, $odooProduct, $odooUpdatedAt);
        }

        // New product - create locally
        $this->createProductFromOdoo($odooProduct);
        return 'synced';
    }

    /**
     * Handle conflict between local and Odoo product
     */
    protected function handleProductConflict(Product $localProduct, array $odooProduct, Carbon $odooUpdatedAt): string
    {
        $localUpdatedAt = $localProduct->updated_at;
        $odooSyncedAt = $localProduct->odoo_synced_at;

        // If local hasn't changed since last sync, always update from Odoo
        if ($odooSyncedAt && $localUpdatedAt <= $odooSyncedAt) {
            $this->updateProductFromOdoo($localProduct, $odooProduct);
            return 'synced';
        }

        // Both sides have changes - apply conflict strategy
        $shouldUpdate = match ($this->conflictStrategy) {
            'odoo_wins' => true,
            'local_wins' => false,
            'newest_wins' => $odooUpdatedAt > $localUpdatedAt,
            default => true,
        };

        if ($shouldUpdate) {
            Log::info('Conflict resolved: Odoo wins', [
                'product' => $localProduct->title,
                'strategy' => $this->conflictStrategy
            ]);
            $this->updateProductFromOdoo($localProduct, $odooProduct);
            return 'synced';
        }

        Log::info('Conflict resolved: Local wins', [
            'product' => $localProduct->title,
            'strategy' => $this->conflictStrategy
        ]);
        return 'conflict';
    }

    /**
     * Create a new product from Odoo data
     */
    protected function createProductFromOdoo(array $odooProduct): Product
    {
        $product = Product::create([
            'odoo_product_id' => $odooProduct['id'],
            'title' => $odooProduct['name'],
            'slug' => $this->generateUniqueSlug($odooProduct['name']),
            'price_usd' => $odooProduct['list_price'] ?? 0,
            'price_zwl' => ($odooProduct['list_price'] ?? 0) * 35000, // Approximate ZWL rate
            'stock_quantity' => (int) ($odooProduct['qty_available'] ?? 0),
            'stock_status' => $this->determineStockStatus($odooProduct['qty_available'] ?? 0),
            'description' => $odooProduct['description_sale'] ?? '',
            'odoo_synced_at' => now(),
        ]);

        // Sync image if available
        if (!empty($odooProduct['image_1920'])) {
            $this->syncProductImage($product, $odooProduct['image_1920']);
        }

        Log::info('Created product from Odoo: ' . $product->title);

        return $product;
    }

    /**
     * Update existing product from Odoo data
     */
    protected function updateProductFromOdoo(Product $product, array $odooProduct): void
    {
        $product->update([
            'odoo_product_id' => $odooProduct['id'],
            'title' => $odooProduct['name'],
            'price_usd' => $odooProduct['list_price'] ?? $product->price_usd,
            'stock_quantity' => (int) ($odooProduct['qty_available'] ?? $product->stock_quantity),
            'stock_status' => $this->determineStockStatus($odooProduct['qty_available'] ?? 0),
            'description' => $odooProduct['description_sale'] ?? $product->description,
            'odoo_synced_at' => now(),
        ]);

        Log::info('Updated product from Odoo: ' . $product->title);
    }

    /**
     * Push a product to Odoo
     */
    public function pushProductToOdoo(Product $product): int
    {
        // Check if product already exists in Odoo by name
        $existingProducts = $this->odoo->search(
            'product.product',
            [['name', '=', $product->title]],
            ['id', 'name']
        );

        if (!empty($existingProducts)) {
            // Link to existing Odoo product
            $odooId = $existingProducts[0]['id'];
            $product->update([
                'odoo_product_id' => $odooId,
                'odoo_synced_at' => now(),
            ]);

            Log::info('Linked local product to existing Odoo product', [
                'product' => $product->title,
                'odoo_id' => $odooId
            ]);

            return $odooId;
        }

        // Create new product in Odoo
        // Note: In Odoo 17+, product types are: 'consu' (consumable), 'service', or 'combo'
        // For storable products, use 'consu' and enable tracking
        $productData = [
            'name' => $product->title,
            'default_code' => $product->item_code ?? $product->isbn ?? $product->slug,
            'list_price' => $product->price_usd ?? 0,
            'description_sale' => $product->description ?? '',
            'sale_ok' => true,
            'purchase_ok' => true,
            'type' => 'consu', // Consumable/storable product (Odoo 17+)
        ];

        // Add image if exists
        if ($product->cover_image) {
            $imagePath = public_path($product->cover_image);
            if (file_exists($imagePath)) {
                $productData['image_1920'] = base64_encode(file_get_contents($imagePath));
            }
        }

        $odooId = $this->odoo->create('product.product', $productData);

        $product->update([
            'odoo_product_id' => $odooId,
            'odoo_synced_at' => now(),
        ]);

        Log::info('Created product in Odoo', [
            'product' => $product->title,
            'odoo_id' => $odooId
        ]);

        return $odooId;
    }

    /**
     * Sync stock levels only (lightweight sync)
     */
    public function syncStockLevels(): array
    {
        $stats = ['updated' => 0, 'errors' => 0];

        $products = Product::whereNotNull('odoo_product_id')->get();

        foreach ($products as $product) {
            try {
                $odooProducts = $this->odoo->read(
                    'product.product',
                    [$product->odoo_product_id],
                    ['qty_available']
                );

                if (!empty($odooProducts)) {
                    $qty = $odooProducts[0]['qty_available'] ?? 0;
                    $product->update([
                        'stock_quantity' => (int) $qty,
                        'stock_status' => $this->determineStockStatus($qty),
                        'odoo_synced_at' => now(),
                    ]);
                    $stats['updated']++;
                }
            } catch (\Exception $e) {
                Log::error('Failed to sync stock for: ' . $product->title);
                $stats['errors']++;
            }
        }

        return $stats;
    }

    /**
     * Generate unique slug
     */
    protected function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $count = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }

    /**
     * Determine stock status based on quantity
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
     * Sync product image from base64
     */
    protected function syncProductImage(Product $product, string $base64Image): void
    {
        try {
            $imageData = base64_decode($base64Image);
            if ($imageData === false) {
                return;
            }

            $filename = 'products/' . $product->slug . '-' . time() . '.jpg';
            \Storage::disk('public')->put($filename, $imageData);

            $product->update(['cover_image' => '/storage/' . $filename]);

        } catch (\Exception $e) {
            Log::error('Failed to sync image: ' . $e->getMessage());
        }
    }

    /**
     * Get sync status summary
     */
    public function getSyncStatus(): array
    {
        return [
            'total_products' => Product::count(),
            'synced_products' => Product::whereNotNull('odoo_product_id')->count(),
            'unsynced_products' => Product::whereNull('odoo_product_id')->count(),
            'last_sync' => Product::whereNotNull('odoo_synced_at')
                ->orderBy('odoo_synced_at', 'desc')
                ->value('odoo_synced_at'),
            'sync_logs_today' => OdooSyncLog::whereDate('synced_at', today())->count(),
            'sync_errors_today' => OdooSyncLog::whereDate('synced_at', today())
                ->where('status', 'error')
                ->count(),
        ];
    }
}
