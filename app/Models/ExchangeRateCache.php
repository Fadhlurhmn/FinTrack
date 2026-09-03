<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRateCache extends Model
{
    protected $table = 'exchange_rates_cache';

    protected $fillable = ['base_currency', 'target_currency', 'rate', 'fetched_at'];

    protected $casts = [
        'fetched_at' => 'datetime',
        'rate' => 'decimal:6',
    ];
}