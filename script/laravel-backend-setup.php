<?php
// Laravel Backend API Structure for Crypto Trading Platform

// 1. Models
// app/Models/User.php
class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password', 'api_key', 'api_secret'
    ];

    public function portfolio()
    {
        return $this->hasOne(Portfolio::class);
    }

    public function trades()
    {
        return $this->hasMany(Trade::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

// app/Models/Portfolio.php
class Portfolio extends Model
{
    protected $fillable = [
        'user_id', 'total_value', 'total_pnl', 'total_pnl_percent'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assets()
    {
        return $this->hasMany(PortfolioAsset::class);
    }
}

// app/Models/PortfolioAsset.php
class PortfolioAsset extends Model
{
    protected $fillable = [
        'portfolio_id', 'symbol', 'amount', 'value', 'pnl', 'pnl_percent'
    ];

    public function portfolio()
    {
        return $this->belongsTo(Portfolio::class);
    }
}

// app/Models/Trade.php
class Trade extends Model
{
    protected $fillable = [
        'user_id', 'pair', 'type', 'amount', 'price', 'total', 'status', 'executed_at'
    ];

    protected $casts = [
        'executed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// app/Models/Order.php
class Order extends Model
{
    protected $fillable = [
        'user_id', 'pair', 'type', 'order_type', 'amount', 'price', 'status', 'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// app/Models/CryptoPair.php
class CryptoPair extends Model
{
    protected $fillable = [
        'symbol', 'price', 'change_24h', 'volume', 'high_24h', 'low_24h', 'last_updated'
    ];

    protected $casts = [
        'last_updated' => 'datetime'
    ];
}

// 2. Controllers
// app/Http/Controllers/Api/TradingController.php
class TradingController extends Controller
{
    public function getMarketData()
    {
        $pairs = CryptoPair::all();
        return response()->json($pairs);
    }

    public function executeTrade(Request $request)
    {
        $request->validate([
            'pair' => 'required|string',
            'type' => 'required|in:buy,sell',
            'order_type' => 'required|in:market,limit',
            'amount' => 'required|numeric|min:0',
            'price' => 'nullable|numeric|min:0'
        ]);

        $user = auth()->user();
        
        // Check balance
        if (!$this->hasSufficientBalance($user, $request)) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }

        // Execute trade logic
        $trade = Trade::create([
            'user_id' => $user->id,
            'pair' => $request->pair,
            'type' => $request->type,
            'amount' => $request->amount,
            'price' => $request->price ?? $this->getCurrentPrice($request->pair),
            'total' => $request->amount * ($request->price ?? $this->getCurrentPrice($request->pair)),
            'status' => $request->order_type === 'market' ? 'completed' : 'pending',
            'executed_at' => $request->order_type === 'market' ? now() : null
        ]);

        // Update portfolio
        $this->updatePortfolio($user, $trade);

        return response()->json($trade);
    }

    public function getPortfolio()
    {
        $user = auth()->user();
        $portfolio = $user->portfolio()->with('assets')->first();
        
        return response()->json($portfolio);
    }

    public function getTrades()
    {
        $trades = auth()->user()->trades()->latest()->paginate(20);
        return response()->json($trades);
    }

    public function getOrders()
    {
        $orders = auth()->user()->orders()->where('status', 'pending')->get();
        return response()->json($orders);
    }

    private function hasSufficientBalance($user, $request)
    {
        // Implement balance checking logic
        return true;
    }

    private function getCurrentPrice($pair)
    {
        $cryptoPair = CryptoPair::where('symbol', $pair)->first();
        return $cryptoPair ? $cryptoPair->price : 0;
    }

    private function updatePortfolio($user, $trade)
    {
        // Implement portfolio update logic
    }
}

// 3. Routes
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/market-data', [TradingController::class, 'getMarketData']);
    Route::post('/trade', [TradingController::class, 'executeTrade']);
    Route::get('/portfolio', [TradingController::class, 'getPortfolio']);
    Route::get('/trades', [TradingController::class, 'getTrades']);
    Route::get('/orders', [TradingController::class, 'getOrders']);
});

// 4. Migrations
// database/migrations/create_portfolios_table.php
Schema::create('portfolios', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->decimal('total_value', 20, 8)->default(0);
    $table->decimal('total_pnl', 20, 8)->default(0);
    $table->decimal('total_pnl_percent', 8, 4)->default(0);
    $table->timestamps();
});

// database/migrations/create_portfolio_assets_table.php
Schema::create('portfolio_assets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('portfolio_id')->constrained()->onDelete('cascade');
    $table->string('symbol');
    $table->decimal('amount', 20, 8);
    $table->decimal('value', 20, 8);
    $table->decimal('pnl', 20, 8)->default(0);
    $table->decimal('pnl_percent', 8, 4)->default(0);
    $table->timestamps();
});

// database/migrations/create_trades_table.php
Schema::create('trades', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('pair');
    $table->enum('type', ['buy', 'sell']);
    $table->decimal('amount', 20, 8);
    $table->decimal('price', 20, 8);
    $table->decimal('total', 20, 8);
    $table->enum('status', ['completed', 'pending', 'cancelled'])->default('pending');
    $table->timestamp('executed_at')->nullable();
    $table->timestamps();
});

// database/migrations/create_orders_table.php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('pair');
    $table->enum('type', ['buy', 'sell']);
    $table->enum('order_type', ['market', 'limit']);
    $table->decimal('amount', 20, 8);
    $table->decimal('price', 20, 8)->nullable();
    $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
});

// database/migrations/create_crypto_pairs_table.php
Schema::create('crypto_pairs', function (Blueprint $table) {
    $table->id();
    $table->string('symbol')->unique();
    $table->decimal('price', 20, 8);
    $table->decimal('change_24h', 8, 4);
    $table->decimal('volume', 20, 8);
    $table->decimal('high_24h', 20, 8);
    $table->decimal('low_24h', 20, 8);
    $table->timestamp('last_updated');
    $table->timestamps();
});

// 5. Services
// app/Services/ExchangeService.php
class ExchangeService
{
    public function fetchMarketData()
    {
        // Integrate with external APIs like Binance, CoinGecko, etc.
        // Update crypto_pairs table with real-time data
    }

    public function executeMarketOrder($pair, $type, $amount)
    {
        // Execute market order through exchange API
    }

    public function placeLimitOrder($pair, $type, $amount, $price)
    {
        // Place limit order through exchange API
    }
}

// 6. Jobs
// app/Jobs/UpdateMarketDataJob.php
class UpdateMarketDataJob implements ShouldQueue
{
    public function handle(ExchangeService $exchangeService)
    {
        $exchangeService->fetchMarketData();
    }
}

// 7. Middleware
// app/Http/Middleware/RateLimitTrading.php
class RateLimitTrading
{
    public function handle($request, Closure $next)
    {
        // Implement rate limiting for trading endpoints
        return $next($request);
    }
}
?>
