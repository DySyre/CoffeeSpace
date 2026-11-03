<?php

namespace App\Livewire\Page;

use App\Models\User;
use App\Rules\PostMediaUpload;
use App\Services\CloudinaryMediaClient;
use App\Services\PostMediaStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class MyProfile extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $bio = '';

    public $photo = null;

    public function mount(): void
    {
        $this->bio = auth()->user()->bio ?? User::DEFAULT_BIO;
    }

    public function saveBio(): void
    {
        abort_unless(auth()->check(), 401);
        $this->bio = trim($this->bio);
        $this->validateOnly('bio', ['bio' => ['nullable', 'string', 'max:160']]);
        $user = auth()->user();
        $user->bio = $this->bio;
        $user->save();
        $this->dispatch('bio-saved');
    }

    public function updatedPhoto(CloudinaryMediaClient $cloudinary, PostMediaStorage $storage): void
    {
        abort_unless(auth()->check(), 401);
        $this->validateOnly('photo', ['photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:10240', new PostMediaUpload]]);
        $user = auth()->user();
        $previous = $user->profile_photo;
        $asset = null;
        try {
            if (config('post-media.disk') === 'cloudinary') {
                $asset = $cloudinary->upload($this->photo, config('services.cloudinary.folders.user').'/'.$user->id.'/profile', 'image');
            } else {
                $path = $this->photo->store('user/'.$user->id.'/profile', 'local');
                if (! $path) {
                    throw new \RuntimeException('Profile photo could not be stored.');
                }
                $asset = ['disk' => 'local', 'path' => $path];
            }
            $asset['mime_type'] = $this->photo->getMimeType();
            $user->profile_photo = $asset;
            $user->save();
        } catch (Throwable) {
            if ($asset) {
                $storage->remove([$asset]);
            }
            $user->refresh();
            $this->addError('photo', 'Your photo could not be saved. Please try again.');

            return;
        }
        if ($previous) {
            $storage->remove([$previous]);
        }
        $this->reset('photo');
        $this->dispatch('profile-photo-saved');
    }

    public function render(): View
    {
        $user = auth()->user();
        $places = $user->posts()->select('cafe_name', 'city')->distinct();

        return view('livewire.page.MyProfile', [
            'user' => $user,
            'storyCount' => $user->posts()->count(),
            'placeCount' => DB::query()->fromSub($places->toBase(), 'visited_places')->count(),
            'posts' => $user->posts()->with('media')->latest('id')->paginate(12),
        ])->layout('components.layouts.coffee', ['title' => 'My Profile', 'section' => 'profile']);
    }
}
