<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('wallet_id', 50)->unique();
            $table->string('currency', 10); // BTC, ETH, USDT, BNB
            $table->string('address')->unique();
            $table->text('private_key_encrypted');
            $table->text('public_key');
            $table->decimal('balance', 20, 8)->default(0);
            $table->boolean('is_active')->default(true);
            $table->text('mpc_key_share_1'); // MPC security
            $table->text('mpc_key_share_2');
            $table->text('mpc_key_share_3');
            $table->timestamps();
            
            $table->index(['user_id', 'currency']);
            $table->index('wallet_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('wallets');
    }
};
