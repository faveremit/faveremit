<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\CryptoSwap;
use App\Models\CryptoPair;
use Illuminate\Support\Facades\DB;

class CryptoSwapService
{
    public function executeSwap(User $user, string $fromCurrency, string $toCurrency, float $amount)
    {
        DB::beginTransaction();

        try {
            // Get user wallets
            $fromWallet = $user->wallets()->where('currency', $fromCurrency)->first();
            $toWallet = $user->wallets()->where('currency', $toCurrency)->first();

            if (!$fromWallet || !$toWallet) {
                throw new \Exception('Wallet not found');
            }

            // Check balance
            if ($fromWallet->balance < $amount) {
                throw new \Exception('Insufficient balance');
            }

            // Get exchange rate
            $exchangeRate = $this->getExchangeRate($fromCurrency, $toCurrency);
            $toAmount = $amount * $exchangeRate;
            $fee = $toAmount * 0.005; // 0.5% swap fee
            $netToAmount = $toAmount - $fee;

            // Create swap record
            $swap = CryptoSwap::create([
                'user_id' => $user->id,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'from_amount' => $amount,
                'to_amount' => $netToAmount,
                'exchange_rate' => $exchangeRate,
                'fee' => $fee,
                'status' => 'completed',
                'executed_at' => now(),
                'from_wallet_id' => $fromWallet->id,
                'to_wallet_id' => $toWallet->id,
            ]);

            // Update wallet balances
            $fromWallet->decrement('balance', $amount);
            $toWallet->increment('balance', $netToAmount);

            // Create transaction records
            $this->createSwapTransactions($swap);

            DB::commit();
            return $swap;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function getExchangeRate(string $fromCurrency, string $toCurrency)
    {
        // Get current prices from your price feed
        $fromPair = CryptoPair::where('base_currency', $fromCurrency)->first();
        $toPair = CryptoPair::where('base_currency', $toCurrency)->first();

        if (!$fromPair || !$toPair) {
            throw new \Exception('Exchange rate not available');
        }

        // Calculate cross rate
        return $fromPair->current_price / $toPair->current_price;
    }

    private function createSwapTransactions(CryptoSwap $swap)
    {
        // Create outgoing transaction
        WalletTransaction::create([
            'wallet_id' => $swap->from_wallet_id,
            'user_id' => $swap->user_id,
            'type' => 'swap_out',
            'currency' => $swap->from_currency,
            'amount' => $swap->from_amount,
            'fee' => 0,
            'from_address' => $swap->fromWallet->address,
            'to_address' => 'SWAP_' . $swap->swap_id,
            'status' => 'confirmed',
        ]);

        // Create incoming transaction
        WalletTransaction::create([
            'wallet_id' => $swap->to_wallet_id,
            'user_id' => $swap->user_id,
            'type' => 'swap_in',
            'currency' => $swap->to_currency,
            'amount' => $swap->to_amount,
            'fee' => $swap->fee,
            'from_address' => 'SWAP_' . $swap->swap_id,
            'to_address' => $swap->toWallet->address,
            'status' => 'confirmed',
        ]);
    }

    public function getSwapQuote(string $fromCurrency, string $toCurrency, float $amount)
    {
        $exchangeRate = $this->getExchangeRate($fromCurrency, $toCurrency);
        $toAmount = $amount * $exchangeRate;
        $fee = $toAmount * 0.005; // 0.5% swap fee
        $netToAmount = $toAmount - $fee;

        return [
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'from_amount' => $amount,
            'to_amount' => $netToAmount,
            'exchange_rate' => $exchangeRate,
            'fee' => $fee,
            'fee_percentage' => 0.5,
        ];
    }
}
