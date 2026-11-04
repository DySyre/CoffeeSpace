<?php

use App\Livewire\Discover\DiscoverFeed;
use App\Livewire\Page\MyProfile;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

function managedStory(User $owner): Post
{
    return $owner->posts()->create(['author_handle' => 'coffee.owner', 'title' => 'A coffee story to manage', 'body' => 'Coffee memories.', 'cafe_name' => 'Cafe', 'city' => 'Bulacan']);
}

test('posts receive stable UUID URLs and the share page respects the audience', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $post = managedStory($owner);
    $uuid = $post->post_uuid;
    expect(Str::isUuid($uuid))->toBeTrue();
    $this->get($post->postUrl())->assertOk()->assertSee($post->title);
    Livewire::actingAs($owner)->test(MyProfile::class, ['user' => $owner])->call('updatePostAudience', $post->id, 'followers')->assertDispatched('post-managed');
    $post->refresh();
    expect($post->post_uuid)->toBe($uuid);
    $this->actingAs($viewer)->get($post->postUrl())->assertNotFound();
    $viewer->following()->attach($owner);
    $this->get($post->postUrl())->assertOk();
    Livewire::actingAs($owner)->test(MyProfile::class, ['user' => $owner])->call('updatePostAudience', $post->id, 'private')->assertHasNoErrors();
    $this->actingAs($viewer)->get($post->postUrl())->assertNotFound();
    $this->actingAs($owner)->get($post->postUrl())->assertOk();
});

test('archived posts disappear from the feed and remain restorable by their owner', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $post = managedStory($owner);
    $component = Livewire::actingAs($owner)->test(MyProfile::class, ['user' => $owner]);
    $component->call('archivePost', $post->id)->assertDispatched('post-managed');
    expect($post->refresh()->archived_at)->not->toBeNull();
    Livewire::test(DiscoverFeed::class)->assertDontSee($post->title);
    $this->actingAs($viewer)->get($post->postUrl())->assertNotFound();
    $this->actingAs($owner)->get($post->postUrl())->assertOk();
    $component->set('postFilter', 'archived')->assertSee($post->title)->call('restorePost', $post->id);
    expect($post->refresh()->archived_at)->toBeNull();
});

test('delete is soft and preserves post records comments and local or cloudinary media', function () {
    Storage::fake('local');
    Http::preventStrayRequests();
    $owner = User::factory()->create();
    $post = managedStory($owner);
    Storage::disk('local')->put('keep.jpg', 'photo bytes');
    $post->media()->create(['disk' => 'local', 'path' => 'keep.jpg', 'kind' => 'image', 'mime_type' => 'image/jpeg', 'size_bytes' => 11, 'position' => 0]);
    $post->media()->create(['disk' => 'cloudinary', 'path' => 'post/test/img/keep', 'kind' => 'image', 'mime_type' => 'image/jpeg', 'size_bytes' => 11, 'position' => 1]);
    $comment = $post->comments()->make(['body' => 'Keep this comment']);
    $comment->user()->associate($owner);
    $comment->save();
    $component = Livewire::actingAs($owner)->test(MyProfile::class, ['user' => $owner])->call('deletePost', $post->id);
    $this->assertSoftDeleted($post);
    $this->assertDatabaseCount('post_media', 2);
    $this->assertDatabaseCount('comments', 1);
    Storage::disk('local')->assertExists('keep.jpg');
    Http::assertNothingSent();
    $this->get($post->postUrl())->assertNotFound();
    $media = $post->media()->first();
    $this->get(route('post-media.show', $media))->assertNotFound();
    $component->set('postFilter', 'trash')->assertSee($post->title)->call('restorePost', $post->id);
    expect($post->refresh()->deleted_at)->toBeNull();
    $this->get($post->postUrl())->assertOk();
});

test('post management is owner scoped and rejects invalid audiences', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $post = managedStory($owner);
    foreach (['archivePost', 'deletePost', 'restorePost'] as $action) {
        Livewire::actingAs($other)->test(MyProfile::class, ['user' => $owner])->call($action, $post->id)->assertForbidden();
        Livewire::actingAs($other)->test(MyProfile::class, ['user' => $other])->call($action, $post->id)->assertNotFound();
    }
    Livewire::actingAs($other)->test(MyProfile::class, ['user' => $owner])->call('updatePostAudience', $post->id, 'public')->assertForbidden();
    Livewire::actingAs($owner)->test(MyProfile::class, ['user' => $owner])->call('updatePostAudience', $post->id, 'invalid')->assertHasErrors('visibility');
    expect($post->refresh()->visibility)->toBe('public');
});
