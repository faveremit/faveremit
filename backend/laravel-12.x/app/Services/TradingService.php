<?php

namespace App\Services;

use App\Models\Trade;
use App\Models\Transaction;
use App\Models\Portfolio;
use App\Models\PortfolioAsset;
use App\Models\CryptoPair;
use Illuminate\Support\Facades\DB;

class TradingService
{
    public function executeTrade($user, $pair, $side, $type, $amount, $price, $fee)
    {
        DB::beginTransaction();

        try {
            $cryptoPair = CryptoPair::where('symbol', $pair)->first();
            $total = $amount * $price;

            // Create trade record
            $trade = Trade::create([
                'user_id' => $user->id,
                'pair' => $pair,
                'side' => $side,
                'type' => $type,
                'amount' => $amount,
                'price' => $price,
                'total' => $total,
                'fee' => $fee,
                'status' => 'completed',
                'executed_at' => now(),
            ]);

            // Update user balance and portfolio
            $this->updateUserBalanceAndPortfolio($user, $trade, $cryptoPair);

            // Create transaction records
            $this->createTransactionRecords($user, $trade);

            DB::commit();
            return $trade;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function updateUserBalanceAndPortfolio($user, $trade, $cryptoPair)
    {
        $portfolio = $user->portfolio;
        
        if ($trade->side === 'buy') {
            // Deduct USD balance
            $totalCost = $trade->total + $trade->fee;
            $user->updateBalance($totalCost, 'subtract');
            $portfolio->decrement('available_balance', $totalCost);

            // Add crypto asset
            $asset = $portfolio->assets()->where('symbol', $cryptoPair->base_currency)->first();
            
            if ($asset) {
                // Update existing asset
                $newAmount = $asset->amount + $trade->amount;
                $newInvestedAmount = $asset->invested_amount + $trade->total;
                $newAverageBuyPrice = $newInvestedAmount / $newAmount;
                
                $asset->update([
                    'amount' => $newAmount,
                    'average_buy_price' => $newAverageBuyPrice,
                    'invested_amount' => $newInvestedAmount,
                ]);
            } else {
                // Create new asset
                PortfolioAsset::create([
                    'portfolio_id' => $portfolio->id,
                    'symbol' => $cryptoPair->base_currency,
                    'amount' => $trade->amount,
                    'average_buy_price' => $trade->price,
                    'invested_amount' => $trade->total,
                    'current_value_usd' => $trade->total,
                ]);
            }

        } else { // sell
            // Add USD balance
            $netAmount = $trade->total - $trade->fee;
            $user->updateBalance($netAmount, 'add');
            $portfolio->increment('available_balance', $netAmount);

            // Reduce crypto asset
            $asset = $portfolio->assets()->where('symbol', $cryptoPair->base_currency)->first();
            
            if ($asset) {
                $newAmount = $asset->amount - $trade->amount;
                $soldRatio = $trade->amount / $asset->amount;
                $soldInvestedAmount = $asset->invested_amount * $soldRatio;
                
                if ($newAmount <= 0) {
                    $asset->delete();
                } else {
                    $asset->update([
                        'amount' => $newAmount,
                        'invested_amount' => $asset->invested_amount - $soldInvestedAmount,
                    ]);
                }
            }
        }

        // Update portfolio totals
        $this->updatePortfolioTotals($portfolio);
    }

    private function createTransactionRecords($user, $trade)
    {
        $balanceBefore = $user->getOriginal('balance_usd');
        $balanceAfter = $user->balance_usd;

        // Trade transaction
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'trade',
            'currency' => 'USD',
            'amount' => $trade->side === 'buy' ? -$trade->total : $trade->total,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'status' => 'completed',
            'description' => ucfirst($trade->side) . ' ' . $trade->amount . ' ' . explode('/', $trade->pair)[0],
            'metadata' => [
                'trade_id' => $trade->trade_id,
                'pair' => $trade->pair,
                'side' => $trade->side,
                'amount' => $trade->amount,
                'price' => $trade->price,
            ],
        ]);

        // Fee transaction
        if ($trade->fee > 0) {
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'fee',
                'currency' => 'USD',
                'amount' => -$trade->fee,
                'balance_before' => $balanceAfter,
                'balance_after' => $balanceAfter - $trade->fee,
                'status' => 'completed',
                'description' => 'Trading fee for ' . $trade->pair,
                'metadata' => [
                    'trade_id' => $trade->trade_id,
                ],
            ]);
        }
    }

    private function updatePortfolioTotals($portfolio)
    {
        $assets = $portfolio->assets;
        $totalValue = 0;
        $totalInvested = 0;

        foreach ($assets as $asset) {
            $cryptoPair = CryptoPair::where('base_currency', $asset->symbol)->first();
            if ($cryptoPair) {
                $currentValue = $asset->amount * $cryptoPair->current_price;
                $pnl = $currentValue - $asset->invested_amount;
                $pnlPercentage = $asset->invested_amount > 0 ? ($pnl / $asset->invested_amount) * 100 : 0;

                $asset->update([
                    'current_value_usd' => $currentValue,
                    'pnl' => $pnl,
                    'pnl_percentage' => $pnlPercentage,
                ]);

                $totalValue += $currentValue;
                $totalInvested += $asset->invested_amount;
            }
        }

        $totalPnl = $totalValue - $totalInvested;
        $totalPnlPercentage = $totalInvested > 0 ? ($totalPnl / $totalInvested) * 100 : 0;

        $portfolio->update([
            'total_value_usd' => $totalValue,
            'total_invested' => $totalInvested,
            'total_pnl' => $totalPnl,
            'total_pnl_percentage' => $totalPnlPercentage,
        ]);
    }
}
