<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use App\Models\Transaction;
use App\Services\PortfolioService;
use Illuminate\Http\Request;

class PortfolioController extends Controller
{
    protected $portfolioService;

    public function __construct(PortfolioService $portfolioService)
    {
        $this->portfolioService = $portfolioService;
    }

    public function getPortfolio(Request $request)
    {
        $user = $request->user();
        $portfolio = $user->portfolio()->with('assets')->first();

        if (!$portfolio) {
            // Create portfolio if it doesn't exist
            $portfolio = Portfolio::create([
                'user_id' => $user->id,
                'available_balance' => $user->balance_usd,
            ]);
        }

        // Update portfolio values
        $this->portfolioService->updatePortfolioValues($portfolio);

        return response()->json([
            'success' => true,
            'data' => [
                'portfolio' => $portfolio->fresh('assets'),
                'user_balance' => $user->balance_usd,
            ]
        ]);
    }

    public function getPortfolioHistory(Request $request)
    {
        $user = $request->user();
        $days = $request->get('days', 30);

        $history = $this->portfolioService->getPortfolioHistory($user, $days);

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    public function getTransactions(Request $request)
    {
        $transactions = Transaction::where('user_id', $request->user()->id)
            ->recent()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function getTransactionsByType(Request $request, $type)
    {
        $validTypes = ['deposit', 'withdrawal', 'trade', 'fee', 'bonus'];
        
        if (!in_array($type, $validTypes)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid transaction type'
            ], 400);
        }

        $transactions = Transaction::where('user_id', $request->user()->id)
            ->byType($type)
            ->recent()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    public function getPortfolioStats(Request $request)
    {
        $user = $request->user();
        $stats = $this->portfolioService->getPortfolioStats($user);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
