<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'withdrawal_id',
        'user_id',
        'bank_account_id',
        'amount_naira',
        'amount_crypto',
        'currency',
        'exchange_rate',
        'fee',
        'status',
        'reference',
        'processed_at',
        'metadata',
    ];

    protected $casts = [
        'amount_naira' => 'decimal:2',
        'amount_crypto' => 'decimal:8',
        'exchange_rate' => 'decimal:2',
        'fee' => 'decimal:2',
        'processed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($withdrawal) {
            if (empty($withdrawal->withdrawal_id)) {
                $withdrawal->withdrawal_id = 'WD_' . Str::upper(Str::random(12));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function getFormattedAmountNairaAttribute()
    {
        return '₦' . number_format($this->amount_naira, 2);
    }

    public function getStatusBadgeAttribute()
    {
        $colors = [
            'pending' => 'yellow',
            'processing' => 'blue',
            'completed' => 'green',
            'failed' => 'red',
            'cancelled' => 'gray',
        ];

        return $colors[$this->status] ?? 'gray';
    }
}
