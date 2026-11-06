@props(['amenities' => [], 'amenityMedia' => [], 'tag' => null, 'price' => null])
@if(count($amenities) || count($amenityMedia) || filled($tag) || $price !== null)
    <details class="post-tags-details mt-4 border-t border-line pt-1">
        <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2 text-sm font-semibold text-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand">
            <x-coffee-icon name="chevron-right" class="post-tags-chevron size-4 transition-transform duration-200 motion-reduce:transition-none"/>
            <span>Tags and details</span>
        </summary>
        <div class="post-tags-panel pb-1">
            <dl class="grid gap-3 sm:grid-cols-2" aria-label="Cafe details shared by the community">
                @if(filled($tag))
                    <div class="flex min-w-0 items-start gap-3">
                        <x-lucide-tag class="mt-1 size-4 shrink-0 text-brand"/>
                        <div class="min-w-0 flex-1">
                            <dt class="text-xs font-semibold text-brand">Tag</dt>
                            <dd class="mt-1 break-words text-sm leading-6 text-muted">{{ $tag }}</dd>
                        </div>
                    </div>
                @endif
                @if($price !== null)
                    <div class="flex min-w-0 items-start gap-3">
                        <x-coffee-icon name="budget" class="mt-1 size-4 text-brand"/>
                        <div class="min-w-0 flex-1">
                            <dt class="text-xs font-semibold text-brand">Price</dt>
                            <dd class="mt-1 text-sm leading-6 text-muted">₱{{ $price }} / cup</dd>
                        </div>
                    </div>
                @endif
                @foreach(\App\Models\Post::AMENITIES as $key => $definition)
                    @php($photos = collect($amenityMedia)->where('amenity_key', $key)->values())
                    @if((isset($amenities[$key]) && is_string($amenities[$key])) || $photos->isNotEmpty())
                        <div class="flex min-w-0 items-start gap-3">
                            <x-coffee-icon :name="$definition['icon']" class="mt-1 size-4 text-brand"/>
                            <div class="min-w-0 flex-1">
                                <dt class="text-xs font-semibold text-brand">{{ $definition['label'] }}</dt>
                                @if(isset($amenities[$key]) && is_string($amenities[$key]))<dd class="mt-1 whitespace-pre-line break-words text-sm leading-6 text-muted">{{ $amenities[$key] }}</dd>@endif
                                @if($photos->isNotEmpty())
                                    <dd class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-4" aria-label="{{ $definition['label'] }} photos">
                                        @foreach($photos as $index => $photo)
                                            <a href="{{ $photo['url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Open {{ $definition['label'] }} photo {{ $index + 1 }}" class="block aspect-square overflow-hidden rounded-sm bg-soft">
                                                <img src="{{ $photo['url'] }}" alt="{{ $definition['label'] }} photo {{ $index + 1 }}" loading="lazy" class="size-full object-cover">
                                            </a>
                                        @endforeach
                                    </dd>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </dl>
        </div>
    </details>
@endif
