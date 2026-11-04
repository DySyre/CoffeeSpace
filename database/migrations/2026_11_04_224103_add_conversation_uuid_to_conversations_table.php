<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->uuid('conversation_uuid')->nullable()->unique();
        });

        DB::table('conversations')->whereNull('conversation_uuid')->orderBy('id')->chunkById(200, function ($conversations) {
            foreach ($conversations as $conversation) {
                DB::table('conversations')->where('id', $conversation->id)->update([
                    'conversation_uuid' => (string) Str::uuid(),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('conversation_uuid');
        });
    }
};
