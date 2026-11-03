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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('demo_key')->nullable()->unique();
            $table->string('author_handle');
            $table->string('title');
            $table->text('body');
            $table->string('cafe_name');
            $table->string('city');
            $table->string('image_path')->nullable();
            $table->string('image_alt')->nullable();
            $table->decimal('rating', 2, 1)->nullable();
            $table->unsignedInteger('price_centavos')->nullable();
            $table->string('tag')->nullable();
            $table->string('visibility')->default('public');
            $table->timestamps();
            $table->index(['visibility', 'id']);
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['post_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
        Schema::dropIfExists('posts');
    }
};
