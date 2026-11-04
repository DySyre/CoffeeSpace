<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('type')->default('text')->after('body');
            $table->json('metadata')->nullable()->after('type');
        });

        Schema::create('planned_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('message_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('cafe_name');
            $table->string('address', 500)->nullable();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->dateTime('planned_at');
            $table->timestamps();
            $table->index(['user_id', 'planned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planned_visits');
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'metadata']);
        });
    }
};
