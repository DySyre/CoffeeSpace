<?php

namespace App\Livewire\Page;

use App\Models\Post;
use App\Rules\MatchingPhotoRatios;
use App\Rules\PostMediaUpload;
use App\Services\MediaInspector;
use App\Services\PlaceSearch;
use App\Services\PostMediaStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class AddExperience extends Component
{
    use WithFileUploads;

    public string $title = '';

    public string $body = '';

    public string $cafeName = '';

    public string $city = '';

    public string $visibility = 'public';

    public array $selectedAmenities = [];

    public array $amenityDetails = [];

    public string $placeQuery = '';

    #[Locked]
    public array $placeResults = [];

    public bool $placeSearchCompleted = false;

    public float|string|null $latitude = null;

    public float|string|null $longitude = null;

    public $upload = null;

    public array $uploads = [];

    public $amenityUpload = null;

    public array $amenityPhotos = [];

    public function searchPlaces(PlaceSearch $places): void
    {
        abort_unless(auth()->check(), 401);
        $this->placeResults = [];
        $this->placeSearchCompleted = false;
        $this->placeQuery = trim($this->placeQuery);
        $this->validateOnly('placeQuery', ['placeQuery' => ['required', 'string', 'min:3', 'max:200']]);
        if (RateLimiter::tooManyAttempts('place-search:user:'.auth()->id(), 15)) {
            $this->addError('placeQuery', 'Please wait a minute before searching again.');

            return;
        }
        RateLimiter::hit('place-search:user:'.auth()->id(), 60);
        try {
            $this->placeResults = $places->search($this->placeQuery);
            $this->placeSearchCompleted = true;
        } catch (Throwable) {
            $this->addError('placeQuery', 'Place search is unavailable. Try again shortly or use your current location.');
        }
    }

    public function previewPlace(int $index): void
    {
        abort_unless(auth()->check(), 401);
        abort_unless(isset($this->placeResults[$index]), 404);
        $place = $this->placeResults[$index];
        $this->cafeName = $place['name'];
        $this->city = $place['area'];
        $this->latitude = null;
        $this->longitude = null;
        $this->dispatch('post-place-preview', place: $place);
    }

    public function updatedUpload(): void
    {
        abort_unless(auth()->check(), 401);
        try {
            $this->validateOnly('upload', ['upload' => ['required', 'file', new PostMediaUpload]]);
            Validator::make(['uploads' => [...$this->uploads, $this->upload]], ['uploads' => [new MatchingPhotoRatios]])->validate();
        } catch (ValidationException $exception) {
            $this->dispatch('post-toast', kind: 'error', title: 'Upload rejected', message: $exception->validator->errors()->first());
            throw $exception;
        }
        $this->uploads[] = $this->upload;
        $this->reset('upload');
    }

    public function removeMedia(int $index): void
    {
        unset($this->uploads[$index]);
        $this->uploads = array_values($this->uploads);
        $this->resetValidation();
    }

    public function updatedAmenityUpload(): void
    {
        abort_unless(auth()->check(), 401);
        abort_unless(in_array('menu', $this->selectedAmenities, true), 422);

        if (count($this->amenityPhotos) >= 5) {
            $this->reset('amenityUpload');
            $this->addError('amenityPhotos', 'You can add up to 5 menu photos.');

            return;
        }

        try {
            $this->validateOnly('amenityUpload', [
                'amenityUpload' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240', new PostMediaUpload],
            ]);
        } catch (ValidationException $exception) {
            $this->dispatch('post-toast', kind: 'error', title: 'Menu photo rejected', message: $exception->validator->errors()->first());
            throw $exception;
        }

        $this->amenityPhotos[] = $this->amenityUpload;
        $this->reset('amenityUpload');
        $this->resetValidation('amenityPhotos');
    }

    public function removeAmenityPhoto(int $index): void
    {
        abort_unless(auth()->check(), 401);
        abort_unless(isset($this->amenityPhotos[$index]), 404);

        unset($this->amenityPhotos[$index]);
        $this->amenityPhotos = array_values($this->amenityPhotos);
        $this->resetValidation('amenityPhotos');
    }

    public function save(MediaInspector $inspector, PostMediaStorage $storage): void
    {
        abort_unless(auth()->check(), 401);
        abort_unless(auth()->user()->hasVerifiedEmail(), 403, 'Verify your email address before creating a post.');
        $this->title = trim($this->title);
        $this->body = trim($this->body);
        $this->cafeName = trim($this->cafeName);
        $this->city = trim($this->city);
        $this->latitude = $this->latitude === '' ? null : $this->latitude;
        $this->longitude = $this->longitude === '' ? null : $this->longitude;
        try {
            $this->validate([
                'title' => ['required', 'string', 'max:160'],
                'body' => ['required', 'string', 'max:3000'],
                'cafeName' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
                'visibility' => ['required', Rule::in(['public', 'followers', 'private'])],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'selectedAmenities' => ['array', 'max:5'],
                'selectedAmenities.*' => ['required', 'string', 'distinct', Rule::in(array_keys(Post::AMENITIES))],
                'amenityDetails' => ['array'],
                'uploads' => ['required', 'array', 'min:1', new MatchingPhotoRatios],
                'uploads.*' => ['required', 'file', new PostMediaUpload],
            ], ['latitude.required' => 'Attach a cafe pin before publishing.', 'longitude.required' => 'Attach a cafe pin before publishing.']);
            $detailRules = [];
            foreach ($this->selectedAmenities as $key) {
                $detailRules['amenityDetails.'.$key] = ['required', 'string', 'max:500'];
            }
            if ($detailRules) {
                $this->validate($detailRules);
            }
            if (in_array('menu', $this->selectedAmenities, true)) {
                $this->validate([
                    'amenityPhotos' => ['array', 'max:5'],
                    'amenityPhotos.*' => ['image', 'mimes:jpg,jpeg,png', 'max:10240', new PostMediaUpload],
                ]);
            }

        } catch (ValidationException $exception) {
            $this->dispatch('post-toast', kind: 'error', title: 'Check your post', message: $exception->validator->errors()->first());
            throw $exception;
        }

        $rateKey = 'create-post:'.auth()->id();
        if (RateLimiter::tooManyAttempts($rateKey, 1)) {
            $seconds = RateLimiter::availableIn($rateKey);
            $wait = $seconds >= 60
                ? ceil($seconds / 60).' '.(ceil($seconds / 60) === 1 ? 'minute' : 'minutes')
                : 'less than a minute';
            $message = 'You can publish another post in '.$wait.'.';
            $this->addError('title', $message);
            $this->dispatch('post-toast', kind: 'error', title: 'Please wait', message: $message);

            return;
        }

        $amenities = collect($this->selectedAmenities)->mapWithKeys(fn ($key) => [$key => trim($this->amenityDetails[$key])])->all();
        $amenityPhotos = in_array('menu', $this->selectedAmenities, true) ? $this->amenityPhotos : [];
        $storedPaths = [];
        try {
            DB::transaction(function () use ($inspector, $storage, &$storedPaths, $amenities, $amenityPhotos) {
                $user = auth()->user();
                $post = $user->posts()->create([
                    'author_handle' => Str::slug($user->name, '.') ?: 'coffee.explorer',
                    'title' => $this->title, 'body' => $this->body,
                    'cafe_name' => $this->cafeName, 'city' => $this->city,
                    'visibility' => $this->visibility, 'amenities' => $amenities,
                    'latitude' => $this->latitude, 'longitude' => $this->longitude,
                ]);

                foreach ($this->uploads as $position => $file) {
                    $metadata = $inspector->inspect($file);
                    $asset = $storage->store($file, $post, $metadata);
                    $storedPaths[] = $asset;
                    $post->media()->create([...$metadata, ...$asset, 'position' => $position]);
                }

                foreach ($amenityPhotos as $position => $file) {
                    $metadata = $inspector->inspect($file);
                    $asset = $storage->store($file, $post, $metadata);
                    $storedPaths[] = $asset;
                    $post->amenityMedia()->create([...$metadata, ...$asset, 'position' => $position, 'amenity_key' => 'menu']);
                }
            });
        } catch (Throwable $exception) {
            $storage->remove($storedPaths);
            report($exception);
            $this->addError('uploads', 'Your post could not be saved. Please try again; no post was published.');
            $this->dispatch('post-toast', kind: 'error', title: 'Post not saved', message: 'Your post could not be saved. Please try again; no post was published.');

            return;
        }

        RateLimiter::hit($rateKey, 300);
        session()->flash('post-status', match ($this->visibility) {
            'private' => 'Your private post has been saved. It will not appear in the public feed.',
            'followers' => 'Your post has been shared with your followers only.',
            default => 'Your post has been published.',
        });
        $this->redirectRoute('home', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.page.AddExperience')->layout('components.layouts.coffee', ['title' => 'Add Experience · CoffeeSpace', 'section' => 'post']);
    }
}
