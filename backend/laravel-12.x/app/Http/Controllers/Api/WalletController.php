<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use App\Services\CryptoSwapService;
use App\Services\NairaExchangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WalletController extends Controller
{
    protected $walletService;
    protected $swapService;
    protected $nairaService;

    public function __construct(
        WalletService $walletService,
        CryptoSwapService $swapService,
        NairaExchangeService $nairaService
    ) {
        $this->walletService = $walletService;
        $this->swapService = $swapService;
        $this->nairaService = $nairaService;
    }

    public function getUserWallets(Request $request)
    {
        $user = $request->user();
        $wallets = $user->wallets()->with('transactions')->get();

        $walletsData = $wallets->map(function ($wallet) {
            return [
                'id' => $wallet->id,
                'wallet_id' => $wallet->wallet_id,
                'currency' => $wallet->currency,
                'address' => $wallet->address,
                'balance' => $wallet->balance,
                'balance_naira' => $wallet->balance_in_naira,
                'formatted_balance' => $wallet->formatted_balance,
                'is_active' => $wallet->is_active,
                'recent_transactions' => $wallet->transactions()->latest()->limit(5)->get(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $walletsData
        ]);
    }

    public function sendCrypto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'wallet_id' => 'required|exists:wallets,id',
            'to_address' => 'required|string',
            'amount' => 'required|numeric|min:0.00000001',
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
            $wallet = $user->wallets()->findOrFail($request->wallet_id);
            
            $transaction = $this->walletService->sendCrypto(
                $wallet,
                $request->to_address,
                $request->amount,
                $request->fee ?? 0
            );

            return response()->json([
                'success' => true,
                'message' => 'Transaction sent successfully',
                'data' => $transaction
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function swapCrypto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_currency' => 'required|string|in:BTC,ETH,USDT,BNB',
            'to_currency' => 'required|string|in:BTC,ETH,USDT,BNB',
            'amount' => 'required|numeric|min:0.00000001',
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
            
            $swap = $this->swapService->executeSwap(
                $user,
                $request->from_currency,
                $request->to_currency,
                $request->amount
            );

            return response()->json([
                'success' => true,
                'message' => 'Swap completed successfully',
                'data' => $swap
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getSwapQuote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_currency' => 'required|string|in:BTC,ETH,USDT,BNB',
            'to_currency' => 'required|string|in:BTC,ETH,USDT,BNB',
            'amount' => 'required|numeric|min:0.00000001',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $quote = $this->swapService->getSwapQuote(
                $request->from_currency,
                $request->to_currency,
                $request->amount
            );

            return response()->json([
                'success' => true,
                'data' => $quote
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function buyCrypto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|string|in:BTC,ETH,USDT,BNB',
            'naira_amount' => 'required|numeric|min:1000', // Minimum ₦1000
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
            
            $result = $this->nairaService->buyCryptoWithNaira(
                $user,
                $request->currency,
                $request->naira_amount
            );

            return response()->json([
                'success' => true,
                'message' => 'Crypto purchased successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function sellCrypto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency' => 'required|string|in:BTC,ETH,USDT,BNB',
            'crypto_amount' => 'required|numeric|min:0.00000001',
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
            
            $result = $this->nairaService->sellCryptoForNaira(
                $user,
                $request->currency,
                $request->crypto_amount
            );

            return response()->json([
                'success' => true,
                'message' => 'Crypto sold successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getLiveRates()
    {
        $rates = $this->nairaService->getLiveRates();

        return response()->json([
            'success' => true,
            'data' => $rates
        ]);
    }

    public function calculateConversion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_currency' => 'required|string',
            'to_currency' => 'required|string',
            'amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->nairaService->calculateConversion(
                $request->from_currency,
                $request->to_currency,
                $request->amount
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'from_currency' => $request->from_currency,
                    'to_currency' => $request->to_currency,
                    'from_amount' => $request->amount,
                    'to_amount' => $result,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getWalletTransactions(Request $request, $walletId)
    {
        $user = $request->user();
        $wallet = $user->wallets()->findOrFail($walletId);
        
        $transactions = $wallet->transactions()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
}
