<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('posts', function (Blueprint $table) {
                $table->fullText(['title', 'body', 'author_handle', 'cafe_name', 'city', 'tag'], 'posts_search_fulltext');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->fullText('name', 'users_name_fulltext');
                $table->index('name', 'users_name_prefix_index');
            });
        }

        Schema::table('posts', function (Blueprint $table) {
            $table->index(['latitude', 'longitude'], 'posts_nearby_coordinates_index');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_nearby_coordinates_index');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            Schema::table('posts', function (Blueprint $table) {
                $table->dropFullText('posts_search_fulltext');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->dropFullText('users_name_fulltext');
                $table->dropIndex('users_name_prefix_index');
            });
        }
    }
};
