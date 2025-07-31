<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\BankAccount;
use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class NairaExchangeService
{
    private $exchangeRates = [
        'BTC' => 65000000, // 65M Naira per BTC
        'ETH' => 4200000,  // 4.2M Naira per ETH
        'USDT' => 1650,    // 1650 Naira per USDT
        'BNB' => 520000,   // 520K Naira per BNB
    ];

    public function buyCryptoWithNaira(User $user, string $currency, float $nairaAmount)
    {
        DB::beginTransaction();

        try {
            // Check if user has enough Naira balance
            if ($user->balance_naira < $nairaAmount) {
                throw new \Exception('Insufficient Naira balance');
            }

            // Get exchange rate
            $exchangeRate = $this->getExchangeRate($currency);
            $cryptoAmount = $nairaAmount / $exchangeRate;
            $fee = $nairaAmount * 0.015; // 1.5% fee
            $netNairaAmount = $nairaAmount + $fee;

            // Get user's crypto wallet
            $wallet = $user->wallets()->where('currency', $currency)->first();
            if (!$wallet) {
                throw new \Exception('Crypto wallet not found');
            }

            // Deduct Naira balance
            $user->decrement('balance_naira', $netNairaAmount);

            // Add crypto to wallet
            $wallet->increment('balance', $cryptoAmount);

            // Create transaction record
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'crypto_purchase',
                'currency' => 'NGN',
                'amount' => -$netNairaAmount,
                'balance_before' => $user->getOriginal('balance_naira'),
                'balance_after' => $user->balance_naira,
                'status' => 'completed',
                'description' => "Bought {$cryptoAmount} {$currency} with ₦{$nairaAmount}",
                'metadata' => [
                    'crypto_currency' => $currency,
                    'crypto_amount' => $cryptoAmount,
                    'exchange_rate' => $exchangeRate,
                    'fee' => $fee,
                ],
            ]);

            DB::commit();
            return [
                'success' => true,
                'crypto_amount' => $cryptoAmount,
                'naira_spent' => $netNairaAmount,
                'fee' => $fee,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function sellCryptoForNaira(User $user, string $currency, float $cryptoAmount)
    {
        DB::beginTransaction();

        try {
            // Get user's crypto wallet
            $wallet = $user->wallets()->where('currency', $currency)->first();
            if (!$wallet || $wallet->balance < $cryptoAmount) {
                throw new \Exception('Insufficient crypto balance');
            }

            // Get exchange rate
            $exchangeRate = $this->getExchangeRate($currency);
            $nairaAmount = $cryptoAmount * $exchangeRate;
            $fee = $nairaAmount * 0.015; // 1.5% fee
            $netNairaAmount = $nairaAmount - $fee;

            // Deduct crypto from wallet
            $wallet->decrement('balance', $cryptoAmount);

            // Add Naira to user balance
            $user->increment('balance_naira', $netNairaAmount);

            // Create transaction record
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'crypto_sale',
                'currency' => 'NGN',
                'amount' => $netNairaAmount,
                'balance_before' => $user->getOriginal('balance_naira'),
                'balance_after' => $user->balance_naira,
                'status' => 'completed',
                'description' => "Sold {$cryptoAmount} {$currency} for ₦{$nairaAmount}",
                'metadata' => [
                    'crypto_currency' => $currency,
                    'crypto_amount' => $cryptoAmount,
                    'exchange_rate' => $exchangeRate,
                    'fee' => $fee,
                ],
            ]);

            DB::commit();
            return [
                'success' => true,
                'naira_received' => $netNairaAmount,
                'crypto_sold' => $cryptoAmount,
                'fee' => $fee,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function withdrawToBank(User $user, BankAccount $bankAccount, float $amount)
    {
        DB::beginTransaction();

        try {
            // Check balance
            if ($user->balance_naira < $amount) {
                throw new \Exception('Insufficient Naira balance');
            }

            $fee = max(100, $amount * 0.01); // Minimum ₦100 or 1% fee
            $netAmount = $amount - $fee;

            // Create withdrawal record
            $withdrawal = Withdrawal::create([
                'user_id' => $user->id,
                'bank_account_id' => $bankAccount->id,
                'amount_naira' => $amount,
                'fee' => $fee,
                'status' => 'pending',
            ]);

            // Deduct from user balance
            $user->decrement('balance_naira', $amount);

            // Process withdrawal (integrate with payment provider)
            $this->processWithdrawal($withdrawal);

            DB::commit();
            return $withdrawal;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function processWithdrawal(Withdrawal $withdrawal)
    {
        // Integrate with Nigerian payment providers like Paystack, Flutterwave, etc.
        // For now, simulate processing
        $withdrawal->update([
            'status' => 'processing',
            'reference' => 'REF_' . time(),
        ]);

        // In production, make API call to payment provider
        // $response = Http::post('https://api.paystack.co/transfer', [...]);
    }

    private function getExchangeRate(string $currency)
    {
        // In production, fetch real-time rates from multiple sources
        return $this->exchangeRates[$currency] ?? 0;
    }

    public function getLiveRates()
    {
        // Return current exchange rates
        return $this->exchangeRates;
    }

    public function calculateConversion(string $fromCurrency, string $toCurrency, float $amount)
    {
        if ($fromCurrency === 'NGN') {
            // Naira to Crypto
            $rate = $this->getExchangeRate($toCurrency);
            return $amount / $rate;
        } elseif ($toCurrency === 'NGN') {
            // Crypto to Naira
            $rate = $this->getExchangeRate($fromCurrency);
            return $amount * $rate;
        } else {
            // Crypto to Crypto
            $fromRate = $this->getExchangeRate($fromCurrency);
            $toRate = $this->getExchangeRate($toCurrency);
            return ($amount * $fromRate) / $toRate;
        }
    }
}
