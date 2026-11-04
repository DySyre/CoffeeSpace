<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planned_visits', function (Blueprint $table) {
            $table->foreignId('conversation_id')->nullable()->after('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('accepted_by_id')->nullable()->after('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending')->after('planned_at');
            $table->timestamp('responded_at')->nullable()->after('status');
            $table->index(['conversation_id', 'status', 'planned_at']);
            $table->index(['accepted_by_id', 'status', 'planned_at']);
        });
    }

    public function down(): void
    {
        Schema::table('planned_visits', function (Blueprint $table) {
            $table->dropIndex(['conversation_id', 'status', 'planned_at']);
            $table->dropIndex(['accepted_by_id', 'status', 'planned_at']);
            $table->dropConstrainedForeignId('conversation_id');
            $table->dropConstrainedForeignId('accepted_by_id');
            $table->dropColumn(['status', 'responded_at']);
        });
    }
};
