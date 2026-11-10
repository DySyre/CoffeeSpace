@props(['media', 'title', 'likePostId' => null])
<section x-data="{
    active: 0,
    photoLikeVisible: false,
    arrowOpacity: 1,
    mediaToPreload: @js(collect($media)->map(fn ($item) => ['url' => $item['url'], 'kind' => $item['kind'], 'mime_type' => $item['mime_type'] ?? null])->values()),
    preloadedMedia: {},
    touchStartX: null,
    touchStartY: 0,
    init() {
        this.scheduleArrowFade();
        if (typeof IntersectionObserver === 'undefined') {
            if (!this.$el.closest('dialog') || this.$el.closest('dialog').open) this.preloadNext();
            return;
        }
        const observer = new IntersectionObserver(entries => {
            if (entries.some(entry => entry.isIntersecting)) {
                this.preloadNext();
                observer.disconnect();
            }
        }, { rootMargin: '250px' });
        observer.observe(this.$el);
    },
    showMedia(index) {
        this.$refs.items.querySelectorAll('video').forEach(video => video.pause());
        this.active = (index + this.mediaToPreload.length) % this.mediaToPreload.length;
        this.scheduleArrowFade();
        this.$nextTick(() => this.preloadNext());
    },
    scheduleArrowFade() {
        this.arrowOpacity = 1;
        clearTimeout(this.arrowFadeTimer);
        this.arrowFadeTimer = setTimeout(() => this.arrowOpacity = .75, 3000);
    },
    startSwipe(event) {
        if (event.touches.length !== 1) return;
        this.touchStartX = event.touches[0].clientX;
        this.touchStartY = event.touches[0].clientY;
    },
    endSwipe(event) {
        if (this.touchStartX === null) return;
        const deltaX = event.changedTouches[0].clientX - this.touchStartX;
        const deltaY = event.changedTouches[0].clientY - this.touchStartY;
        this.touchStartX = null;
        if (Math.abs(deltaX) < 48 || Math.abs(deltaX) < Math.abs(deltaY) * 1.2) return;
        this.showMedia(this.active + (deltaX < 0 ? 1 : -1));
    },
    preloadNext() {
        if (this.mediaToPreload.length < 2) return;
        const index = (this.active + 1) % this.mediaToPreload.length;
        if (this.preloadedMedia[index]) return;
        const item = this.mediaToPreload[index];
        const slide = this.$refs.items.querySelectorAll('[data-media-index]')[index];
        if (item.kind === 'image') {
            const image = slide?.querySelector('img');
            if (image) image.loading = 'eager';
            this.preloadedMedia[index] = true;
            return;
        }
        const video = slide?.querySelector('video');
        if (video) {
            video.preload = 'auto';
            video.load();
        }
        this.preloadedMedia[index] = true;
    }
}" class="relative bg-soft" aria-label="Post photos and videos">
    <div x-ref="items" class="relative touch-pan-y" x-on:touchstart.passive="startSwipe($event)" x-on:touchend.passive="endSwipe($event)" x-on:touchcancel="touchStartX = null">
        @foreach($media as $index => $item)
            <div data-media-index="{{ $index }}" x-show="active === {{ $index }}" @if($index > 0) x-cloak @endif>
                @if($item['kind'] === 'image')
                    <div class="relative" @if($likePostId) x-on:dblclick.prevent.stop="if (!photoLikeVisible) { photoLikeVisible = true; $wire.likePost({{ $likePostId }}); setTimeout(() => photoLikeVisible = false, 620) }" @endif>
                        <img src="{{ $item['url'] }}" alt="{{ $title }} &mdash; photo {{ $index + 1 }}" loading="lazy" draggable="false" class="h-auto w-full select-none object-contain">
                        @if($likePostId)
                            <span x-cloak x-show="photoLikeVisible" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-75 opacity-0" class="pointer-events-none absolute inset-0 flex items-center justify-center"><x-coffee-icon name="heart" class="size-24 fill-current text-red-500 drop-shadow-lg photo-like-pop"/></span>
                        @endif
                    </div>
                @else
                    <video controls playsinline preload="metadata" class="aspect-[1.6] w-full bg-black object-contain" aria-label="{{ $title }} &mdash; video {{ $index + 1 }}"><source src="{{ $item['url'] }}" type="{{ $item['mime_type'] }}">Your browser cannot play this video.</video>
                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="block px-4 py-2 text-xs text-brand underline">Open original video</a>
                @endif
            </div>
        @endforeach
    </div>
    @if(count($media) > 1)
        <button type="button" x-on:click="showMedia(active - 1)" x-bind:style="'opacity: ' + arrowOpacity" aria-label="Previous photo or video" title="Previous photo" class="absolute left-3 top-1/2 z-10 flex size-11 -translate-y-1/2 items-center justify-center bg-transparent text-brand transition-opacity duration-500 hover:opacity-100 focus-visible:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand motion-reduce:transition-none">
            <x-coffee-icon name="chevron-left" class="size-7"/>
        </button>
        <button type="button" x-on:click="showMedia(active + 1)" x-bind:style="'opacity: ' + arrowOpacity" aria-label="Next photo or video" title="Next photo" class="absolute right-3 top-1/2 z-10 flex size-11 -translate-y-1/2 items-center justify-center bg-transparent text-brand transition-opacity duration-500 hover:opacity-100 focus-visible:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand motion-reduce:transition-none">
            <x-coffee-icon name="chevron-right" class="size-7"/>
        </button>
        <span class="pointer-events-none absolute top-3 right-3 z-10 rounded-full bg-surface/75 px-3 py-1 text-xs text-ink shadow-sm" aria-live="polite" aria-atomic="true"><span x-text="active + 1">1</span> / {{ count($media) }}</span>
    @endif
</section>
