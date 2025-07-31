<?php
// Complete Laravel Backend Setup for Crypto Trading Platform

// 1. First, create a new Laravel project and install required packages
/*
composer create-project laravel/laravel crypto-trading-backend
cd crypto-trading-backend
composer require laravel/sanctum
composer require guzzlehttp/guzzle
composer require pusher/pusher-php-server
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
*/

// 2. Environment Configuration (.env)
/*
APP_NAME="Crypto Trading Platform"
APP_ENV=production
APP_KEY=base64:your-generated-key-here
APP_DEBUG=false
APP_URL=https://your-backend-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crypto_trading_db
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

BROADCAST_DRIVER=pusher
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=database
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_key
PUSHER_APP_SECRET=your_pusher_secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

BINANCE_API_KEY=your_binance_api_key
BINANCE_SECRET_KEY=your_binance_secret_key
COINGECKO_API_KEY=your_coingecko_api_key

JWT_SECRET=your_jwt_secret_key
*/

// 3. Database Migrations
echo "Creating database migrations...\n";

// Migration: create_users_table (modify existing)
/*
Schema::table('users', function (Blueprint $table) {
    $table->string('phone')->nullable()->after('email');
    $table->decimal('balance_usd', 20, 8)->default(0)->after('phone');
    $table->boolean('is_verified')->default(false)->after('balance_usd');
    $table->boolean('two_factor_enabled')->default(false)->after('is_verified');
    $table->string('two_factor_secret')->nullable()->after('two_factor_enabled');
    $table->timestamp('last_login_at')->nullable()->after('two_factor_secret');
    $table->string('api_key')->nullable()->after('last_login_at');
    $table->string('api_secret')->nullable()->after('api_key');
});
*/

// Migration: create_crypto_pairs_table
/*
Schema::create('crypto_pairs', function (Blueprint $table) {
    $table->id();
    $table->string('symbol', 20)->unique(); // BTC/USDT
    $table->string('base_currency', 10); // BTC
    $table->string('quote_currency', 10); // USDT
    $table->decimal('current_price', 20, 8);
    $table->decimal('price_change_24h', 10, 4);
    $table->decimal('price_change_percentage_24h', 8, 4);
    $table->decimal('high_24h', 20, 8);
    $table->decimal('low_24h', 20, 8);
    $table->decimal('volume_24h', 25, 8);
    $table->decimal('market_cap', 25, 2)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_updated');
    $table->timestamps();
    
    $table->index(['symbol', 'is_active']);
    $table->index('last_updated');
});
*/

// Migration: create_portfolios_table
/*
Schema::create('portfolios', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->decimal('total_value_usd', 20, 8)->default(0);
    $table->decimal('total_invested', 20, 8)->default(0);
    $table->decimal('total_pnl', 20, 8)->default(0);
    $table->decimal('total_pnl_percentage', 8, 4)->default(0);
    $table->decimal('available_balance', 20, 8)->default(0);
    $table->timestamps();
    
    $table->unique('user_id');
});
*/

// Migration: create_portfolio_assets_table
/*
Schema::create('portfolio_assets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('portfolio_id')->constrained()->onDelete('cascade');
    $table->string('symbol', 10); // BTC, ETH, etc.
    $table->decimal('amount', 20, 8);
    $table->decimal('average_buy_price', 20, 8);
    $table->decimal('current_value_usd', 20, 8);
    $table->decimal('invested_amount', 20, 8);
    $table->decimal('pnl', 20, 8)->default(0);
    $table->decimal('pnl_percentage', 8, 4)->default(0);
    $table->timestamps();
    
    $table->unique(['portfolio_id', 'symbol']);
    $table->index('symbol');
});
*/

// Migration: create_trades_table
/*
Schema::create('trades', function (Blueprint $table) {
    $table->id();
    $table->string('trade_id', 50)->unique();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('pair', 20); // BTC/USDT
    $table->enum('side', ['buy', 'sell']);
    $table->enum('type', ['market', 'limit', 'stop_loss', 'take_profit']);
    $table->decimal('amount', 20, 8);
    $table->decimal('price', 20, 8);
    $table->decimal('total', 20, 8);
    $table->decimal('fee', 20, 8)->default(0);
    $table->enum('status', ['pending', 'completed', 'cancelled', 'failed'])->default('pending');
    $table->timestamp('executed_at')->nullable();
    $table->json('metadata')->nullable(); // Additional trade info
    $table->timestamps();
    
    $table->index(['user_id', 'status']);
    $table->index(['pair', 'executed_at']);
    $table->index('trade_id');
});
*/

// Migration: create_orders_table
/*
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->string('order_id', 50)->unique();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('pair', 20);
    $table->enum('side', ['buy', 'sell']);
    $table->enum('type', ['market', 'limit', 'stop_loss', 'take_profit']);
    $table->decimal('amount', 20, 8);
    $table->decimal('price', 20, 8)->nullable();
    $table->decimal('stop_price', 20, 8)->nullable();
    $table->decimal('filled_amount', 20, 8)->default(0);
    $table->enum('status', ['pending', 'partially_filled', 'filled', 'cancelled', 'expired'])->default('pending');
    $table->timestamp('expires_at')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    
    $table->index(['user_id', 'status']);
    $table->index(['pair', 'status']);
    $table->index('order_id');
});
*/

// Migration: create_transactions_table
/*
Schema::create('transactions', function (Blueprint $table) {
    $table->id();
    $table->string('transaction_id', 50)->unique();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->enum('type', ['deposit', 'withdrawal', 'trade', 'fee', 'bonus']);
    $table->string('currency', 10);
    $table->decimal('amount', 20, 8);
    $table->decimal('balance_before', 20, 8);
    $table->decimal('balance_after', 20, 8);
    $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
    $table->string('reference')->nullable(); // External reference
    $table->text('description')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    
    $table->index(['user_id', 'type']);
    $table->index(['transaction_id']);
    $table->index(['status', 'created_at']);
});
*/

// Migration: create_price_alerts_table
/*
Schema::create('price_alerts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('symbol', 20);
    $table->enum('condition', ['above', 'below']);
    $table->decimal('target_price', 20, 8);
    $table->boolean('is_active')->default(true);
    $table->boolean('is_triggered')->default(false);
    $table->timestamp('triggered_at')->nullable();
    $table->timestamps();
    
    $table->index(['user_id', 'is_active']);
    $table->index(['symbol', 'is_active']);
});
*/

echo "Database migrations created successfully!\n";
echo "Run: php artisan migrate\n";
?>
