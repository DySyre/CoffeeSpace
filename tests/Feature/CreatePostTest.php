<?php

use App\Livewire\Discover\DiscoverFeed;
use App\Livewire\Page\AddExperience;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function postPhoto(string $name)
{
    $contents = match (strtolower(pathinfo($name, PATHINFO_EXTENSION))) {
        'png' => base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jJX8AAAAASUVORK5CYII='),
        'gif' => base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'),
        default => file_get_contents(public_path('images/cafe.jpg')),
    };

    return UploadedFile::fake()->createWithContent($name, $contents);
}

function postEditor(User $user)
{
    return Livewire::actingAs($user)->test(AddExperience::class)
        ->set('title', 'My new coffee spot')->set('body', 'A lovely place to meet friends.')
        ->set('cafeName', 'Morning Café')->set('city', 'Meycauayan, Bulacan');
}

test('a photo post and its media belong to the authenticated user', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $other = User::factory()->create();

    postEditor($owner)->set('uploads', [postPhoto('visit.jpeg')])
        ->call('save')->assertHasNoErrors()->assertRedirect(route('home'));

    $post = Post::sole();
    expect($post->user->is($owner))->toBeTrue();
    expect($other->posts()->count())->toBe(0);
    $media = $post->media()->sole();
    expect($media->kind)->toBe('image');
    expect($media->post->is($post))->toBeTrue();
    Storage::disk('local')->assertExists($media->path);
    Livewire::test(DiscoverFeed::class)->assertSee('My new coffee spot')->assertSee(route('post-media.show', $media));
});

test('the picker adds and removes files without discarding other selections', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    postEditor($user)->set('upload', postPhoto('one.png'))
        ->assertHasNoErrors()->set('upload', postPhoto('two.jpg'))
        ->assertHasNoErrors()->call('removeMedia', 0)->call('save')->assertHasNoErrors();

    expect(Post::sole()->media()->count())->toBe(1);
});

test('guests cannot publish posts through the component', function () {
    Livewire::test(AddExperience::class)->call('save')->assertStatus(401);

    $this->assertDatabaseCount('posts', 0);
});

test('a post must include media', function () {
    $user = User::factory()->create();

    postEditor($user)->call('save')->assertHasErrors('uploads');

    $this->assertDatabaseCount('posts', 0);
});

test('oversized and unsupported files are rejected before publishing', function (string $kind) {
    Storage::fake('local');
    $user = User::factory()->create();
    $file = match ($kind) {
        'oversized' => postPhoto('large.jpg')->size(10241),
        'gif' => postPhoto('animated.gif'),
        'spoofed' => UploadedFile::fake()->createWithContent('photo.png', 'This is not a PNG.'),
        'video' => UploadedFile::fake()->createWithContent('broken.mp4', 'Not a valid video'),
    };

    postEditor($user)->set('upload', $file)->assertHasErrors('upload');

    $this->assertDatabaseCount('posts', 0);
    $this->assertDatabaseCount('post_media', 0);
})->with(['oversized', 'gif', 'spoofed', 'video']);

test('jpeg and png files exactly at the size limit are accepted', function (string $extension) {
    Storage::fake('local');
    $user = User::factory()->create();

    postEditor($user)->set('uploads', [postPhoto('photo.'.$extension)->size(10240)])
        ->call('save')->assertHasNoErrors();

    $this->assertDatabaseCount('posts', 1);
})->with(['jpeg', 'png']);

test('public media is readable but private media requires its owner', function () {
    Storage::fake('local');
    $owner = User::factory()->create();
    $other = User::factory()->create();
    postEditor($owner)->set('uploads', [postPhoto('photo.jpg')])->call('save');
    $post = Post::sole();
    $media = $post->media()->sole();
    $post->update(['visibility' => 'private']);

    $this->actingAs($other)->get(route('post-media.show', $media))->assertNotFound();
    $this->actingAs($owner)->get(route('post-media.show', $media))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->app['auth']->forgetGuards();
    $this->get(route('post-media.show', $media))->assertNotFound();
    $post->update(['visibility' => 'public']);
    $this->get(route('post-media.show', $media))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
});

test('private posts do not appear in the public feed', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    postEditor($user)->set('visibility', 'private')->set('uploads', [postPhoto('photo.png')])->call('save')->assertHasNoErrors();

    Livewire::test(DiscoverFeed::class)->assertDontSee('My new coffee spot');
    expect(Post::sole()->visibility)->toBe('private');
});

test('post fields are validated before any media is saved', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    postEditor($user)->set('title', ' ')->set('visibility', 'everyone')
        ->set('uploads', [postPhoto('photo.jpg')])->call('save')
        ->assertHasErrors(['title', 'visibility']);

    $this->assertDatabaseCount('posts', 0);
    expect(Storage::disk('local')->files('post-media/'.$user->id))->toBe([]);
});

test('a failure saving media rolls back the post and cleans up files', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    PostMedia::creating(function (PostMedia $media) {
        if ($media->position === 1) {
            throw new RuntimeException('Simulated second attachment failure');
        }
    });

    try {
        postEditor($user)->set('uploads', [postPhoto('one.jpg'), postPhoto('two.png')])
            ->call('save')->assertHasErrors('uploads');

        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('post_media', 0);
        expect(Storage::disk('local')->files('post-media/'.$user->id))->toBe([]);
    } finally {
        PostMedia::flushEventListeners();
    }
});

test('an optional confirmed pin is saved with its user owned post', function (float $latitude, float $longitude) {
    Storage::fake('local');
    $user = User::factory()->create();

    postEditor($user)->set('latitude', $latitude)->set('longitude', $longitude)
        ->set('uploads', [postPhoto('photo.jpg')])->call('save')->assertHasNoErrors();

    $post = Post::sole();
    expect((float) $post->latitude)->toBe($latitude);
    expect((float) $post->longitude)->toBe($longitude);
    expect($post->user_id)->toBe($user->id);
    Livewire::test(DiscoverFeed::class)->assertSee('View location pin');
})->with([[14.7352, 120.9551], [0.0, 0.0], [-90.0, 180.0]]);

test('invalid or incomplete location pins cannot be published', function ($latitude, $longitude, string $error) {
    Storage::fake('local');
    $user = User::factory()->create();

    postEditor($user)->set('latitude', $latitude)->set('longitude', $longitude)
        ->set('uploads', [postPhoto('photo.png')])->call('save')->assertHasErrors($error);

    $this->assertDatabaseCount('posts', 0);
    $this->assertDatabaseCount('post_media', 0);
})->with([
    [91, 120, 'latitude'], [-91, 120, 'latitude'], [14, 181, 'longitude'],
    [14, -181, 'longitude'], [14, null, 'longitude'], [null, 0, 'latitude'],
    ['invalid', 120, 'latitude'],
]);

test('private post pins are excluded from the public feed', function () {
    Storage::fake('local');
    $user = User::factory()->create();

    postEditor($user)->set('latitude', 14.7352)->set('longitude', 120.9551)
        ->set('visibility', 'private')->set('uploads', [postPhoto('photo.jpg')])->call('save')->assertHasNoErrors();

    Livewire::test(DiscoverFeed::class)->assertDontSee('View location pin');
});
