<div class="mx-auto max-w-[640px] bg-surface lg:border lg:border-line">
    <form wire:submit="save" x-data="postComposer($wire)" x-effect="syncMedia($wire.uploads.length)"
        x-on:livewire-upload-start="uploading = true; progress = 0" x-on:livewire-upload-finish="uploading = false" x-on:livewire-upload-error="uploading = false" x-on:livewire-upload-cancel="uploading = false" x-on:livewire-upload-progress="progress = $event.detail.progress"
        x-on:submit.capture="if (uploading || step === 'media') { $event.preventDefault(); $event.stopImmediatePropagation(); }">
        <header class="sticky top-0 z-10 flex min-h-16 items-center justify-between gap-4 border-b border-line bg-surface px-4 lg:top-20">
            <a x-show="step === 'media'" href="{{ route('home') }}" wire:navigate aria-label="Close new post" class="flex size-11 items-center justify-center text-2xl">&times;</a>
            <button x-cloak x-show="step === 'details'" type="button" x-on:click="step = 'media'" class="min-h-11 text-sm text-brand">Back</button>
            <h1 class="flex-1 font-display text-xl font-semibold" x-text="step === 'media' ? 'New post' : 'Your coffee story'">New post</h1>
            <button x-show="step === 'media'" type="button" x-on:click="nextStep()" x-bind:disabled="!mediaCount || uploading" wire:loading.attr="disabled" wire:target="upload,removeMedia" class="min-h-11 px-2 font-semibold text-brand disabled:opacity-40">Next</button>
            <span x-cloak x-show="step === 'details'" class="text-xs text-muted">2 of 2</span>
        </header>
        <section x-show="step === 'media'" aria-label="Post media">
            <div class="relative aspect-square overflow-hidden bg-soft">
                @forelse($uploads as $index => $file)
                    <div wire:key="preview-{{ $file->getFilename() }}" x-show="activeMedia === {{ $index }}" class="absolute inset-0">
                        @if(in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png']))
                            <img src="{{ $file->temporaryUrl() }}" alt="Selected photo {{ $index + 1 }}" class="size-full object-contain">
                        @else
                            <video src="{{ $file->temporaryUrl() }}" controls playsinline preload="metadata" aria-label="Selected video {{ $index + 1 }}" class="size-full object-contain"></video>
                        @endif
                        <button type="button" wire:click="removeMedia({{ $index }})" x-bind:disabled="uploading" wire:loading.attr="disabled" wire:target="save,removeMedia" aria-label="Remove {{ $file->getClientOriginalName() }}" class="absolute top-3 right-3 min-h-11 rounded-sm bg-surface px-4 text-sm text-ink">Remove</button>
                        <span class="absolute bottom-3 left-3 rounded-sm bg-surface px-3 py-2 text-xs text-ink">{{ $index + 1 }} / {{ count($uploads) }}</span>
                    </div>
                @empty
                    <button type="button" x-on:click="$refs.library.click()" class="flex size-full flex-col items-center justify-center gap-4 px-8 text-center text-brand">
                        <x-coffee-icon name="image" class="size-10"/>
                        <span class="font-display text-2xl font-semibold">Start with a coffee moment</span>
                        <span class="max-w-xs text-sm leading-6 text-muted">Choose a photo or video, or open your camera below.</span>
                    </button>
                @endforelse
            </div>
            <div class="flex items-center justify-between gap-3 px-4 py-3">
                <h2 class="font-semibold">Your selection <span class="ml-1 text-sm font-normal text-muted" x-text="mediaCount ? '(' + mediaCount + ')' : ''"></span></h2>
                <button type="button" x-on:click="$refs.library.click()" x-bind:disabled="uploading" class="flex min-h-11 items-center gap-2 rounded-sm border border-line px-3 text-sm text-brand disabled:opacity-50"><x-coffee-icon name="image"/>Add media</button>
            </div>
            <div class="grid grid-cols-4 gap-0.5" aria-label="Selected media thumbnails">
                <button type="button" x-on:click="openCamera()" x-bind:disabled="uploading" class="flex aspect-square flex-col items-center justify-center gap-2 bg-soft text-brand disabled:opacity-50"><x-coffee-icon name="camera" class="size-7"/><span class="text-xs">Camera</span></button>
                @foreach($uploads as $index => $file)
                    <button wire:key="thumbnail-{{ $file->getFilename() }}" type="button" x-on:click="selectMedia({{ $index }})" x-bind:aria-pressed="activeMedia === {{ $index }}" aria-label="Preview media {{ $index + 1 }}" class="relative aspect-square overflow-hidden bg-soft">
                        @if(in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png']))
                            <img src="{{ $file->temporaryUrl() }}" alt="" class="size-full object-cover">
                        @else
                            <span class="flex size-full flex-col items-center justify-center gap-2 text-brand"><x-coffee-icon name="video"/><span class="text-xs">Video</span></span>
                        @endif
                        <span class="absolute inset-0 border-2" x-bind:class="activeMedia === {{ $index }} ? 'border-brand' : 'border-transparent'"></span>
                        <span class="absolute top-1 right-1 flex size-6 items-center justify-center rounded-full bg-brand text-xs text-on-brand">{{ $index + 1 }}</span>
                    </button>
                @endforeach
                <button type="button" x-on:click="recordOnDevice()" x-bind:disabled="uploading" class="flex aspect-square flex-col items-center justify-center gap-2 bg-soft text-brand disabled:opacity-50"><x-coffee-icon name="video" class="size-7"/><span class="text-xs">Record video</span></button>
            </div>
            <div class="px-4 py-4">
                <p id="upload-help" class="text-xs leading-5 text-muted">JPEG, PNG, MP4 or MOV / 10 MB per file / Videos up to 1 minute. Add files one at a time.</p>
                <p x-cloak x-show="uploading" role="status" class="mt-2 text-sm text-brand">Uploading... <span x-text="progress + '%'"></span></p>
                <p wire:loading wire:target="upload" class="mt-2 text-sm text-muted">Checking your file...</p>
                <p x-cloak x-show="mediaError" x-text="mediaError" role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300"></p>
                <p x-cloak x-show="cameraError && !$refs.cameraDialog.open" x-text="cameraError" role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300"></p>
                @error('upload')<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                @error('uploads')<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                @foreach($uploads as $index => $file)
                    @error('uploads.'.$index)<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                @endforeach
            </div>
        </section>
        <input id="post-upload" x-ref="library" wire:key="upload-picker-{{ count($uploads) }}" type="file" wire:model="upload" hidden accept=".jpg,.jpeg,.png,.mp4,.mov,image/jpeg,image/png,video/mp4,video/quicktime" aria-label="Choose a photo or video">
        <input x-ref="devicePhoto" wire:key="photo-capture-{{ count($uploads) }}" type="file" wire:model="upload" hidden accept="image/jpeg" capture="environment" aria-label="Take a device photo">
        <input x-ref="deviceVideo" wire:key="video-capture-{{ count($uploads) }}" type="file" wire:model="upload" hidden accept="video/mp4,video/quicktime" capture="environment" aria-label="Capture a device video">
            <dialog x-ref="cameraDialog" wire:ignore.self x-on:cancel.prevent="closeCamera()" aria-labelledby="camera-title" class="m-auto w-[min(92vw,640px)] rounded-md border border-line bg-surface p-5 text-ink backdrop:bg-black/60">
                <div class="mb-4 flex items-center justify-between gap-4"><h2 id="camera-title" class="font-display text-xl font-semibold">Capture your coffee moment</h2><button type="button" x-on:click="closeCamera()" class="min-h-11 px-3 text-sm text-muted">Close</button></div>
                <p x-show="cameraStarting" role="status" class="mb-3 text-sm text-muted">Waiting for camera permission…</p>
                <p x-show="cameraError" x-text="cameraError" role="alert" class="mb-3 text-sm text-red-700 dark:text-red-300"></p>
                <video x-ref="cameraPreview" wire:ignore autoplay muted playsinline class="aspect-video w-full rounded-sm bg-black object-contain" aria-label="Live camera preview"></video>
                <div class="mt-4 flex flex-wrap justify-between gap-3"><button type="button" x-on:click="closeCamera(); $refs.devicePhoto.click()" class="min-h-11 px-3 text-sm text-brand">Use device camera</button><button type="button" x-on:click="takePhoto()" x-bind:disabled="!cameraReady" class="flex min-h-11 items-center gap-2 rounded-sm bg-brand px-5 text-sm text-on-brand disabled:opacity-50"><x-coffee-icon name="camera"/>Take photo</button></div>
            </dialog>
        <fieldset x-cloak x-show="step === 'details'" x-bind:disabled="step !== 'details'" class="flex min-w-0 flex-col gap-6 p-4 sm:p-6">
        <div class="grid gap-5 sm:grid-cols-2">
            <flux:input wire:model="cafeName" label="Café name" maxlength="255" required />
            <flux:input wire:model="city" label="City / area" maxlength="255" required />
        </div>
        <section class="rounded-md border border-line bg-surface p-5 sm:p-6" aria-label="Optional location pin">
            <div class="flex flex-wrap items-start justify-between gap-4"><div><h2 class="font-display text-xl font-semibold">Pin the café <span class="font-sans text-sm font-normal text-muted">optional</span></h2><p class="mt-2 max-w-lg text-sm leading-6 text-muted">Use your location while you’re at the café. Review the pin before attaching it to your post.</p></div><button type="button" x-on:click="locate()" x-bind:disabled="locating" class="flex min-h-11 items-center gap-2 rounded-sm border border-line px-4 text-sm text-brand disabled:opacity-50"><x-coffee-icon name="map"/><span x-text="locating ? 'Finding location…' : 'Use my location'">Use my location</span></button></div>
            <p class="mt-3 text-xs leading-5 text-muted">One-time access only. We don’t track your location. An attached pin will be visible to the audience you choose for this post.</p>
            <p x-cloak x-show="locationError" x-text="locationError" role="alert" class="mt-3 text-sm text-red-700 dark:text-red-300"></p>
            <template x-if="candidate">
                <div class="mt-4" wire:ignore>
                    <iframe x-bind:src="mapUrl()" title="Preview of your location pin" class="h-60 w-full rounded-sm border border-line" referrerpolicy="no-referrer"></iframe>
                    <p class="mt-2 text-xs text-muted">© OpenStreetMap contributors · Approximate accuracy: <span x-text="candidate.accuracy"></span> m</p>
                    <p class="mt-2 text-sm text-muted"><span x-text="candidate.latitude"></span>, <span x-text="candidate.longitude"></span></p>
                    <div class="mt-3 flex flex-wrap items-center gap-4"><button type="button" x-on:click="attachPin()" x-bind:disabled="attachedPin" class="min-h-11 rounded-sm bg-brand px-4 text-sm text-on-brand disabled:opacity-60" x-text="attachedPin ? 'Pin attached' : 'Attach this pin'">Attach this pin</button><button type="button" x-on:click="removePin()" class="min-h-11 px-2 text-sm text-muted">Remove pin</button></div>
                </div>
            </template>
            @error('latitude')<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
            @error('longitude')<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
        </section>
        <flux:input wire:model="title" label="Title" maxlength="160" required />
        <flux:textarea wire:model="body" label="Your story" rows="5" maxlength="3000" required />
        <flux:select wire:model="visibility" label="Who can see this post?">
            <flux:select.option value="public">Public</flux:select.option>
            <flux:select.option value="private">Only me</flux:select.option>
        </flux:select>
        <div class="flex items-center justify-between gap-4 border-t border-line pt-6">
            <a href="{{ route('home') }}" wire:navigate class="min-h-11 py-3 text-sm text-muted">Cancel</a>
            <button type="submit" x-bind:disabled="uploading" wire:loading.attr="disabled" wire:target="save,upload,removeMedia" class="min-h-11 rounded-sm bg-brand px-6 py-3 text-sm font-medium text-on-brand disabled:opacity-50"><span wire:loading.remove wire:target="save">Publish post</span><span wire:loading wire:target="save">Saving your post…</span></button>
        </div>
        </fieldset>
    </form>
</div>
