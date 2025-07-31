<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'pair',
        'side',
        'type',
        'amount',
        'price',
        'stop_price',
        'filled_amount',
        'status',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'price' => 'decimal:8',
        'stop_price' => 'decimal:8',
        'filled_amount' => 'decimal:8',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            if (empty($order->order_id)) {
                $order->order_id = 'ORD_' . Str::upper(Str::random(10));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cryptoPair()
    {
        return $this->belongsTo(CryptoPair::class, 'pair', 'symbol');
    }

    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->filled_amount;
    }

    public function getFilledPercentageAttribute()
    {
        return $this->amount > 0 ? ($this->filled_amount / $this->amount) * 100 : 0;
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 8);
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 8);
    }

    public function getSideColorAttribute()
    {
        return $this->side === 'buy' ? 'green' : 'red';
    }

    public function getStatusBadgeAttribute()
    {
        $colors = [
            'pending' => 'yellow',
            'partially_filled' => 'blue',
            'filled' => 'green',
            'cancelled' => 'red',
            'expired' => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['pending', 'partially_filled']);
    }

    public function scopeByPair($query, $pair)
    {
        return $query->where('pair', $pair);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
