<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoPair extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'base_currency',
        'quote_currency',
        'current_price',
        'price_change_24h',
        'price_change_percentage_24h',
        'high_24h',
        'low_24h',
        'volume_24h',
        'market_cap',
        'is_active',
        'last_updated',
    ];

    protected $casts = [
        'current_price' => 'decimal:8',
        'price_change_24h' => 'decimal:4',
        'price_change_percentage_24h' => 'decimal:4',
        'high_24h' => 'decimal:8',
        'low_24h' => 'decimal:8',
        'volume_24h' => 'decimal:8',
        'market_cap' => 'decimal:2',
        'is_active' => 'boolean',
        'last_updated' => 'datetime',
    ];

    public function trades()
    {
        return $this->hasMany(Trade::class, 'pair', 'symbol');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'pair', 'symbol');
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->current_price, 8);
    }

    public function getFormattedVolumeAttribute()
    {
        return number_format($this->volume_24h, 2);
    }

    public function getPriceChangeColorAttribute()
    {
        return $this->price_change_percentage_24h >= 0 ? 'green' : 'red';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePopular($query)
    {
        return $query->orderBy('volume_24h', 'desc');
    }
}
