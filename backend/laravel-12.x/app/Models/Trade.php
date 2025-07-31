<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'trade_id',
        'user_id',
        'pair',
        'side',
        'type',
        'amount',
        'price',
        'total',
        'fee',
        'status',
        'executed_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'price' => 'decimal:8',
        'total' => 'decimal:8',
        'fee' => 'decimal:8',
        'executed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($trade) {
            if (empty($trade->trade_id)) {
                $trade->trade_id = 'TRD_' . Str::upper(Str::random(10));
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

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 8);
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 8);
    }

    public function getFormattedTotalAttribute()
    {
        return number_format($this->total, 2);
    }

    public function getSideColorAttribute()
    {
        return $this->side === 'buy' ? 'green' : 'red';
    }

    public function getStatusBadgeAttribute()
    {
        $colors = [
            'pending' => 'yellow',
            'completed' => 'green',
            'cancelled' => 'red',
            'failed' => 'red',
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
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
