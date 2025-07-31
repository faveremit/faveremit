<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'currency',
        'address',
        'private_key_encrypted',
        'public_key',
        'balance',
        'is_active',
        'mpc_key_share_1',
        'mpc_key_share_2',
        'mpc_key_share_3',
    ];

    protected $hidden = [
        'private_key_encrypted',
        'mpc_key_share_1',
        'mpc_key_share_2',
        'mpc_key_share_3',
    ];

    protected $casts = [
        'balance' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($wallet) {
            if (empty($wallet->wallet_id)) {
                $wallet->wallet_id = 'WALLET_' . Str::upper(Str::random(12));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 8) . ' ' . $this->currency;
    }

    public function getBalanceInNairaAttribute()
    {
        // Get current exchange rate and convert to Naira
        $exchangeRate = $this->getCurrentExchangeRate();
        return $this->balance * $exchangeRate;
    }

    private function getCurrentExchangeRate()
    {
        // This would fetch real exchange rates from your provider
        $rates = [
            'BTC' => 65000000, // 65M Naira per BTC
            'ETH' => 4200000,  // 4.2M Naira per ETH
            'USDT' => 1650,    // 1650 Naira per USDT
            'BNB' => 520000,   // 520K Naira per BNB
        ];

        return $rates[$this->currency] ?? 0;
    }
}
