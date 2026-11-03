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
        Schema::table('post_media', function (Blueprint $table) {
            $table->string('asset_id')->nullable();
            $table->string('resource_type', 10)->nullable();
            $table->string('delivery_type', 20)->nullable();
            $table->string('format', 10)->nullable();
            $table->string('asset_folder')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_media', function (Blueprint $table) {
            $table->dropColumn(['asset_id', 'resource_type', 'delivery_type', 'format', 'asset_folder']);
        });
    }
};
