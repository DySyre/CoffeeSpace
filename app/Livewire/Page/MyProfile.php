<?php

namespace App\Livewire\Page;

use App\Models\Conversation;
use App\Models\Post;
use App\Models\User;
use App\Rules\PostMediaUpload;
use App\Services\CloudinaryMediaClient;
use App\Services\MediaInspector;
use App\Services\PostMediaStorage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class MyProfile extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Locked]
    public int $profileUserId;

    public string $postFilter = 'active';

    public string $tab = 'stories';

    public $photo = null;

    public $cover = null;

    #[Locked]
    public ?int $editingPostId = null;

    public string $editTitle = '';

    public string $editCafeName = '';

    public string $editCity = '';

    public string $editBody = '';

    public string $editVisibility = 'public';

    #[Locked]
    public array $removedMediaIds = [];

    public array $editUploads = [];

    public function mount(User $user): void
    {
        $profile = $user;
        abort_unless($profile, 404);
        $this->profileUserId = $profile->id;
    }

    private function authorizeOwner(): void
    {
        abort_unless(auth()->check() && auth()->id() === $this->profileUserId, 403);
    }

    public function toggleFollow(): void
    {
        abort_unless(auth()->check(), 401);
        abort_if(auth()->id() === $this->profileUserId, 403);
        $target = User::findOrFail($this->profileUserId);
        abort_if(auth()->user()->hasBlocked($target) || $target->hasBlocked(auth()->user()), 403);
        $following = auth()->user()->following();
        if ($following->whereKey($target->id)->exists()) {
            $following->detach($target->id);
        } else {
            $following->syncWithoutDetaching([$target->id]);
        }
    }

    public function toggleBlock(): void
    {
        abort_unless(auth()->check(), 401);
        abort_if(auth()->id() === $this->profileUserId, 403);

        $viewer = auth()->user();
        $target = User::findOrFail($this->profileUserId);

        if ($viewer->hasBlocked($target)) {
            $viewer->blockedUsers()->detach($target->id);

            return;
        }

        DB::transaction(function () use ($viewer, $target) {
            $viewer->blockedUsers()->syncWithoutDetaching([$target->id]);
            $viewer->following()->detach($target->id);
            $viewer->followers()->detach($target->id);
        });
    }

    private function ownedPost(int $postId): Post
    {
        $this->authorizeOwner();

        return auth()->user()->posts()->withTrashed()->findOrFail($postId);
    }

    public function updatePostAudience(int $postId, string $visibility): void
    {
        $post = $this->ownedPost($postId);
        abort_if($post->trashed(), 404);
        validator(['visibility' => $visibility], ['visibility' => ['required', Rule::in(['public', 'followers', 'private'])]])->validate();
        $post->visibility = $visibility;
        $post->save();
        $this->dispatch('post-managed', message: 'Post audience updated.');
    }

    public function archivePost(int $postId): void
    {
        $post = $this->ownedPost($postId);
        abort_if($post->trashed(), 404);
        $post->archived_at = now();
        $post->save();
        $this->dispatch('post-managed', message: 'Post archived. You can restore it from Archived.');
    }

    public function deletePost(int $postId): void
    {
        $post = $this->ownedPost($postId);
        $post->delete();
        $this->dispatch('post-managed', message: 'Post moved to Trash. Its files are preserved.');
    }

    public function restorePost(int $postId): void
    {
        $post = $this->ownedPost($postId);
        if ($post->trashed()) {
            $post->restore();
        }
        $post->archived_at = null;
        $post->save();
        $this->dispatch('post-managed', message: 'Post restored.');
    }

    public function editPost(int $postId): void
    {
        $post = $this->ownedPost($postId);
        abort_if($post->trashed(), 404);

        $this->editingPostId = $post->id;
        $this->editTitle = $post->title;
        $this->editCafeName = $post->cafe_name;
        $this->editCity = $post->city;
        $this->editBody = $post->body;
        $this->editVisibility = $post->visibility;
        $this->removedMediaIds = [];
        $this->reset('editUploads');
        $this->resetValidation();
        $this->dispatch('post-edit-opened');
    }

    public function toggleRemoveExistingMedia(int $mediaId): void
    {
        abort_unless($this->editingPostId !== null, 404);
        $post = $this->ownedPost($this->editingPostId);
        abort_if($post->trashed(), 404);
        abort_unless($post->media()->whereKey($mediaId)->exists(), 404);

        if (in_array($mediaId, $this->removedMediaIds, true)) {
            $this->removedMediaIds = array_values(array_filter(
                $this->removedMediaIds,
                fn (int $removedId) => $removedId !== $mediaId,
            ));

            return;
        }

        $this->removedMediaIds[] = $mediaId;
    }

    public function cancelEditPost(): void
    {
        $this->authorizeOwner();
        $this->editingPostId = null;
        $this->reset('editTitle', 'editCafeName', 'editCity', 'editBody', 'editVisibility', 'removedMediaIds', 'editUploads');
        $this->resetValidation();
        $this->dispatch('post-edit-closed');
    }

    public function savePostEdits(MediaInspector $inspector, PostMediaStorage $storage): void
    {
        abort_unless($this->editingPostId !== null, 404);
        $post = $this->ownedPost($this->editingPostId);
        abort_if($post->trashed(), 404);

        $this->editTitle = trim($this->editTitle);
        $this->editCafeName = trim($this->editCafeName);
        $this->editCity = trim($this->editCity);
        $this->editBody = trim($this->editBody);
        $validated = $this->validate([
            'editTitle' => ['required', 'string', 'max:160'],
            'editCafeName' => ['required', 'string', 'max:255'],
            'editCity' => ['required', 'string', 'max:255'],
            'editBody' => ['required', 'string', 'max:3000'],
            'editVisibility' => ['required', Rule::in(['public', 'followers', 'private'])],
            'editUploads' => ['array'],
            'editUploads.*' => ['required', 'file', new PostMediaUpload],
        ]);

        $removeIds = array_values(array_unique($this->removedMediaIds));
        $mediaToRemove = $removeIds ? $post->media()->whereIn('id', $removeIds)->get() : collect();
        abort_unless($mediaToRemove->count() === count($removeIds), 404);

        $remainingMediaCount = $post->media()->when($removeIds, fn ($media) => $media->whereNotIn('id', $removeIds))->count();
        $uploads = $validated['editUploads'] ?? [];
        if ($remainingMediaCount + count($uploads) === 0 && ! $post->image_path) {
            throw ValidationException::withMessages(['editUploads' => 'Keep at least one photo or video on the post.']);
        }

        $storedMedia = [];
        $storedAssets = [];
        try {
            foreach ($uploads as $file) {
                $metadata = $inspector->inspect($file);
                $asset = $storage->store($file, $post, $metadata);
                $storedAssets[] = $asset;
                $storedMedia[] = [...$metadata, ...$asset];
            }

            $removedAssets = [];
            DB::transaction(function () use ($post, $validated, $removeIds, $storedMedia, &$removedAssets) {
                $post->fill([
                    'title' => $validated['editTitle'],
                    'cafe_name' => $validated['editCafeName'],
                    'city' => $validated['editCity'],
                    'body' => $validated['editBody'],
                    'visibility' => $validated['editVisibility'],
                ])->save();

                if ($removeIds) {
                    $removed = $post->media()->whereIn('id', $removeIds)->get();
                    $removedAssets = $removed->map(fn ($media) => $media->getAttributes())->all();
                    $post->media()->whereIn('id', $removeIds)->delete();
                }

                $position = (int) ($post->media()->max('position') ?? -1) + 1;
                foreach ($storedMedia as $media) {
                    $post->media()->create([...$media, 'position' => $position++]);
                }
            });
        } catch (Throwable $exception) {
            $storage->remove($storedAssets);
            report($exception);
            $this->addError('editUploads', 'Your post could not be updated. Please try again.');

            return;
        }

        $storage->remove($removedAssets);
        $this->editingPostId = null;
        $this->reset('editTitle', 'editCafeName', 'editCity', 'editBody', 'editVisibility', 'removedMediaIds', 'editUploads');
        $this->resetValidation();
        $this->dispatch('post-managed', message: 'Post updated.');
    }

    public function updatedPostFilter(): void
    {
        $this->validateOnly('postFilter', ['postFilter' => ['required', Rule::in(['active', 'archived', 'trash'])]]);
        $this->resetPage();
    }

    public function updatedTab(): void
    {
        $this->resetPage();
    }

    public function updatedPhoto(CloudinaryMediaClient $cloudinary, PostMediaStorage $storage): void
    {
        $this->storeProfileImage('photo', 'profile_photo', 'profile', $cloudinary, $storage);
    }

    public function updatedCover(CloudinaryMediaClient $cloudinary, PostMediaStorage $storage): void
    {
        $this->storeProfileImage('cover', 'cover_photo', 'cover', $cloudinary, $storage);
    }

    private function storeProfileImage(string $field, string $column, string $folder, CloudinaryMediaClient $cloudinary, PostMediaStorage $storage): void
    {
        $this->authorizeOwner();
        try {
            $this->validateOnly($field, [$field => ['required', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:10240', new PostMediaUpload]]);
        } catch (ValidationException $exception) {
            $this->dispatch('profile-image-result', field: $field, success: false, message: $exception->validator->errors()->first($field));
            throw $exception;
        }
        $user = auth()->user();
        $previous = $user->{$column};
        $asset = null;
        try {
            if (config('post-media.disk') === 'cloudinary') {
                $asset = $cloudinary->upload($this->{$field}, config('services.cloudinary.folders.user').'/'.$user->id.'/'.$folder, 'image');
            } else {
                $path = $this->{$field}->store('user/'.$user->id.'/'.$folder, 'local');
                if (! $path) {
                    throw new \RuntimeException('Profile photo could not be stored.');
                }
                $asset = ['disk' => 'local', 'path' => $path];
            }
            $asset['mime_type'] = $this->{$field}->getMimeType();
            $user->{$column} = $asset;
            $user->save();
        } catch (Throwable) {
            if ($asset) {
                $storage->remove([$asset]);
            }
            $user->refresh();
            $this->addError($field, 'Your photo could not be saved. Please try again.');
            $this->dispatch('profile-image-result', field: $field, success: false, message: 'Your photo could not be saved. Please try again.');

            return;
        }
        if ($previous) {
            $storage->remove([$previous]);
        }
        $this->reset($field);
        $this->dispatch('profile-image-result', field: $field, success: true, message: $field === 'cover' ? 'Your cover photo has been updated.' : 'Your profile photo has been updated.');
        if ($field === 'photo') {
            $this->dispatch('profile-photo-saved', userId: $user->id, url: $user->profilePhotoUrl());
        } else {
            $this->dispatch('cover-photo-saved');
        }
    }

    public function render(): View
    {
        $user = User::findOrFail($this->profileUserId);
        $isOwner = auth()->id() === $user->id;
        $viewer = auth()->user();
        $blockedByViewer = $viewer && $viewer->hasBlocked($user);
        $blockedViewer = $viewer && $user->hasBlocked($viewer);
        abort_if(! $isOwner && $blockedViewer, 404);
        $existingConversationUuid = null;
        if ($viewer && ! $isOwner && ! $blockedByViewer && ! $blockedViewer) {
            $existingConversationUuid = Conversation::query()
                ->where('user_one_id', min($viewer->id, $user->id))
                ->where('user_two_id', max($viewer->id, $user->id))
                ->whereDoesntHave('userSettings', fn ($settings) => $settings
                    ->where('user_id', $viewer->id)
                    ->whereNotNull('deleted_at'))
                ->value('conversation_uuid');
        }
        $activePosts = $user->posts()->whereNull('archived_at')->when(! $isOwner, fn ($query) => $query->visibleTo(auth()->user()));
        if ($blockedByViewer) {
            $activePosts->whereRaw('1 = 0');
        }
        $posts = clone $activePosts;
        if ($isOwner && $this->tab === 'saved') {
            $posts = auth()->user()->savedPosts()->visibleTo(auth()->user())->whereNull('archived_at');
        } elseif ($isOwner && $this->postFilter === 'archived') {
            $posts = $user->posts()->whereNotNull('archived_at');
        } elseif ($isOwner && $this->postFilter === 'trash') {
            $posts = $user->posts()->onlyTrashed();
        }
        $places = (clone $activePosts)->whereNotNull('latitude')->whereNotNull('longitude')->select('cafe_name', 'city')->distinct();
        $editingPost = null;
        if ($isOwner && $this->editingPostId !== null) {
            $editingPost = $this->ownedPost($this->editingPostId)->load(['media', 'amenityMedia']);
            abort_if($editingPost->trashed(), 404);
        }

        return view('livewire.page.MyProfile', [
            'user' => $user,
            'editingPost' => $editingPost,
            'existingConversationUuid' => $existingConversationUuid,
            'isOwner' => $isOwner,
            'blockedByViewer' => (bool) $blockedByViewer,
            'blockedViewer' => (bool) $blockedViewer,
            'isFollowing' => auth()->check() && auth()->user()->following()->whereKey($user->id)->exists(),
            'followsViewer' => auth()->check() && $user->following()->whereKey(auth()->id())->exists(),
            'followerCount' => $blockedByViewer ? 0 : $user->followers()->count(),
            'followingCount' => $blockedByViewer ? 0 : $user->following()->count(),
            'storyCount' => (clone $activePosts)->count(),
            'placeCount' => DB::query()->fromSub($places->toBase(), 'visited_places')->count(),
            'posts' => $posts->with(['media', 'amenityMedia'])->latest('posts.id')->paginate(12),
        ])->layout('components.layouts.coffee', ['title' => $isOwner ? 'My Profile' : $user->name.' - CoffeeSpace', 'section' => 'profile']);
    }
}
