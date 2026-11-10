    <dialog x-ref="cropDialog" wire:ignore.self x-on:cancel.prevent="closeCrop()" aria-labelledby="profile-crop-title" aria-describedby="profile-crop-help" class="m-auto max-h-[94dvh] w-[min(94vw,640px)] overflow-y-auto rounded-lg border border-line bg-surface p-4 text-ink backdrop:bg-black/60 sm:p-6">
        <div class="flex items-center justify-between gap-4"><h2 id="profile-crop-title" class="font-display text-xl font-semibold" x-text="cropKind === 'cover' ? 'Crop your cover photo' : cropKind === 'post' ? 'Crop photo for your post' : 'Crop your profile photo'">Crop your photo</h2><button type="button" x-cloak x-show="cropKind === 'post'" x-on:click="saveCrop()" x-bind:disabled="!cropReady || cropBusy" aria-label="Next photo" title="Save crop and continue" class="flex size-11 shrink-0 items-center justify-center rounded-md text-brand hover:bg-soft disabled:opacity-50"><x-coffee-icon name="arrow"/></button><button type="button" x-show="cropKind !== 'post'" x-on:click="closeCrop()" x-bind:disabled="cropBusy" class="min-h-11 shrink-0 px-2 text-sm text-muted disabled:opacity-50">Cancel crop</button></div>
        <p x-cloak x-show="cropKind === 'post'" x-text="cropKind === 'post' ? 'Media ' + batchPosition + ' of ' + batchTotal : ''" class="mt-1 text-xs text-muted"></p>
        <p id="profile-crop-help" class="mt-2 text-sm leading-6 text-muted">Drag to reposition. Adjust Zoom, or focus the preview and use the arrow keys.</p>
        <fieldset x-cloak x-show="cropKind === 'post'" x-bind:disabled="postRatioLocked || cropBusy" class="mt-4">
            <legend class="text-sm font-medium">Photo shape</legend>
            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach(['original' => 'Original', 'square' => 'Square 1:1', 'landscape' => 'Landscape 16:9', 'portrait' => 'Portrait 4:5'] as $ratio => $label)
                    <button type="button" title="{{ $ratio === 'original' ? 'Original (default)' : $label }}" x-on:click="setPostRatio('{{ $ratio }}')" x-bind:aria-pressed="postRatio === '{{ $ratio }}'" x-bind:class="postRatio === '{{ $ratio }}' ? 'border-brand bg-soft text-brand' : 'border-line text-muted'" class="min-h-11 rounded-sm border px-2 text-xs font-medium disabled:cursor-default">{{ $label }}</button>
                @endforeach
            </div>
            <p class="mt-2 text-xs leading-5 text-muted" x-text="postRatioLocked ? 'Shape is locked to match the first photo in this post.' : 'Choose once. Every photo in this post will use this shape.'"></p>
        </fieldset>
        <div x-show="cropReady" class="mt-4 overflow-hidden rounded-sm bg-soft">
            <canvas x-ref="cropCanvas" wire:ignore tabindex="0" role="img" aria-label="Draggable crop preview. Use arrow keys to reposition the photo." x-on:pointerdown="startDrag($event)" x-on:pointermove="drag($event)" x-on:pointerup="stopDrag()" x-on:pointercancel="stopDrag()" x-on:lostpointercapture="stopDrag()" x-on:keydown.left.prevent="nudge(-1, 0)" x-on:keydown.right.prevent="nudge(1, 0)" x-on:keydown.up.prevent="nudge(0, -1)" x-on:keydown.down.prevent="nudge(0, 1)" class="mx-auto block w-full touch-none cursor-grab focus-visible:outline-2 focus-visible:outline-brand active:cursor-grabbing" x-bind:style="cropFrameStyle"></canvas>
        </div>
        <div x-show="cropReady" class="mt-4">
            <button type="button" x-on:click="zoomExpanded = !zoomExpanded" x-bind:aria-expanded="zoomExpanded" aria-controls="profile-crop-zoom-panel" x-bind:disabled="cropBusy" class="flex min-h-11 items-center gap-2 rounded-sm border border-line px-3 text-sm text-brand disabled:opacity-50"><x-coffee-icon name="zoom" class="size-4"/><span x-text="zoomExpanded ? 'Hide zoom' : 'Zoom'">Zoom</span></button>
            <div id="profile-crop-zoom-panel" x-cloak x-bind:inert="!zoomExpanded" x-bind:aria-hidden="!zoomExpanded" x-bind:class="zoomExpanded ? 'crop-zoom-panel-open' : ''" class="crop-zoom-panel">
                <div class="min-h-0 overflow-hidden"><div class="pt-3">
                <label for="profile-crop-zoom" class="text-sm font-medium">Zoom level</label><input id="profile-crop-zoom" type="range" min="1" max="4" step="0.01" x-model="zoom" x-on:input="drawCrop()" x-bind:disabled="cropBusy || !zoomExpanded" class="mt-2 block min-h-11 w-full accent-brand">
                </div></div>
            </div>
        </div>
        <p x-cloak x-show="cropError" x-text="cropError" role="alert" class="mt-3 text-sm text-red-700 dark:text-red-300"></p>
        <div class="sticky bottom-3 z-10 mt-4 ml-auto flex w-fit items-center gap-2">
            <button type="button" x-cloak x-show="cropKind === 'post'" x-on:click="closeCrop()" x-bind:disabled="cropBusy" aria-label="Skip photo" title="Skip this photo" class="flex size-12 shrink-0 items-center justify-center rounded-md border border-line bg-surface text-muted shadow-md hover:text-brand focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand disabled:opacity-50"><x-coffee-icon name="circle-cancel"/></button>
            <button type="button" x-on:click="saveCrop()" x-bind:disabled="!cropReady || cropBusy" aria-label="Save cropped photo" title="Save cropped photo" class="flex size-12 shrink-0 items-center justify-center rounded-md bg-brand text-on-brand shadow-md hover:brightness-95 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand disabled:opacity-50"><x-coffee-icon name="save"/></button>
        </div>
        <x-photo-toast :inside="true"/>
    </dialog>
