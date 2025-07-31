<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crypto_swaps', function (Blueprint $table) {
            $table->id();
            $table->string('swap_id', 50)->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('from_currency', 10);
            $table->string('to_currency', 10);
            $table->decimal('from_amount', 20, 8);
            $table->decimal('to_amount', 20, 8);
            $table->decimal('exchange_rate', 20, 8);
            $table->decimal('fee', 20, 8)->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->timestamp('executed_at')->nullable();
            $table->foreignId('from_wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->foreignId('to_wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->json('metadata')->nullable();
            $table->string('transaction_hash', 100)->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['user_id', 'status']);
            $table->index(['swap_id']);
            $table->index(['from_currency', 'to_currency']);
            $table->index(['created_at']);
            $table->index(['status', 'executed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_swaps');
    }
};
