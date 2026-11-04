<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->uuid('post_uuid')->nullable()->unique();
            $table->timestamp('archived_at')->nullable()->index();
            $table->softDeletes();
        });
        DB::table('posts')->select('id')->orderBy('id')->chunkById(200, function ($posts) {
            foreach ($posts as $post) {
                DB::table('posts')->where('id', $post->id)->update(['post_uuid' => (string) Str::uuid()]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['post_uuid', 'archived_at', 'deleted_at']);
        });
    }
};
