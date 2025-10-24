<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    use HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'inquiry_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'delivery_method',
        'delivery_address',
        'delivery_city',
        'message',
        'cart_items',
        'total_zwl',
        'total_usd',
        'status',
        'odoo_order_id',
        'odoo_synced_at',
        'assigned_to',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cart_items' => 'array',
        'total_zwl' => 'decimal:2',
        'total_usd' => 'decimal:2',
        'odoo_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user assigned to the inquiry.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
