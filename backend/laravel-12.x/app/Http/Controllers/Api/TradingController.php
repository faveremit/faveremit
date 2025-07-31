<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CryptoPair;
use App\Models\Trade;
use App\Models\Order;
use App\Models\Portfolio;
use App\Models\PortfolioAsset;
use App\Models\Transaction;
use App\Services\TradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TradingController extends Controller
{
    protected $tradingService;

    public function __construct(TradingService $tradingService)
    {
        $this->tradingService = $tradingService;
    }

    public function getMarketData()
    {
        $pairs = CryptoPair::active()
            ->popular()
            ->get()
            ->map(function ($pair) {
                return [
                    'id' => $pair->id,
                    'symbol' => $pair->symbol,
                    'base_currency' => $pair->base_currency,
                    'quote_currency' => $pair->quote_currency,
                    'current_price' => $pair->current_price,
                    'price_change_24h' => $pair->price_change_24h,
                    'price_change_percentage_24h' => $pair->price_change_percentage_24h,
                    'high_24h' => $pair->high_24h,
                    'low_24h' => $pair->low_24h,
                    'volume_24h' => $pair->volume_24h,
                    'market_cap' => $pair->market_cap,
                    'last_updated' => $pair->last_updated,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $pairs
        ]);
    }

    public function getPairDetails($symbol)
    {
        $pair = CryptoPair::where('symbol', $symbol)->first();

        if (!$pair) {
            return response()->json([
                'success' => false,
                'message' => 'Crypto pair not found'
            ], 404);
        }

        // Get recent trades for this pair
        $recentTrades = Trade::where('pair', $symbol)
            ->completed()
            ->recent()
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'pair' => $pair,
                'recent_trades' => $recentTrades
            ]
        ]);
    }

    public function executeTrade(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pair' => 'required|string|exists:crypto_pairs,symbol',
            'side' => 'required|in:buy,sell',
            'type' => 'required|in:market,limit',
            'amount' => 'required|numeric|min:0.00000001',
            'price' => 'required_if:type,limit|nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = $request->user();
            $pair = CryptoPair::where('symbol', $request->pair)->first();
            
            // Calculate trade details
            $price = $request->type === 'market' ? $pair->current_price : $request->price;
            $total = $request->amount * $price;
            $fee = $total * 0.001; // 0.1% fee

            // Check balance
            if ($request->side === 'buy') {
                $requiredAmount = $total + $fee;
                if (!$user->hasEnoughBalance($requiredAmount)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient balance'
                    ], 400);
                }
            } else {
                // Check if user has enough crypto to sell
                $portfolio = $user->portfolio;
                $asset = $portfolio->assets()->where('symbol', $pair->base_currency)->first();
                
                if (!$asset || $asset->amount < $request->amount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient ' . $pair->base_currency . ' balance'
                    ], 400);
                }
            }

            // Execute trade
            $trade = $this->tradingService->executeTrade(
                $user,
                $request->pair,
                $request->side,
                $request->type,
                $request->amount,
                $price,
                $fee
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Trade executed successfully',
                'data' => $trade
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Trade execution failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function placeOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pair' => 'required|string|exists:crypto_pairs,symbol',
            'side' => 'required|in:buy,sell',
            'type' => 'required|in:limit,stop_loss,take_profit',
            'amount' => 'required|numeric|min:0.00000001',
            'price' => 'required|numeric|min:0',
            'stop_price' => 'required_if:type,stop_loss|nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            
            $order = Order::create([
                'user_id' => $user->id,
                'pair' => $request->pair,
                'side' => $request->side,
                'type' => $request->type,
                'amount' => $request->amount,
                'price' => $request->price,
                'stop_price' => $request->stop_price,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order placement failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelOrder(Request $request, $orderId)
    {
        $order = Order::where('order_id', $orderId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        }

        if (!in_array($order->status, ['pending', 'partially_filled'])) {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be cancelled'
            ], 400);
        }

        $order->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Order cancelled successfully',
            'data' => $order
        ]);
    }

    public function getUserTrades(Request $request)
    {
        $trades = Trade::where('user_id', $request->user()->id)
            ->with('cryptoPair')
            ->recent()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $trades
        ]);
    }

    public function getUserOrders(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with('cryptoPair')
            ->recent()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    public function getActiveOrders(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->active()
            ->with('cryptoPair')
            ->recent()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }
}
