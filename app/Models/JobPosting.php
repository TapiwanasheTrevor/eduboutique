<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JobPosting extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'requirements',
        'department',
        'location',
        'employment_type',
        'experience_level',
        'salary_min',
        'salary_max',
        'salary_currency',
        'is_active',
        'published_at',
        'deadline',
        'odoo_job_id',
        'odoo_synced_at',
    ];

    protected $casts = [
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'deadline' => 'datetime',
        'odoo_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($job) {
            if (empty($job->slug)) {
                $job->slug = Str::slug($job->title);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('deadline')
              ->orWhere('deadline', '>', now());
        });
    }

    public function getFormattedSalaryAttribute(): ?string
    {
        if (!$this->salary_min && !$this->salary_max) {
            return null;
        }

        $currency = $this->salary_currency;

        if ($this->salary_min && $this->salary_max) {
            return "{$currency} " . number_format($this->salary_min) . " - " . number_format($this->salary_max);
        }

        if ($this->salary_min) {
            return "From {$currency} " . number_format($this->salary_min);
        }

        return "Up to {$currency} " . number_format($this->salary_max);
    }
}
