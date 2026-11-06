@props(['media', 'title', 'likePostId' => null])
<section x-data="{ active: 0, photoLikeVisible: false }" class="relative bg-soft" aria-label="Post photos and videos">
    <div x-ref="items">
        @foreach($media as $index => $item)
            <div x-show="active === {{ $index }}" @if($index > 0) x-cloak @endif>
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
        <div class="flex items-center justify-between gap-3 border-t border-line bg-surface px-3 py-1">
            <button type="button" x-on:click="$refs.items.querySelectorAll('video').forEach(video => video.pause()); active = (active - 1 + {{ count($media) }}) % {{ count($media) }}" class="min-h-11 px-3 text-sm text-brand" aria-label="Previous photo or video">Previous</button>
            <span class="text-xs text-muted" aria-live="polite"><span x-text="active + 1">1</span> / {{ count($media) }}</span>
            <button type="button" x-on:click="$refs.items.querySelectorAll('video').forEach(video => video.pause()); active = (active + 1) % {{ count($media) }}" class="min-h-11 px-3 text-sm text-brand" aria-label="Next photo or video">Next</button>
        </div>
    @endif
</section>
