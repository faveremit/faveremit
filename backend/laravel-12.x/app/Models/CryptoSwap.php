<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CryptoSwap extends Model
{
    use HasFactory;

    protected $fillable = [
        'swap_id',
        'user_id',
        'from_currency',
        'to_currency',
        'from_amount',
        'to_amount',
        'exchange_rate',
        'fee',
        'status',
        'executed_at',
        'from_wallet_id',
        'to_wallet_id',
        'metadata',
    ];

    protected $casts = [
        'from_amount' => 'decimal:8',
        'to_amount' => 'decimal:8',
        'exchange_rate' => 'decimal:8',
        'fee' => 'decimal:8',
        'executed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($swap) {
            if (empty($swap->swap_id)) {
                $swap->swap_id = 'SWAP_' . Str::upper(Str::random(12));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fromWallet()
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet()
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    public function getFormattedFromAmountAttribute()
    {
        return number_format($this->from_amount, 8) . ' ' . $this->from_currency;
    }

    public function getFormattedToAmountAttribute()
    {
        return number_format($this->to_amount, 8) . ' ' . $this->to_currency;
    }

    public function getStatusBadgeAttribute()
    {
        $colors = [
            'pending' => 'yellow',
            'completed' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }
}
