<?php

namespace App\Services;

use App\Models\CryptoPair;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ExchangeService
{
    private $binanceApiUrl = 'https://api.binance.com/api/v3';
    private $coinGeckoApiUrl = 'https://api.coingecko.com/api/v3';

    public function updateMarketData()
    {
        try {
            // Get data from Binance
            $binanceData = $this->getBinanceMarketData();
            
            // Get additional data from CoinGecko
            $coinGeckoData = $this->getCoinGeckoMarketData();
            
            // Merge and update database
            $this->updateCryptoPairs($binanceData, $coinGeckoData);
            
            Log::info('Market data updated successfully');
            
        } catch (\Exception $e) {
            Log::error('Failed to update market data: ' . $e->getMessage());
            throw $e;
        }
    }

    private function getBinanceMarketData()
    {
        $response = Http::get($this->binanceApiUrl . '/ticker/24hr');
        
        if (!$response->successful()) {
            throw new \Exception('Failed to fetch Binance market data');
        }
        
        return $response->json();
    }

    private function getCoinGeckoMarketData()
    {
        $response = Http::get($this->coinGeckoApiUrl . '/coins/markets', [
            'vs_currency' => 'usd',
            'order' => 'market_cap_desc',
            'per_page' => 100,
            'page' => 1,
            'sparkline' => false,
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to fetch CoinGecko market data');
        }
        
        return $response->json();
    }

    private function updateCryptoPairs($binanceData, $coinGeckoData)
    {
        // Create a map of CoinGecko data by symbol
        $coinGeckoMap = collect($coinGeckoData)->keyBy('symbol');
        
        foreach ($binanceData as $ticker) {
            // Only process USDT pairs
            if (!str_ends_with($ticker['symbol'], 'USDT')) {
                continue;
            }
            
            $symbol = $ticker['symbol'];
            $baseCurrency = str_replace('USDT', '', $symbol);
            $formattedSymbol = $baseCurrency . '/USDT';
            
            // Get additional data from CoinGecko if available
            $coinGeckoInfo = $coinGeckoMap->get(strtolower($baseCurrency));
            
            CryptoPair::updateOrCreate(
                ['symbol' => $formattedSymbol],
                [
                    'base_currency' => $baseCurrency,
                    'quote_currency' => 'USDT',
                    'current_price' => (float) $ticker['lastPrice'],
                    'price_change_24h' => (float) $ticker['priceChange'],
                    'price_change_percentage_24h' => (float) $ticker['priceChangePercent'],
                    'high_24h' => (float) $ticker['highPrice'],
                    'low_24h' => (float) $ticker['lowPrice'],
                    'volume_24h' => (float) $ticker['volume'],
                    'market_cap' => $coinGeckoInfo ? $coinGeckoInfo['market_cap'] : null,
                    'is_active' => true,
                    'last_updated' => Carbon::now(),
                ]
            );
        }
    }

    public function getOrderBook($symbol, $limit = 100)
    {
        $binanceSymbol = str_replace('/', '', $symbol);
        
        $response = Http::get($this->binanceApiUrl . '/depth', [
            'symbol' => $binanceSymbol,
            'limit' => $limit,
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to fetch order book data');
        }
        
        return $response->json();
    }

    public function getKlineData($symbol, $interval = '1h', $limit = 100)
    {
        $binanceSymbol = str_replace('/', '', $symbol);
        
        $response = Http::get($this->binanceApiUrl . '/klines', [
            'symbol' => $binanceSymbol,
            'interval' => $interval,
            'limit' => $limit,
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to fetch kline data');
        }
        
        return $response->json();
    }
}
