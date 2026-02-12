<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasUuids;

    protected $appends = ['cover_image_url'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'price_zwl',
        'price_usd',
        'category_id',
        'syllabus',
        'level',
        'subject',
        'publisher',
        'isbn',
        'item_code',
        'author',
        'cover_image',
        'stock_status',
        'stock_quantity',
        'featured',
        'odoo_product_id',
        'odoo_synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price_zwl' => 'decimal:2',
        'price_usd' => 'decimal:2',
        'stock_quantity' => 'integer',
        'featured' => 'boolean',
        'odoo_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the resolved image URL.
     * - Odoo products: serve from Odoo directly (no local storage needed)
     * - Local uploads: serve from Laravel storage
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        $image = $this->cover_image;

        // Already a full URL (e.g. previously stored Odoo URL)
        if ($image && str_starts_with($image, 'http')) {
            return $image;
        }

        // Local file uploaded via Filament
        if ($image && Storage::disk('public')->exists($image)) {
            return Storage::disk('public')->url($image);
        }

        // Odoo-synced product — serve image from Odoo directly
        if ($this->odoo_product_id) {
            return 'https://eduboutique.odoo.com/web/image/product.product/' . $this->odoo_product_id . '/image_1920';
        }

        return null;
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
