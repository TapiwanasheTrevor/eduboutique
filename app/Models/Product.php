<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasUuids;

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
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
