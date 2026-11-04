<div class="mx-auto max-w-[640px] bg-surface lg:border lg:border-line">
    @if(auth()->check() && auth()->user()->hasVerifiedEmail())
    <form wire:submit="save" x-data="postComposer($wire)" x-effect="syncMedia($wire.uploads.length)" x-on:post-place-preview.window="previewPlace($event.detail.place)" x-on:post-toast.window="showToast($event.detail.kind, $event.detail.message, $event.detail.title); if ($event.detail.message.toLowerCase().includes('pin')) pinOpen = true"
        x-on:livewire-upload-start="uploading = true; progress = 0; if ($event.detail.property === 'amenityUpload') showToast('loading', 'Uploading menu photo...', 'Adding menu photo')" x-on:livewire-upload-finish="uploading = false; if ($event.detail.property === 'amenityUpload') showToast('success', 'Menu photo is ready to add to your post.', 'Menu photo added')" x-on:livewire-upload-error="uploading = false; if ($event.detail.property === 'amenityUpload') showToast('error', 'The menu photo could not be uploaded. Please try again.', 'Upload failed')" x-on:livewire-upload-cancel="uploading = false" x-on:livewire-upload-progress="progress = $event.detail.progress; if ($event.detail.property === 'amenityUpload') toastMessage = 'Uploading menu photo... ' + progress + '%'"
        x-on:submit.capture="if (uploading || batchBusy || step === 'media') { $event.preventDefault(); $event.stopImmediatePropagation(); } else { showToast('loading', 'Saving your photos and videos. Please keep this page open.', 'Saving post'); }">
        <header class="sticky top-0 z-10 flex min-h-16 items-center justify-between gap-4 border-b border-line bg-surface px-4 lg:top-20">
            <a x-show="step === 'media'" href="{{ route('home') }}" wire:navigate aria-label="Close new post" class="flex size-11 items-center justify-center text-2xl">&times;</a>
            <button x-cloak x-show="step === 'details'" type="button" x-on:click="step = 'media'" class="min-h-11 text-sm text-brand">Back</button>
            <h1 class="flex-1 font-display text-xl font-semibold" x-text="step === 'media' ? 'New post' : 'Your coffee story'">New post</h1>
            <button x-show="step === 'media'" type="button" x-on:click="nextStep()" x-bind:disabled="!mediaCount || uploading || batchBusy" wire:loading.attr="disabled" wire:target="upload,removeMedia" class="min-h-11 px-2 font-semibold text-brand disabled:opacity-40">Next</button>
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
                        <button type="button" wire:click="removeMedia({{ $index }})" x-bind:disabled="uploading || batchBusy" wire:loading.attr="disabled" wire:target="save,removeMedia" aria-label="Remove {{ $file->getClientOriginalName() }}" class="absolute top-3 right-3 min-h-11 rounded-sm bg-surface px-4 text-sm text-ink">Remove</button>
                        <span class="absolute bottom-3 left-3 rounded-sm bg-surface px-3 py-2 text-xs text-ink">{{ $index + 1 }} / {{ count($uploads) }}</span>
                    </div>
                @empty
                    <button type="button" x-on:click="$refs.library.click()" class="flex size-full flex-col items-center justify-center gap-4 px-8 text-center text-brand">
                        <x-coffee-icon name="image" class="size-10"/>
                        <span class="font-display text-2xl font-semibold">Add photos or videos</span>
                        <span class="max-w-xs text-sm leading-6 text-muted">Choose recent media from your device. Your browser will ask you to select what to share.</span>
                    </button>
                @endforelse
            </div>
            <div class="flex items-center justify-between gap-3 px-4 py-3">
                <div>
                    <h2 class="font-semibold">Selected media <span class="ml-1 text-sm font-normal text-muted" x-text="mediaCount ? '(' + mediaCount + ')' : ''"></span></h2>
                    <p class="mt-1 text-xs text-muted">Only the photos and videos you choose are shared with this post.</p>
                </div>
                <button type="button" x-on:click="$refs.library.click()" x-bind:disabled="uploading || batchBusy" class="flex min-h-11 shrink-0 items-center gap-2 rounded-sm border border-line px-3 text-sm text-brand disabled:opacity-50"><x-coffee-icon name="image"/>Browse recent media</button>
            </div>
            <div class="grid max-h-72 grid-cols-3 gap-2 overflow-y-auto overscroll-contain px-4 pb-4 sm:grid-cols-4" aria-label="Scrollable selected photo and video thumbnails" tabindex="0">
                <button type="button" x-on:click="openCamera()" x-bind:disabled="uploading || batchBusy" class="flex aspect-square flex-col items-center justify-center gap-2 rounded-sm bg-soft text-brand disabled:opacity-50"><x-coffee-icon name="camera" class="size-7"/><span class="text-xs">Camera</span></button>
                @foreach($uploads as $index => $file)
                    <button wire:key="thumbnail-{{ $file->getFilename() }}" type="button" x-on:click="selectMedia({{ $index }})" x-bind:aria-pressed="activeMedia === {{ $index }}" aria-label="Preview {{ in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png']) ? 'photo' : 'video' }} {{ $index + 1 }}" class="relative aspect-square overflow-hidden rounded-sm bg-soft">
                        @if(in_array(strtolower($file->getClientOriginalExtension()), ['jpg', 'jpeg', 'png']))
                            <img src="{{ $file->temporaryUrl() }}" alt="" class="size-full object-cover">
                            <span class="absolute bottom-1 left-1 rounded-sm bg-surface/90 px-2 py-1 text-[10px] font-medium text-ink">Photo</span>
                        @else
                            <span class="flex size-full flex-col items-center justify-center gap-2 text-brand"><x-coffee-icon name="video" class="size-8"/><span class="text-xs">Video preview</span></span>
                            <span class="absolute bottom-1 left-1 rounded-sm bg-surface/90 px-2 py-1 text-[10px] font-medium text-ink">Video</span>
                        @endif
                        <span class="absolute inset-0 border-2" x-bind:class="activeMedia === {{ $index }} ? 'border-brand' : 'border-transparent'"></span>
                        <span class="absolute top-1 right-1 flex size-6 items-center justify-center rounded-full bg-brand text-xs text-on-brand">{{ $index + 1 }}</span>
                    </button>
                @endforeach
                <button type="button" x-on:click="recordOnDevice()" x-bind:disabled="uploading || batchBusy" class="flex aspect-square flex-col items-center justify-center gap-2 rounded-sm bg-soft text-brand disabled:opacity-50"><x-coffee-icon name="video" class="size-7"/><span class="text-xs">Record video</span></button>
            </div>
            <div class="px-4 py-4">
                <p id="upload-help" class="text-xs leading-5 text-muted">Photos: JPEG/PNG up to 10 MB. Videos: MP4/MOV up to 25 MB and 1 minute. Select multiple photos and videos. Crop each photo before uploading.</p>
                <p x-cloak x-show="mediaError" x-text="mediaError" role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300"></p>
                <p x-cloak x-show="cameraError && !$refs.cameraDialog.open" x-text="cameraError" role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300"></p>
                @error('upload')<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                @error('uploads')<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                @foreach($uploads as $index => $file)
                    @error('uploads.'.$index)<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                @endforeach
            </div>
        </section>
        <input id="post-upload" x-ref="library" multiple wire:key="upload-picker-{{ count($uploads) }}" type="file" x-on:change="selectFiles($event)" hidden accept=".jpg,.jpeg,.png,.mp4,.mov,image/jpeg,image/png,video/mp4,video/quicktime" aria-label="Choose a photo or video">
        <input x-ref="devicePhoto" wire:key="photo-capture-{{ count($uploads) }}" type="file" wire:model="upload" hidden accept="image/jpeg" capture="environment" aria-label="Take a device photo">
        <input x-ref="deviceVideo" wire:key="video-capture-{{ count($uploads) }}" type="file" wire:model="upload" hidden accept="video/mp4,video/quicktime" capture="environment" aria-label="Capture a device video">
            <dialog x-ref="cameraDialog" wire:ignore.self x-on:cancel.prevent="closeCamera()" aria-labelledby="camera-title" class="m-auto w-[min(92vw,640px)] rounded-lg border border-line bg-surface p-5 text-ink backdrop:bg-black/60">
                <div class="mb-4 flex items-center justify-between gap-4"><h2 id="camera-title" class="font-display text-xl font-semibold">Capture your coffee moment</h2><button type="button" x-on:click="closeCamera()" class="min-h-11 px-3 text-sm text-muted">Close</button></div>
                <p x-show="cameraStarting" role="status" class="mb-3 text-sm text-muted">Waiting for camera permission…</p>
                <p x-show="cameraError" x-text="cameraError" role="alert" class="mb-3 text-sm text-red-700 dark:text-red-300"></p>
                <video x-ref="cameraPreview" wire:ignore autoplay muted playsinline class="aspect-video w-full rounded-sm bg-black object-contain" aria-label="Live camera preview"></video>
                <div class="mt-4 flex flex-wrap justify-between gap-3"><button type="button" x-on:click="closeCamera(); $refs.devicePhoto.click()" class="min-h-11 px-3 text-sm text-brand">Use device camera</button><button type="button" x-on:click="takePhoto()" x-bind:disabled="!cameraReady" class="flex min-h-11 items-center gap-2 rounded-sm bg-brand px-5 text-sm text-on-brand disabled:opacity-50"><x-coffee-icon name="camera"/>Take photo</button></div>
            </dialog>
        <fieldset x-cloak x-show="step === 'details'" x-bind:disabled="step !== 'details'" class="flex min-w-0 flex-col gap-6 p-4 sm:p-6">
        <details wire:ignore.self x-bind:open="pinOpen" x-on:toggle="pinOpen = $el.open" class="rounded-md border border-line border-t-[6px] border-t-brand bg-surface p-5 sm:p-6" aria-label="Cafe location pin">
            <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-3 font-display text-lg font-semibold [&::-webkit-details-marker]:hidden"><span>Pin the caf&eacute;<span x-show="attachedPin" class="ml-2 font-sans text-xs font-normal text-brand">Attached</span></span><x-coffee-icon name="chevron-right" x-show="!pinOpen"/><x-coffee-icon name="chevron-down" x-cloak x-show="pinOpen"/></summary>
            <div class="mt-3">
            <div class="flex flex-wrap items-start justify-between gap-4"><div><p class="mt-2 max-w-lg text-sm leading-6 text-muted"><i data-lucide="map-pin" class="mr-1 inline-block size-4"></i>Find and attach a cafe pin.</p></div><button type="button" x-on:click="locate()" x-bind:disabled="locating" class="flex min-h-11 items-center gap-2 rounded-sm border border-line px-4 text-sm text-brand disabled:opacity-50"><x-coffee-icon name="map"/><span x-text="locating ? 'Finding location…' : 'Use my location'">Use my location</span></button></div>
            <div class="mt-5">
                <label for="post-place-search" class="block text-sm font-medium">Search for a place</label>
                <div class="mt-2 flex gap-2"><input id="post-place-search" wire:model="placeQuery" x-on:keydown.enter.prevent="$wire.searchPlaces()" type="search" maxlength="200" placeholder="Cafe name, city or address" class="min-w-0 flex-1 rounded-sm border border-line bg-surface px-3 py-2 text-base text-ink sm:text-sm"><button type="button" wire:click="searchPlaces" wire:loading.attr="disabled" wire:target="searchPlaces" aria-label="Search places" title="Search places" class="flex size-11 shrink-0 items-center justify-center rounded-sm bg-brand text-on-brand disabled:opacity-50"><x-coffee-icon name="search"/></button></div>
                <p wire:loading wire:target="searchPlaces" role="status" class="mt-2 text-xs text-muted">Searching places...</p>
                @error('placeQuery')<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                @if(count($placeResults))
                    <ul class="mt-3 divide-y divide-line border border-line">
                        @foreach($placeResults as $index => $place)
                            <li wire:key="place-{{ $index }}-{{ md5($place['name'].$place['latitude']) }}"><button type="button" wire:click="previewPlace({{ $index }})" class="block min-h-11 w-full px-3 py-3 text-left hover:bg-soft"><span class="block text-sm font-medium">{{ $place['name'] }}</span><span class="mt-1 block text-xs leading-5 text-muted">{{ $place['address'] }}</span></button></li>
                        @endforeach
                    </ul>
                @elseif($placeSearchCompleted)<p role="status" class="mt-3 text-sm text-muted">No places found. Try adding a city or a more specific name.</p>@endif
                <p class="mt-2 text-xs text-muted">Search powered by <a href="https://photon.komoot.io" target="_blank" rel="noopener" class="underline">Photon</a>. Map data &copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener" class="underline">OpenStreetMap contributors</a>. Review a result, then attach its pin.</p>
            </div>
            <p x-cloak x-show="locationError" x-text="locationError" role="alert" class="mt-3 text-sm text-red-700 dark:text-red-300"></p>
            <template x-if="candidate">
                <div class="mt-4" wire:ignore>
                    <iframe x-bind:src="mapUrl()" title="Preview of your location pin" class="h-60 w-full rounded-sm border border-line" referrerpolicy="no-referrer"></iframe>
                    <p class="mt-2 text-xs text-muted">© OpenStreetMap contributors · <span x-show="candidate.accuracy !== null">Approximate accuracy: <span x-text="candidate.accuracy"></span> m</span><span x-show="candidate.accuracy === null" x-text="candidate.name"></span></p>
                    <p class="mt-2 text-sm text-muted"><span x-text="candidate.latitude"></span>, <span x-text="candidate.longitude"></span></p>
                    <div class="mt-3 flex flex-wrap items-center gap-4"><button type="button" x-on:click="attachPin()" x-bind:disabled="attachedPin" class="min-h-11 rounded-sm bg-brand px-4 text-sm text-on-brand disabled:opacity-60" x-text="attachedPin ? 'Pin attached' : 'Attach this pin'">Attach this pin</button><button type="button" x-on:click="removePin()" class="min-h-11 px-2 text-sm text-muted">Remove pin</button></div>
                </div>
            </template>
            @error('latitude')<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
            @error('longitude')<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
            </div>
        </details>
        <flux:select wire:model="visibility" label="Who can see this post?">
            <flux:select.option value="public">Public</flux:select.option>
            <flux:select.option value="followers">Followers only</flux:select.option>
            <flux:select.option value="private">Only me</flux:select.option>
        </flux:select>
        <div class="grid gap-5 sm:grid-cols-2">
            <flux:input wire:model="cafeName" label="Café name" maxlength="255" required />
            <flux:input wire:model="city" label="City / area" maxlength="255" required />
        </div>

        <flux:input wire:model="title" label="Title" maxlength="160" required />
        <flux:textarea wire:model="body" label="Your story" rows="5" maxlength="3000" required />
        <fieldset x-data="{ selected: $wire.entangle('selectedAmenities') }" class="border-t border-line pt-5">
            <legend class="font-display text-lg font-semibold">Post tags</legend>
            <p class="mt-2 text-sm text-muted">Tick what you checked and add useful details.</p>
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach(\App\Models\Post::AMENITIES as $key => $definition)
                    <label class="flex min-h-11 cursor-pointer items-center gap-2 rounded-sm border border-line px-3 text-sm text-brand has-checked:border-brand has-checked:bg-soft"><input type="checkbox" x-model="selected" value="{{ $key }}" class="size-4 accent-brand"><x-coffee-icon :name="$definition['icon']" class="size-4"/>{{ $definition['label'] }}</label>
                @endforeach
            </div>
            @error('selectedAmenities')<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
            @error('selectedAmenities.*')<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
            @foreach(\App\Models\Post::AMENITIES as $key => $definition)
                <div x-cloak x-show="selected.includes('{{ $key }}')" class="mt-4">
                    <label for="post-tag-{{ $key }}" class="block text-sm font-medium">{{ $definition['label'] }} details</label>
                    <textarea id="post-tag-{{ $key }}" wire:model="amenityDetails.{{ $key }}" rows="2" maxlength="500" x-bind:required="selected.includes('{{ $key }}')" x-bind:disabled="!selected.includes('{{ $key }}')" placeholder="{{ $definition['hint'] }}" class="mt-2 w-full rounded-sm border border-line bg-surface p-3 text-sm text-ink focus:border-brand focus:outline-2 focus:outline-brand"></textarea>
                    @error('amenityDetails.'.$key)<p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                    @if($key === 'menu')
                        <div class="mt-4 rounded-sm border border-line bg-page p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="flex items-center gap-2 text-sm font-medium"><x-coffee-icon name="image" class="size-4 text-brand"/>Menu photos <span class="text-xs font-normal text-muted">({{ count($amenityPhotos) }} / 5)</span></p>
                                    <p class="mt-1 text-xs leading-5 text-muted">Add up to 5 photos of the cafe menu. JPEG or PNG, up to 10 MB each.</p>
                                </div>
                                <button type="button" x-on:click="$refs.menuPhoto.click()" x-bind:disabled="$wire.amenityPhotos.length >= 5 || uploading" wire:loading.attr="disabled" wire:target="amenityUpload" class="inline-flex min-h-11 items-center gap-2 rounded-sm border border-line px-3 text-sm text-brand disabled:opacity-50"><x-coffee-icon name="image" class="size-4"/>Add photo</button>
                            </div>
                            <input x-ref="menuPhoto" type="file" wire:model="amenityUpload" accept=".jpg,.jpeg,.png,image/jpeg,image/png" x-bind:disabled="$wire.amenityPhotos.length >= 5" class="hidden" aria-label="Choose a photo of the menu">
                            <p wire:loading wire:target="amenityUpload" role="status" class="mt-3 text-xs text-muted">Adding menu photo…</p>
                            @error('amenityUpload')<p role="alert" class="mt-3 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                            @error('amenityPhotos')<p role="alert" class="mt-3 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                            @error('amenityPhotos.*')<p role="alert" class="mt-3 text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
                            @if(count($amenityPhotos))
                                <div class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-5" aria-label="Selected menu photos">
                                    @foreach($amenityPhotos as $photoIndex => $photo)
                                        <div wire:key="menu-photo-{{ $photo->getFilename() }}" class="relative aspect-square overflow-hidden rounded-sm bg-soft">
                                            <img src="{{ $photo->temporaryUrl() }}" alt="Menu photo {{ $photoIndex + 1 }} preview" class="size-full object-cover">
                                            <span class="absolute bottom-1 left-1 rounded-sm bg-surface/90 px-2 py-1 text-[10px] font-medium text-ink">Photo {{ $photoIndex + 1 }}</span>
                                            <button type="button" wire:click="removeAmenityPhoto({{ $photoIndex }})" wire:loading.attr="disabled" wire:target="amenityUpload,removeAmenityPhoto" aria-label="Remove menu photo {{ $photoIndex + 1 }}" class="absolute top-1 right-1 flex size-8 items-center justify-center rounded-full bg-surface text-sm text-ink">&times;</button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </fieldset>

        @error('uploads')<p role="alert" class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
        @error('upload')<p role="alert" class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>@enderror
        <div class="flex items-center justify-between gap-4 border-t border-line pt-6">
            <a href="{{ route('home') }}" wire:navigate class="min-h-11 py-3 text-sm text-muted">Cancel</a>
            <button type="submit" x-bind:disabled="uploading || batchBusy" wire:loading.attr="disabled" wire:target="save,upload,removeMedia,amenityUpload,removeAmenityPhoto" class="min-h-11 rounded-sm bg-brand px-6 py-3 text-sm font-medium text-on-brand disabled:opacity-50">Publish post</button>
        </div>
        </fieldset>
        <x-image-crop-modal/>
        <x-photo-toast/>
    </form>
    @else
        <section class="p-6 text-center sm:p-10">
            <x-coffee-icon name="lock" class="mx-auto size-8 text-brand"/>
            <h1 class="mt-4 font-display text-xl font-semibold">Verify your email to post</h1>
            <p class="mt-2 text-sm leading-6 text-muted">Confirm your email address before sharing a coffee story.</p>
            <a href="{{ route('verification.notice') }}" wire:navigate class="mt-5 inline-flex min-h-11 items-center justify-center rounded-sm bg-gradient-to-r from-black via-zinc-900 to-zinc-700 px-5 text-sm font-semibold text-white">Verify email</a>
        </section>
    @endif
</div>
