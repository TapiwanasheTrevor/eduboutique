<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'mobile',
        'company',
        'job_title',
        'street',
        'street2',
        'city',
        'state',
        'zip',
        'country',
        'type',
        'source',
        'odoo_partner_id',
        'odoo_synced_at',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'odoo_synced_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get inquiries for this customer.
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    /**
     * Get or create customer from inquiry data.
     */
    public static function findOrCreateFromInquiry(Inquiry $inquiry): self
    {
        return static::firstOrCreate(
            ['email' => $inquiry->customer_email],
            [
                'name' => $inquiry->customer_name,
                'phone' => $inquiry->customer_phone,
                'city' => $inquiry->delivery_city,
                'street' => $inquiry->delivery_address,
                'source' => 'inquiry',
            ]
        );
    }

    /**
     * Get full address as string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street,
            $this->street2,
            $this->city,
            $this->state,
            $this->zip,
            $this->country,
        ]);

        return implode(', ', $parts);
    }

    /**
     * Check if customer is synced with Odoo.
     */
    public function isSyncedWithOdoo(): bool
    {
        return !is_null($this->odoo_partner_id);
    }

    /**
     * Scope for unsynced customers.
     */
    public function scopeUnsynced($query)
    {
        return $query->whereNull('odoo_partner_id');
    }

    /**
     * Scope for synced customers.
     */
    public function scopeSynced($query)
    {
        return $query->whereNotNull('odoo_partner_id');
    }
}
