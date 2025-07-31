<?php

namespace App\Services;

use App\Models\Portfolio;
use App\Models\CryptoPair;
use App\Models\Trade;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PortfolioService
{
    public function updatePortfolioValues($portfolio)
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

    public function getPortfolioHistory($user, $days = 30)
    {
        $startDate = Carbon::now()->subDays($days);
        
        // Get daily portfolio snapshots
        $history = DB::table('portfolio_snapshots')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at')
            ->get();

        // If no snapshots exist, create them from trade history
        if ($history->isEmpty()) {
            $history = $this->generatePortfolioHistoryFromTrades($user, $days);
        }

        return $history;
    }

    public function getPortfolioStats($user)
    {
        $portfolio = $user->portfolio;
        
        // Get trading stats
        $totalTrades = Trade::where('user_id', $user->id)->completed()->count();
        $winningTrades = Trade::where('user_id', $user->id)
            ->completed()
            ->whereRaw('(CASE WHEN side = "buy" THEN price < (SELECT current_price FROM crypto_pairs WHERE symbol = trades.pair LIMIT 1) ELSE price > (SELECT current_price FROM crypto_pairs WHERE symbol = trades.pair LIMIT 1) END)')
            ->count();
        
        $winRate = $totalTrades > 0 ? ($winningTrades / $totalTrades) * 100 : 0;
        
        // Get monthly performance
        $monthlyTrades = Trade::where('user_id', $user->id)
            ->completed()
            ->where('executed_at', '>=', Carbon::now()->startOfMonth())
            ->sum('total');
        
        // Get best performing asset
        $bestAsset = $portfolio->assets()
            ->orderBy('pnl_percentage', 'desc')
            ->first();
        
        // Get worst performing asset
        $worstAsset = $portfolio->assets()
            ->orderBy('pnl_percentage', 'asc')
            ->first();

        return [
            'total_trades' => $totalTrades,
            'winning_trades' => $winningTrades,
            'win_rate' => round($winRate, 2),
            'monthly_volume' => $monthlyTrades,
            'best_performing_asset' => $bestAsset,
            'worst_performing_asset' => $worstAsset,
            'total_fees_paid' => Transaction::where('user_id', $user->id)
                ->byType('fee')
                ->sum('amount'),
        ];
    }

    private function generatePortfolioHistoryFromTrades($user, $days)
    {
        // This would generate historical portfolio values based on trade history
        // For now, return empty array - implement based on your needs
        return collect([]);
    }
}
