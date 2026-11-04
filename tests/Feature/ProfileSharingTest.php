<?php

use App\Livewire\Discover\DiscoverFeed;
use App\Livewire\Page\MyProfile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;

function sharedProfileStory(User $user, string $title, string $visibility = 'public')
{
    return $user->posts()->create(['author_handle' => 'coffee.friend', 'title' => $title, 'body' => 'Coffee memories.', 'cafe_name' => 'Cafe', 'city' => 'Bulacan', 'visibility' => $visibility]);
}

test('profile UUID links are unique and remain stable after editing account information', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $uuid = $user->profile_uuid;
    expect(Str::isUuid($uuid))->toBeTrue();
    expect($uuid)->not->toBe($other->profile_uuid);
    $user->update(['name' => 'Another coffee name']);
    expect($user->refresh()->profile_uuid)->toBe($uuid);
    $this->actingAs($user)->get('/profile')->assertRedirect($user->profileUrl());
});

test('UUID profiles are public and hide private stories and editing controls from visitors', function () {
    $owner = User::factory()->create();
    sharedProfileStory($owner, 'Public memory');
    sharedProfileStory($owner, 'Private memory', 'private');
    $this->get($owner->profileUrl())->assertOk()->assertSee($owner->name)->assertSee('Public memory')->assertDontSee('Private memory')
        ->assertDontSee('aria-label="Edit profile bio"', false)->assertDontSee('aria-label="Upload cover photo"', false);
    $this->get('/profile/not-a-uuid')->assertNotFound();
    $this->get('/profile/'.Str::uuid())->assertNotFound();
});

test('the profile from the URL is shown instead of the signed in viewers profile', function () {
    $owner = User::factory()->create(['name' => 'Coffee creator']);
    $visitor = User::factory()->create(['name' => 'Coffee visitor']);
    $this->actingAs($visitor)->get($owner->profileUrl())->assertOk()->assertSee('Coffee creator')->assertSee('Follow');
    Livewire::actingAs($visitor)->test(MyProfile::class, ['user' => $owner])->set('bio', 'Unauthorized edit')->call('saveBio')->assertForbidden();
    expect($owner->refresh()->bio)->toBeNull();
    Livewire::actingAs($visitor)->test(MyProfile::class, ['user' => $owner])->set('cover', UploadedFile::fake()->createWithContent('cover.jpg', file_get_contents(public_path('images/cafe.jpg'))))->assertForbidden();
});

test('people can follow back and unfollow while owners cannot follow themselves', function () {
    $creator = User::factory()->create();
    $viewer = User::factory()->create();
    $creator->following()->attach($viewer);
    $component = Livewire::actingAs($viewer)->test(MyProfile::class, ['user' => $creator])->assertSee('Follow back');
    $component->call('toggleFollow')->assertSee('Unfollow')->assertViewHas('followerCount', 1);
    expect($viewer->following()->whereKey($creator)->exists())->toBeTrue();
    $component->call('toggleFollow')->assertSee('Follow back')->assertViewHas('followerCount', 0);
    expect($viewer->following()->whereKey($creator)->exists())->toBeFalse();
    Livewire::actingAs($creator)->test(MyProfile::class, ['user' => $creator])->call('toggleFollow')->assertForbidden();
});

test('following feed includes only public posts by followed creators and links to UUID profiles', function () {
    $viewer = User::factory()->create();
    $followed = User::factory()->create();
    $other = User::factory()->create();
    $viewer->following()->attach($followed);
    sharedProfileStory($followed, 'Followed public story');
    sharedProfileStory($followed, 'Followed secret story', 'private');
    sharedProfileStory($other, 'Other creator story');
    Livewire::actingAs($viewer)->test(DiscoverFeed::class)->set('filter', 'following')
        ->assertSee('Followed public story')->assertSee($followed->profileUrl())
        ->assertDontSee('Followed secret story')->assertDontSee('Other creator story');
});

test('public profile photos and covers use the UUID owner without requiring the viewers session', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    Storage::disk('local')->put('photo.jpg', file_get_contents(public_path('images/cafe.jpg')));
    $asset = ['disk' => 'local', 'path' => 'photo.jpg', 'mime_type' => 'image/jpeg'];
    $user->profile_photo = $asset;
    $user->cover_photo = $asset;
    $user->save();
    $this->get($user->profilePhotoUrl())->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $this->get($user->coverPhotoUrl())->assertOk()->assertHeader('Content-Type', 'image/jpeg');
});
