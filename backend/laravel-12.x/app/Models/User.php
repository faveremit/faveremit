<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'balance_usd',
        'is_verified',
        'two_factor_enabled',
        'api_key',
        'api_secret',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'api_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'balance_usd' => 'decimal:8',
        'is_verified' => 'boolean',
        'two_factor_enabled' => 'boolean',
    ];

    public function portfolio()
    {
        return $this->hasOne(Portfolio::class);
    }

    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function priceAlerts()
    {
        return $this->hasMany(PriceAlert::class);
    }

    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance_usd, 2);
    }

    public function hasEnoughBalance($amount)
    {
        return $this->balance_usd >= $amount;
    }

    public function updateBalance($amount, $type = 'add')
    {
        if ($type === 'add') {
            $this->increment('balance_usd', $amount);
        } else {
            $this->decrement('balance_usd', $amount);
        }
    }
}
