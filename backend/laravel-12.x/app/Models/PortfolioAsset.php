<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortfolioAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'portfolio_id',
        'symbol',
        'amount',
        'average_buy_price',
        'current_value_usd',
        'invested_amount',
        'pnl',
        'pnl_percentage',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'average_buy_price' => 'decimal:8',
        'current_value_usd' => 'decimal:8',
        'invested_amount' => 'decimal:8',
        'pnl' => 'decimal:8',
        'pnl_percentage' => 'decimal:4',
    ];

    public function portfolio()
    {
        return $this->belongsTo(Portfolio::class);
    }

    public function updateCurrentValue()
    {
        $cryptoPair = CryptoPair::where('base_currency', $this->symbol)->first();
        if ($cryptoPair) {
            $currentValue = $this->amount * $cryptoPair->current_price;
            $pnl = $currentValue - $this->invested_amount;
            $pnlPercentage = $this->invested_amount > 0 ? ($pnl / $this->invested_amount) * 100 : 0;

            $this->update([
                'current_value_usd' => $currentValue,
                'pnl' => $pnl,
                'pnl_percentage' => $pnlPercentage,
            ]);
        }
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 8);
    }

    public function getFormattedValueAttribute()
    {
        return number_format($this->current_value_usd, 2);
    }

    public function getPnlColorAttribute()
    {
        return $this->pnl >= 0 ? 'green' : 'red';
    }
}
