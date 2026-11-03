<?php

use App\Livewire\Page\AddExperience;
use App\Models\Post;
use App\Models\User;
use App\Rules\PostMediaUpload;
use App\Services\MediaInspector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Livewire;

function clipWithDuration(float $seconds): UploadedFile
{
    $data = file_get_contents(__DIR__.'/../Fixtures/video.mp4');
    $movie = strpos($data, 'mvhd');
    // The fixture uses a version-0 movie header: timescale then duration.
    $data = substr_replace($data, pack('N', 1000), $movie + 16, 4);
    $data = substr_replace($data, pack('N', (int) round($seconds * 1000)), $movie + 20, 4);

    return UploadedFile::fake()->createWithContent('visit.mp4', $data);
}

test('real mp4 and mov video tracks are accepted', function (string $extension) {
    $file = new UploadedFile(__DIR__.'/../Fixtures/video.'.$extension, 'visit.'.$extension, test: true);

    $metadata = app(MediaInspector::class)->inspect($file);

    expect($metadata['kind'])->toBe('video');
    expect($metadata['duration_seconds'])->toBeGreaterThan(0)->toBeLessThanOrEqual(60);
})->with(['mp4', 'mov']);

test('a 60 second video passes and anything longer fails', function (float $seconds, bool $passes) {
    $file = clipWithDuration($seconds);

    $validator = Validator::make(['upload' => $file], ['upload' => ['file', new PostMediaUpload]]);

    expect($validator->passes())->toBe($passes);
})->with([[60.0, true], [60.001, false], [61.0, false]]);

test('an empty video container or audio only mp4 is rejected', function (string $fixture) {
    $file = new UploadedFile(__DIR__.'/../Fixtures/'.$fixture, 'visit.mp4', test: true);

    $validator = Validator::make(['upload' => $file], ['upload' => ['file', new PostMediaUpload]]);

    expect($validator->fails())->toBeTrue();
})->with(['sample.mp4', 'sample-with-track.mp4']);

test('a video post stores its duration with the media record', function () {
    Storage::fake('local');
    $user = User::factory()->create();
    $video = UploadedFile::fake()->createWithContent('visit.mp4', file_get_contents(__DIR__.'/../Fixtures/video.mp4'));

    Livewire::actingAs($user)->test(AddExperience::class)
        ->set('title', 'A video visit')->set('body', 'A short café clip.')
        ->set('cafeName', 'Coffee Corner')->set('city', 'Bulacan')->set('uploads', [$video])
        ->call('save')->assertHasNoErrors();

    $media = Post::sole()->media()->sole();
    expect($media->kind)->toBe('video');
    expect((float) $media->duration_seconds)->toBe(4.0);
    Storage::disk('local')->assertExists($media->path);
});
