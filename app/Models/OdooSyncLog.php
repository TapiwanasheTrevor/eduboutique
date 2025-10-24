<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OdooSyncLog extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'model',
        'record_id',
        'operation',
        'direction',
        'status',
        'request_data',
        'response_data',
        'error_message',
        'synced_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'synced_at' => 'datetime',
    ];
}
