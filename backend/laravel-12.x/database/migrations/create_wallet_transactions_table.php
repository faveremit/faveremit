<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_hash', 100)->unique();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['send', 'receive', 'swap_in', 'swap_out']);
            $table->string('currency', 10);
            $table->decimal('amount', 20, 8);
            $table->decimal('fee', 20, 8)->default(0);
            $table->string('from_address');
            $table->string('to_address');
            $table->enum('status', ['pending', 'confirmed', 'failed', 'cancelled'])->default('pending');
            $table->integer('confirmations')->default(0);
            $table->bigInteger('block_height')->nullable();
            $table->decimal('gas_price', 20, 8)->nullable();
            $table->decimal('gas_used', 20, 8)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['wallet_id', 'type']);
            $table->index(['user_id', 'status']);
            $table->index('transaction_hash');
        });
    }

    public function down()
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
