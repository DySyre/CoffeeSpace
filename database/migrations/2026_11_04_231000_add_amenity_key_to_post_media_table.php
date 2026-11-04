<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('post_media', function (Blueprint $table) {
            $table->string('amenity_key', 32)->nullable()->after('position');
            $table->index(['post_id', 'amenity_key', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('post_media', function (Blueprint $table) {
            $table->dropIndex(['post_id', 'amenity_key', 'position']);
            $table->dropColumn('amenity_key');
        });
    }
};
