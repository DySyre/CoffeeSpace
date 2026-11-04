<?php

use App\Livewire\Discover\DiscoverFeed;
use App\Livewire\Posts\PostComments;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('followers only stories and files are visible to owners and followers but not other viewers', function (string $viewerKind, bool $allowed) {
    Storage::fake('local');
    $owner = User::factory()->create();
    $follower = User::factory()->create();
    $stranger = User::factory()->create();
    $follower->following()->attach($owner);
    $post = $owner->posts()->create(['author_handle' => 'coffee', 'title' => 'Followers coffee memory', 'body' => 'A quiet afternoon.', 'cafe_name' => 'Cafe', 'city' => 'Bulacan', 'visibility' => 'followers']);
    Storage::disk('local')->put('photo.jpg', file_get_contents(public_path('images/cafe.jpg')));
    $media = $post->media()->create(['disk' => 'local', 'path' => 'photo.jpg', 'kind' => 'image', 'mime_type' => 'image/jpeg', 'size_bytes' => 100, 'position' => 0]);
    $viewer = match ($viewerKind) {
        'owner' => $owner, 'follower' => $follower, 'stranger' => $stranger, default => null
    };
    if ($viewer) {
        $this->actingAs($viewer);
    }
    $profile = $this->get($owner->profileUrl())->assertOk();
    $feed = Livewire::test(DiscoverFeed::class);
    $file = $this->get(route('post-media.show', $media));
    if ($allowed) {
        $profile->assertSee($post->title);
        $feed->assertSee($post->title);
        $file->assertOk();
        Livewire::test(PostComments::class, ['postId' => $post->id])->call('toggle')->assertSet('expanded', true);
    } else {
        $profile->assertDontSee($post->title);
        $feed->assertDontSee($post->title);
        $file->assertNotFound();
        Livewire::test(PostComments::class, ['postId' => $post->id])->call('toggle')->assertNotFound();
    }
})->with([['guest', false], ['stranger', false], ['owner', true], ['follower', true]]);

test('unfollowing immediately revokes followers only media access', function () {
    $owner = User::factory()->create();
    $follower = User::factory()->create();
    $follower->following()->attach($owner);
    $post = $owner->posts()->create(['author_handle' => 'coffee', 'title' => 'Restricted coffee', 'body' => 'Coffee.', 'cafe_name' => 'Cafe', 'city' => 'Bulacan', 'visibility' => 'followers']);
    expect(Post::visibleTo($follower)->whereKey($post)->exists())->toBeTrue();
    $follower->following()->detach($owner);
    expect(Post::visibleTo($follower)->whereKey($post)->exists())->toBeFalse();
});

test('a post can be published to followers only', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    postEditor($owner)->set('uploads', [postPhoto('followers.jpg')])->set('visibility', 'followers')->call('save')->assertHasNoErrors();
    expect($owner->posts()->sole()->visibility)->toBe('followers');
});
