<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

test('fresh migrations provide the account post media and follow schema', function () {
    expect(Schema::hasColumns('users', ['bio', 'profile_photo', 'cover_photo', 'profile_uuid']))->toBeTrue();
    expect(Schema::hasColumns('posts', ['post_uuid', 'latitude', 'longitude', 'amenities', 'archived_at', 'deleted_at']))->toBeTrue();
    expect(Schema::hasColumns('post_media', ['post_id', 'disk', 'path', 'asset_id', 'resource_type', 'delivery_type', 'asset_folder']))->toBeTrue();
    expect(Schema::hasColumns('user_follows', ['follower_id', 'followed_id']))->toBeTrue();
});

test('the UUID backfill migration repairs older records without changing existing links or media', function () {
    $migration = require database_path('migrations/2026_11_04_204236_backfill_missing_profile_and_post_uuids.php');
    $migration->down();
    $existing = User::factory()->create();
    $legacy = User::factory()->create();
    $post = $legacy->posts()->create(['author_handle' => 'legacy.coffee', 'title' => 'Existing coffee memory', 'body' => 'Keep this story.', 'cafe_name' => 'Cafe', 'city' => 'Bulacan']);
    $post->media()->create(['disk' => 'cloudinary', 'path' => 'post/existing/img/preserved', 'kind' => 'image', 'mime_type' => 'image/jpeg', 'size_bytes' => 100, 'position' => 0]);
    $uuid = $existing->profile_uuid;
    DB::table('users')->where('id', $legacy->id)->update(['profile_uuid' => null]);
    DB::table('posts')->where('id', $post->id)->update(['post_uuid' => null]);

    $migration->up();

    expect($existing->refresh()->profile_uuid)->toBe($uuid);
    expect(Str::isUuid($legacy->refresh()->profile_uuid))->toBeTrue();
    expect(Str::isUuid($post->refresh()->post_uuid))->toBeTrue();
    expect($post->body)->toBe('Keep this story.');
    expect($post->media()->sole()->path)->toBe('post/existing/img/preserved');
});

test('an existing pre November fourth database upgrades without losing its users posts or media references', function () {
    expect(DB::connection()->getDriverName())->toBe('sqlite');
    expect(DB::connection()->getDatabaseName())->toBe(':memory:');

    $originalConnection = DB::getDefaultConnection();
    $upgradeConnection = 'migration_upgrade';
    config(['database.connections.'.$upgradeConnection => [...config('database.connections.sqlite'), 'database' => ':memory:']]);
    DB::purge($upgradeConnection);
    DB::setDefaultConnection($upgradeConnection);

    try {
        $files = glob(database_path('migrations/*.php'));
        sort($files);

        foreach ($files as $file) {
            if (basename($file) < '2026_11_04_') {
                (require $file)->up();
            }
        }

        $userId = DB::table('users')->insertGetId(['name' => 'Existing coffee creator', 'email' => 'upgrade@example.test', 'password' => 'existing-password-hash', 'profile_photo' => json_encode(['disk' => 'local', 'path' => 'user/existing/profile.jpg'])]);
        $postId = DB::table('posts')->insertGetId(['user_id' => $userId, 'author_handle' => 'existing.creator', 'title' => 'Keep my old post', 'body' => 'Existing coffee memories', 'cafe_name' => 'Cafe', 'city' => 'Bulacan', 'visibility' => 'public']);
        DB::table('post_media')->insert(['post_id' => $postId, 'disk' => 'cloudinary', 'path' => 'post/existing/img/original', 'kind' => 'image', 'mime_type' => 'image/jpeg', 'size_bytes' => 100, 'position' => 0]);

        foreach ($files as $file) {
            if (basename($file) >= '2026_11_04_') {
                (require $file)->up();
            }
        }

        $user = DB::table('users')->find($userId);
        $post = DB::table('posts')->find($postId);
        expect($user->name)->toBe('Existing coffee creator');
        expect(Str::isUuid($user->profile_uuid))->toBeTrue();
        expect(json_decode($user->profile_photo, true)['path'])->toBe('user/existing/profile.jpg');
        expect($post->body)->toBe('Existing coffee memories');
        expect(Str::isUuid($post->post_uuid))->toBeTrue();
        expect($post->archived_at)->toBeNull();
        expect($post->deleted_at)->toBeNull();
        expect(DB::table('post_media')->where('post_id', $postId)->value('path'))->toBe('post/existing/img/original');
    } finally {
        DB::setDefaultConnection($originalConnection);
        DB::purge($upgradeConnection);
    }
});
