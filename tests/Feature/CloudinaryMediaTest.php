<?php

use App\Livewire\CreatePost;
use App\Models\Post;
use App\Models\User;
use App\Services\CloudinaryMediaClient;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function useCloudinaryForTests(): void
{
    config([
        'post-media.disk' => 'cloudinary',
        'services.cloudinary.cloud_name' => 'coffee-tests',
        'services.cloudinary.api_key' => 'test-key',
        'services.cloudinary.api_secret' => 'test-secret',
        'services.cloudinary.folder_mode' => 'dynamic',
    ]);
    Http::preventStrayRequests();
}

function cloudinaryUploadReply(Request $request): array
{
    $video = str_contains($request->url(), '/video/');
    $data = cloudinaryRequestData($request);

    return [
        'public_id' => $data['public_id'], 'asset_id' => 'asset-'.sha1($data['public_id']),
        'resource_type' => $video ? 'video' : 'image', 'type' => 'authenticated',
        'format' => $video ? 'mp4' : 'jpg', 'bytes' => 1000, 'duration' => $video ? 4 : null,
    ];
}

function cloudinaryRequestData(Request $request): array
{
    $data = $request->data();
    if (isset($data[0]['name'])) {
        return collect($data)->mapWithKeys(fn ($part) => [$part['name'] => $part['contents']])->all();
    }

    return $data;
}

test('posts upload photos and videos into separate per-post Cloudinary folders', function () {
    useCloudinaryForTests();
    $user = User::factory()->create();
    Http::fake(fn (Request $request) => Http::response(cloudinaryUploadReply($request)));
    $photo = UploadedFile::fake()->createWithContent('photo.jpg', file_get_contents(public_path('images/cafe.jpg')));
    $video = UploadedFile::fake()->createWithContent('video.mp4', file_get_contents(__DIR__.'/../Fixtures/video.mp4'));

    Livewire::actingAs($user)->test(CreatePost::class)
        ->set('title', 'A cloud coffee story')->set('body', 'A visit with friends.')
        ->set('cafeName', 'Corner Café')->set('city', 'Bulacan')->set('uploads', [$photo, $video])
        ->call('save')->assertHasNoErrors()->assertRedirect(route('home'));

    $post = Post::sole();
    expect($post->user_id)->toBe($user->id);
    $media = $post->media()->get();
    expect($media)->toHaveCount(2);
    expect($media[0]->disk)->toBe('cloudinary');
    expect($media[0]->path)->toStartWith('post/'.$post->id.'/img/');
    expect($media[1]->path)->toStartWith('post/'.$post->id.'/vid/');
    expect($media[0]->delivery_type)->toBe('authenticated');
    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/image/upload') && cloudinaryRequestData($request)['asset_folder'] === 'post/'.$post->id.'/img');
    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/video/upload') && cloudinaryRequestData($request)['asset_folder'] === 'post/'.$post->id.'/vid');
});

test('an upload failure removes prior remote assets and rolls back the post', function () {
    useCloudinaryForTests();
    $user = User::factory()->create();
    $attempts = 0;
    Http::fake(function (Request $request) use (&$attempts) {
        if (str_ends_with($request->url(), '/destroy')) {
            return Http::response(['result' => 'ok']);
        }
        $attempts++;

        return $attempts === 1
            ? Http::response(cloudinaryUploadReply($request))
            : Http::response(['error' => ['message' => 'Sensitive provider detail']], 403);
    });
    $photo = fn ($name) => UploadedFile::fake()->createWithContent($name, file_get_contents(public_path('images/cafe.jpg')));

    Livewire::actingAs($user)->test(CreatePost::class)
        ->set('title', 'A cloud coffee story')->set('body', 'A visit with friends.')
        ->set('cafeName', 'Corner Café')->set('city', 'Bulacan')->set('uploads', [$photo('one.jpg'), $photo('two.jpg')])
        ->call('save')->assertHasErrors('uploads')->assertDontSee('Sensitive provider detail');

    $this->assertDatabaseCount('posts', 0);
    $this->assertDatabaseCount('post_media', 0);
    expect(Http::recorded(fn (Request $request) => str_ends_with($request->url(), '/destroy')))->toHaveCount(2);
});

test('the media route proxies Cloudinary bytes without exposing provider credentials', function () {
    useCloudinaryForTests();
    $user = User::factory()->create();
    $post = $user->posts()->create(['author_handle' => 'coffee.user', 'title' => 'A visit', 'body' => 'A story', 'cafe_name' => 'Café', 'city' => 'Bulacan', 'visibility' => 'public']);
    $media = $post->media()->create([
        'disk' => 'cloudinary', 'path' => 'post/'.$post->id.'/vid/asset',
        'resource_type' => 'video', 'delivery_type' => 'authenticated', 'format' => 'mp4',
        'kind' => 'video', 'mime_type' => 'video/mp4', 'size_bytes' => 4, 'position' => 0,
    ]);
    Http::fake(['api.cloudinary.com/*' => Http::response('data', 206, ['Content-Range' => 'bytes 0-3/10', 'Accept-Ranges' => 'bytes', 'Content-Length' => '4'])]);

    $response = $this->withHeader('Range', 'bytes=0-3')->get(route('post-media.show', $media));

    $response->assertStatus(206)->assertStreamedContent('data')->assertHeader('Content-Type', 'video/mp4')
        ->assertHeader('Content-Range', 'bytes 0-3/10')->assertHeader('X-Content-Type-Options', 'nosniff');
    Http::assertSent(fn (Request $request) => $request->hasHeader('Range', 'bytes=0-3') && str_contains($request->url(), 'signature=') && ! str_contains($request->url(), 'test-secret'));
});

test('private Cloudinary media is blocked before contacting the provider', function () {
    useCloudinaryForTests();
    $user = User::factory()->create();
    $post = $user->posts()->create(['author_handle' => 'coffee.user', 'title' => 'A visit', 'body' => 'A story', 'cafe_name' => 'Café', 'city' => 'Bulacan', 'visibility' => 'private']);
    $media = $post->media()->create(['disk' => 'cloudinary', 'path' => 'post/1/img/asset', 'kind' => 'image', 'mime_type' => 'image/jpeg', 'size_bytes' => 1000, 'position' => 0]);
    Http::fake();

    $this->get(route('post-media.show', $media))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('post-media.show', $media))->assertNotFound();

    Http::assertNothingSent();
});

test('a provider outage produces a safe media error', function () {
    useCloudinaryForTests();
    config(['app.debug' => false]);
    $user = User::factory()->create();
    $post = $user->posts()->create(['author_handle' => 'coffee.user', 'title' => 'A visit', 'body' => 'A story', 'cafe_name' => 'Café', 'city' => 'Bulacan', 'visibility' => 'public']);
    $media = $post->media()->create(['disk' => 'cloudinary', 'path' => 'post/1/img/asset', 'resource_type' => 'image', 'delivery_type' => 'authenticated', 'format' => 'jpg', 'kind' => 'image', 'mime_type' => 'image/jpeg', 'size_bytes' => 1000, 'position' => 0]);
    Http::fake(['*' => Http::response(['error' => 'secret provider message'], 500)]);

    $this->get(route('post-media.show', $media))->assertStatus(502)->assertDontSee('secret provider message');
});

test('legacy fixed folder mode preserves the requested hierarchy', function () {
    useCloudinaryForTests();
    config(['services.cloudinary.folder_mode' => 'fixed']);
    Http::fake(function (Request $request) {
        $reply = cloudinaryUploadReply($request);
        $data = cloudinaryRequestData($request);
        $reply['public_id'] = $data['folder'].'/'.$data['public_id'];

        return Http::response($reply);
    });
    $file = UploadedFile::fake()->createWithContent('photo.jpg', file_get_contents(public_path('images/cafe.jpg')));

    $asset = app(CloudinaryMediaClient::class)->upload($file, 'post/42/img', 'image');

    expect($asset['path'])->toStartWith('post/42/img/');
    Http::assertSent(fn (Request $request) => (cloudinaryRequestData($request)['folder'] ?? null) === 'post/42/img' && ! isset(cloudinaryRequestData($request)['asset_folder']));
});

test('missing credentials do not attempt an upload', function () {
    useCloudinaryForTests();
    config(['services.cloudinary.api_secret' => null]);
    Http::fake();
    $file = UploadedFile::fake()->createWithContent('photo.jpg', file_get_contents(public_path('images/cafe.jpg')));

    expect(fn () => app(CloudinaryMediaClient::class)->upload($file, 'post/1/img', 'image'))->toThrow(RuntimeException::class, 'Cloudinary credentials are not configured.');
    Http::assertNothingSent();
});
