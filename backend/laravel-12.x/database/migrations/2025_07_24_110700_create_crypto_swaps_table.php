<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('crypto_swaps', function (Blueprint $table) {
            $table->id();
            $table->string('swap_id', 50)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('from_currency', 10);
            $table->string('to_currency', 10);
            $table->decimal('to_amount', 20, 8);
            $table->decimal('exchange_rate', 20, 8);
            $table->decimal('fee', 20, 8);
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('executed_at')->nullable();
            $table->foreignId('from_wallet_id')->constrained('wallets');
            $table->foreignId('to_wallet_id')->constrained('wallets');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index('swap_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('crypto_swaps');
    }
};
