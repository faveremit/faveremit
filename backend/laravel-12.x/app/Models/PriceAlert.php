<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'symbol',
        'condition',
        'target_price',
        'is_active',
        'is_triggered',
        'triggered_at',
    ];

    protected $casts = [
        'target_price' => 'decimal:8',
        'is_active' => 'boolean',
        'is_triggered' => 'boolean',
        'triggered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cryptoPair()
    {
        return $this->belongsTo(CryptoPair::class, 'symbol', 'base_currency');
    }

    public function getFormattedTargetPriceAttribute()
    {
        return number_format($this->target_price, 8);
    }

    public function getConditionTextAttribute()
    {
        return $this->condition === 'above' ? 'Above' : 'Below';
    }

    public function checkCondition($currentPrice)
    {
        if ($this->condition === 'above') {
            return $currentPrice >= $this->target_price;
        } else {
            return $currentPrice <= $this->target_price;
        }
    }

    public function trigger()
    {
        $this->update([
            'is_triggered' => true,
            'is_active' => false,
            'triggered_at' => now(),
        ]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_triggered', false);
    }

    public function scopeBySymbol($query, $symbol)
    {
        return $query->where('symbol', $symbol);
    }
}
