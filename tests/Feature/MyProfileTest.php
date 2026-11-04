<?php

use App\Livewire\Discover\DiscoverFeed;
use App\Livewire\Page\MyProfile;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\User;
use App\Services\CloudinaryMediaClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('the coffee profile requires authentication', function () {
    $this->get('/profile')->assertRedirect(route('login'));
});

test('the coffee profile shows only the owners stories including private ones and counts distinct places', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $attributes = ['author_handle' => 'coffee.friend', 'title' => 'First morning coffee', 'body' => 'A quiet corner.', 'cafe_name' => 'Common Grounds', 'city' => 'Bulacan'];
    $owner->posts()->create($attributes);
    $private = $owner->posts()->create([...$attributes, 'title' => 'My private visit', 'visibility' => 'private']);
    $owner->posts()->create([...$attributes, 'city' => 'Manila', 'title' => 'Another branch']);
    $other->posts()->create([...$attributes, 'title' => 'Someone elses secret', 'visibility' => 'private']);
    $private->media()->create(['disk' => 'local', 'path' => 'private.jpg', 'mime_type' => 'image/jpeg', 'kind' => 'image', 'size_bytes' => 100, 'position' => 0]);

    Livewire::actingAs($owner)->test(MyProfile::class, ['user' => $owner])
        ->assertViewHas('storyCount', 3)
        ->assertViewHas('placeCount', 2)
        ->assertSee('My private visit')
        ->assertSee('Only me')
        ->assertSee(route('post-media.show', $private->media()->sole()))
        ->assertDontSee('Someone elses secret');
});

test('profile stories are paginated newest first', function () {
    $user = User::factory()->create();
    foreach (range(1, 13) as $number) {
        $user->posts()->create(['author_handle' => 'coffee.friend', 'title' => 'Coffee visit '.$number, 'body' => 'Coffee memories.', 'cafe_name' => 'Cafe', 'city' => 'Bulacan']);
    }

    Livewire::actingAs($user)->test(MyProfile::class, ['user' => $user])
        ->assertViewHas('posts', fn ($posts) => $posts->count() === 12 && $posts->first()->title === 'Coffee visit 13')
        ->call('setPage', 2)
        ->assertViewHas('posts', fn ($posts) => $posts->count() === 1 && $posts->first()->title === 'Coffee visit 1');
});

test('the saved tab shows the original post and media without copying either', function () {
    $viewer = User::factory()->create();
    $author = User::factory()->create();
    $post = $author->posts()->create([
        'author_handle' => 'coffee.friend',
        'title' => 'Saved coffee corner',
        'body' => 'A saved recommendation.',
        'cafe_name' => 'Common Grounds',
        'city' => 'Bulacan',
        'visibility' => 'public',
    ]);
    $media = $post->media()->create([
        'disk' => 'local',
        'path' => 'post-media/original.jpg',
        'mime_type' => 'image/jpeg',
        'kind' => 'image',
        'size_bytes' => 100,
        'position' => 0,
    ]);
    $postCount = Post::count();
    $mediaCount = PostMedia::count();

    Livewire::actingAs($viewer)->test(DiscoverFeed::class)
        ->call('toggleSave', $post->id)
        ->assertHasNoErrors();

    Livewire::actingAs($viewer)->test(MyProfile::class, ['user' => $viewer])
        ->set('tab', 'saved')
        ->assertViewHas('posts', fn ($posts) => $posts->count() === 1 && $posts->first()->is($post))
        ->assertSee($post->title)
        ->assertSee(route('post-media.show', $media));

    $this->assertDatabaseCount('saved_posts', 1);
    $this->assertDatabaseCount('posts', $postCount);
    $this->assertDatabaseCount('post_media', $mediaCount);

    Livewire::actingAs($viewer)->test(DiscoverFeed::class)->call('toggleSave', $post->id)->assertHasNoErrors();

    $this->assertDatabaseMissing('saved_posts', ['user_id' => $viewer->id, 'post_id' => $post->id]);
    $this->assertDatabaseCount('posts', $postCount);
    $this->assertDatabaseCount('post_media', $mediaCount);
});

test('profile navigation points to the coffee profile and settings stay behind the gear', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get($user->profileUrl())->assertOk()
        ->assertSee('Your coffee story starts here')
        ->assertSee(route('settings.profile'))
        ->assertDontSee('Update your name and email address');
    $this->get('/settings/profile')->assertOk()->assertSee('Update your name and email address');
});

test('profile bio edits belong to the signed in user and can be cleared', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    Livewire::actingAs($owner)->test(MyProfile::class, ['user' => $owner])->set('bio', '  Finding quiet coffee corners.  ')->call('saveBio')->assertHasNoErrors()->assertDispatched('bio-saved');
    expect($owner->refresh()->bio)->toBe('Finding quiet coffee corners.');
    expect($other->refresh()->bio)->toBeNull();
    Livewire::actingAs($owner)->test(MyProfile::class, ['user' => $owner])->set('bio', '')->call('saveBio')->assertHasNoErrors();
    expect($owner->refresh()->bio)->toBe('');
});

test('profile bio has a maximum length', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)->test(MyProfile::class, ['user' => $user])->set('bio', str_repeat('a', 161))->call('saveBio')->assertHasErrors(['bio' => 'max']);
    expect($user->refresh()->bio)->toBeNull();
});

test('profile photos replace the previous file and are served for the signed in account', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $photo = fn () => UploadedFile::fake()->createWithContent('avatar.jpg', file_get_contents(public_path('images/cafe.jpg')));
    Livewire::actingAs($owner)->test(MyProfile::class, ['user' => $owner])->set('photo', $photo())->assertHasNoErrors()->assertDispatched('profile-photo-saved');
    $old = $owner->refresh()->profile_photo;
    expect($old['path'])->toStartWith('user/'.$owner->id.'/profile/');
    $this->actingAs($owner)->get(route('profile.photo'))->assertOk()->assertHeader('Content-Type', 'image/jpeg');

    Livewire::actingAs($owner)->test(MyProfile::class, ['user' => $owner])->set('photo', $photo())->assertHasNoErrors();
    Storage::disk('local')->assertMissing($old['path']);
    Storage::disk('local')->assertExists($owner->refresh()->profile_photo['path']);
    expect($other->refresh()->profile_photo)->toBeNull();
    $this->actingAs($other)->get(route('profile.photo'))->assertNotFound();
});

test('profile photos reject non images and oversized files', function () {
    $user = User::factory()->create();
    Livewire::actingAs($user)->test(MyProfile::class, ['user' => $user])
        ->set('photo', UploadedFile::fake()->createWithContent('fake.jpg', 'not a photo'))
        ->assertHasErrors('photo');
    Livewire::actingAs($user)->test(MyProfile::class, ['user' => $user])
        ->set('photo', UploadedFile::fake()->create('large.jpg', 10241, 'image/jpeg'))
        ->assertHasErrors('photo');
    expect($user->refresh()->profile_photo)->toBeNull();
});

test('a failed cloudinary photo upload keeps the existing photo', function () {
    $user = User::factory()->create();
    $previous = ['disk' => 'local', 'path' => 'existing.jpg', 'mime_type' => 'image/jpeg'];
    $user->profile_photo = $previous;
    $user->save();
    config(['post-media.disk' => 'cloudinary']);
    $this->mock(CloudinaryMediaClient::class)->shouldReceive('upload')->once()->andThrow(new RuntimeException('Upload unavailable'));

    Livewire::actingAs($user)->test(MyProfile::class, ['user' => $user])
        ->set('photo', UploadedFile::fake()->createWithContent('avatar.jpg', file_get_contents(public_path('images/cafe.jpg'))))
        ->assertHasErrors('photo')->assertDispatched('profile-image-result', field: 'photo', success: false, message: 'Your photo could not be saved. Please try again.');
    expect($user->refresh()->profile_photo)->toBe($previous);
});

test('cover photos are independent of avatars and replace only the owners old cover', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $avatar = ['disk' => 'local', 'path' => 'avatar.jpg', 'mime_type' => 'image/jpeg'];
    $owner->profile_photo = $avatar;
    $owner->save();
    $image = fn () => UploadedFile::fake()->createWithContent('cover.jpg', file_get_contents(public_path('images/cafe.jpg')));

    Livewire::actingAs($owner)->test(MyProfile::class, ['user' => $owner])->set('cover', $image())->assertHasNoErrors()->assertDispatched('cover-photo-saved');
    $old = $owner->refresh()->cover_photo;
    expect($old['path'])->toStartWith('user/'.$owner->id.'/cover/');
    expect($owner->profile_photo)->toBe($avatar);
    $this->actingAs($owner)->get(route('profile.cover'))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    Livewire::actingAs($owner)->test(MyProfile::class, ['user' => $owner])->set('cover', $image())->assertHasNoErrors();
    Storage::disk('local')->assertMissing($old['path']);
    Storage::disk('local')->assertExists($owner->refresh()->cover_photo['path']);
    expect($other->refresh()->cover_photo)->toBeNull();
    $this->actingAs($other)->get(route('profile.cover'))->assertNotFound();
});

test('invalid cover photos are rejected without overwriting the saved cover', function () {
    $user = User::factory()->create();
    $old = ['disk' => 'local', 'path' => 'existing-cover.jpg', 'mime_type' => 'image/jpeg'];
    $user->cover_photo = $old;
    $user->save();

    Livewire::actingAs($user)->test(MyProfile::class, ['user' => $user])
        ->set('cover', UploadedFile::fake()->createWithContent('fake.jpg', 'not an image'))->assertHasErrors('cover');
    expect($user->refresh()->cover_photo)->toBe($old);
});

test('cloudinary cover uploads use the account cover folder and keep the old cover on failure', function () {
    $user = User::factory()->create();
    $old = ['disk' => 'local', 'path' => 'existing-cover.jpg', 'mime_type' => 'image/jpeg'];
    $user->cover_photo = $old;
    $user->save();
    config(['post-media.disk' => 'cloudinary']);
    $this->mock(CloudinaryMediaClient::class)->shouldReceive('upload')->once()
        ->withArgs(fn ($file, $folder, $kind) => $folder === 'user/'.$user->id.'/cover' && $kind === 'image')
        ->andThrow(new RuntimeException('Unavailable'));

    Livewire::actingAs($user)->test(MyProfile::class, ['user' => $user])
        ->set('cover', UploadedFile::fake()->createWithContent('cover.jpg', file_get_contents(public_path('images/cafe.jpg'))))
        ->assertHasErrors('cover');
    expect($user->refresh()->cover_photo)->toBe($old);
});

test('the shared header and sidebar render the saved avatar and send its url after a replacement', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $user->profile_photo = ['disk' => 'local', 'path' => 'old-avatar.jpg', 'mime_type' => 'image/jpeg'];
    $user->save();

    $this->actingAs($user)->get($user->profileUrl())->assertOk()->assertSee($user->profilePhotoUrl());
    Livewire::actingAs($user)->test(MyProfile::class, ['user' => $user])
        ->set('photo', UploadedFile::fake()->createWithContent('avatar.jpg', file_get_contents(public_path('images/cafe.jpg'))))
        ->assertHasNoErrors()->assertDispatched('profile-photo-saved', url: $user->refresh()->profilePhotoUrl());
});

test('cover success notifications follow the saved database record', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    Livewire::actingAs($user)->test(MyProfile::class, ['user' => $user])
        ->set('cover', UploadedFile::fake()->createWithContent('cover.jpg', file_get_contents(public_path('images/cafe.jpg'))))
        ->assertHasNoErrors()
        ->assertDispatched('profile-image-result', field: 'cover', success: true, message: 'Your cover photo has been updated.');
    expect($user->refresh()->cover_photo)->not->toBeNull();
    Storage::disk('local')->assertExists($user->cover_photo['path']);
});
