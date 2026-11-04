<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coffee_budget_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('daily_centavos')->nullable();
            $table->unsignedInteger('weekly_centavos')->nullable();
            $table->unsignedInteger('monthly_centavos')->nullable();
            $table->timestamps();
        });
        Schema::create('coffee_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('item_name');
            $table->string('cafe_name')->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price_centavos');
            $table->date('purchased_on');
            $table->timestamps();
            $table->index(['user_id', 'purchased_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coffee_purchases');
        Schema::dropIfExists('coffee_budget_limits');
    }
};
