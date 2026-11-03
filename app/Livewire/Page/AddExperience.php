<?php

namespace App\Livewire\Page;

use App\Rules\PostMediaUpload;
use App\Services\MediaInspector;
use App\Services\PostMediaStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public float|string|null $latitude = null;

    public float|string|null $longitude = null;

    public $upload = null;

    public array $uploads = [];

    public function updatedUpload(): void
    {
        abort_unless(auth()->check(), 401);
        $this->validateOnly('upload', ['upload' => ['required', 'file', new PostMediaUpload]]);
        $this->uploads[] = $this->upload;
        $this->reset('upload');
    }

    public function removeMedia(int $index): void
    {
        unset($this->uploads[$index]);
        $this->uploads = array_values($this->uploads);
        $this->resetValidation();
    }

    public function save(MediaInspector $inspector, PostMediaStorage $storage): void
    {
        abort_unless(auth()->check(), 401);
        $this->title = trim($this->title);
        $this->body = trim($this->body);
        $this->cafeName = trim($this->cafeName);
        $this->city = trim($this->city);
        $this->latitude = $this->latitude === '' ? null : $this->latitude;
        $this->longitude = $this->longitude === '' ? null : $this->longitude;
        $this->validate([
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:3000'],
            'cafeName' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'uploads' => ['required', 'array', 'min:1'],
            'uploads.*' => ['required', 'file', new PostMediaUpload],
        ]);

        $rateKey = 'create-post:'.auth()->id();
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $this->addError('title', 'Please wait a minute before publishing another post.');

            return;
        }

        $storedPaths = [];
        try {
            DB::transaction(function () use ($inspector, $storage, &$storedPaths) {
                $user = auth()->user();
                $post = $user->posts()->create([
                    'author_handle' => Str::slug($user->name, '.') ?: 'coffee.explorer',
                    'title' => $this->title, 'body' => $this->body,
                    'cafe_name' => $this->cafeName, 'city' => $this->city,
                    'visibility' => $this->visibility,
                    'latitude' => $this->latitude, 'longitude' => $this->longitude,
                ]);

                foreach ($this->uploads as $position => $file) {
                    $metadata = $inspector->inspect($file);
                    $asset = $storage->store($file, $post, $metadata);
                    $storedPaths[] = $asset;
                    $post->media()->create([...$metadata, ...$asset, 'position' => $position]);
                }
            });
        } catch (Throwable $exception) {
            $storage->remove($storedPaths);
            report($exception);
            $this->addError('uploads', 'Your post could not be saved. Please try again; no post was published.');

            return;
        }

        RateLimiter::hit($rateKey, 60);
        session()->flash('post-status', $this->visibility === 'private' ? 'Your private post has been saved. It will not appear in the public feed.' : 'Your post has been published.');
        $this->redirectRoute('home', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.page.AddExperience')->layout('components.layouts.coffee', ['title' => 'Add Experience · CoffeeSpace', 'section' => 'post']);
    }
}
