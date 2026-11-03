<div class="mx-auto max-w-[760px]">
    <h1 class="font-display text-3xl font-semibold">Share a coffee story</h1>
    <p class="mt-3 text-base text-muted">A place worth visiting. A cup worth remembering.</p>

    <form wire:submit="save" x-data="{ uploading: false, progress: 0 }"
        x-on:livewire-upload-start="uploading = true; progress = 0" x-on:livewire-upload-finish="uploading = false" x-on:livewire-upload-error="uploading = false" x-on:livewire-upload-cancel="uploading = false" x-on:livewire-upload-progress="progress = $event.detail.progress"
        x-on:submit.capture="if (uploading) { $event.preventDefault(); $event.stopImmediatePropagation(); }" class="mt-8 flex flex-col gap-6">
        <section class="rounded-md border border-line bg-surface p-5 sm:p-6" aria-label="Post media">
            <h2 class="font-display text-xl font-semibold">Photos & videos</h2>
            <p id="upload-help" class="mt-2 text-sm leading-6 text-muted">JPEG, PNG, MP4 or MOV. Up to 10 MB each. Videos up to 60 seconds. Add files one at a time.</p>
            <div class="mt-4">
                <label for="post-upload" class="mb-2 block text-sm font-medium">Add a photo or video</label>
                <input id="post-upload" wire:key="upload-picker-{{ count($uploads) }}" type="file" wire:model="upload" x-bind:disabled="uploading" wire:loading.attr="disabled" wire:target="save" accept=".jpg,.jpeg,.png,.mp4,.mov,image/jpeg,image/png,video/mp4,video/quicktime" aria-describedby="upload-help" class="block w-full rounded-sm border border-line p-3 text-sm file:mr-4 file:cursor-pointer file:border-0 file:bg-soft file:px-3 file:py-2 file:text-brand">
                <p x-cloak x-show="uploading" role="status" class="mt-2 text-sm text-muted">Uploading… <span x-text="progress + '%'"></span></p>
                <p wire:loading wire:target="upload" class="mt-2 text-sm text-muted">Checking your file…</p>
            </div>
            @error('upload')<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
            @error('uploads')<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
            <div class="mt-4 flex flex-col gap-3">
                @foreach($uploads as $index => $file)
                    <div wire:key="selected-media-{{ $index }}-{{ $file->getFilename() }}" class="flex min-w-0 items-center gap-3 border-t border-line pt-3">
                        @if(in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png']))
                            <img src="{{ $file->temporaryUrl() }}" alt="Selected photo {{ $index + 1 }}" class="size-16 rounded-sm object-cover">
                        @else
                            <span class="flex size-16 shrink-0 items-center justify-center rounded-sm bg-soft text-xs text-brand">VIDEO</span>
                        @endif
                        <div class="min-w-0 flex-1"><p class="truncate text-sm">{{ $file->getClientOriginalName() }}</p><p class="mt-1 text-xs text-muted">{{ number_format($file->getSize() / 1024 / 1024, 2) }} MB</p></div>
                        <button type="button" wire:click="removeUpload({{ $index }})" x-bind:disabled="uploading" wire:loading.attr="disabled" wire:target="save" class="min-h-11 px-2 text-sm text-brand" aria-label="Remove {{ $file->getClientOriginalName() }}">Remove</button>
                    </div>
                    @error('uploads.'.$index)<p role="alert" class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                @endforeach
            </div>
        </section>

        <div class="grid gap-5 sm:grid-cols-2">
            <flux:input wire:model="cafeName" label="Café name" maxlength="255" required />
            <flux:input wire:model="city" label="City / area" maxlength="255" required />
        </div>
        <flux:input wire:model="title" label="Title" maxlength="160" required />
        <flux:textarea wire:model="body" label="Your story" rows="5" maxlength="3000" required />
        <flux:select wire:model="visibility" label="Who can see this post?">
            <flux:select.option value="public">Public</flux:select.option>
            <flux:select.option value="private">Only me</flux:select.option>
        </flux:select>
        <div class="flex items-center justify-between gap-4 border-t border-line pt-6">
            <a href="{{ route('home') }}" class="min-h-11 py-3 text-sm text-muted">Cancel</a>
            <button type="submit" x-bind:disabled="uploading" wire:loading.attr="disabled" wire:target="save,upload,removeUpload" class="min-h-11 rounded-sm bg-brand px-6 py-3 text-sm font-medium text-on-brand disabled:opacity-50"><span wire:loading.remove wire:target="save">Publish post</span><span wire:loading wire:target="save">Saving your post…</span></button>
        </div>
    </form>
</div>
