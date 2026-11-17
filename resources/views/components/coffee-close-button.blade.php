@props([
    'label' => 'Close',
    'action' => '$dispatch("coffee-close")',
    'buttonSize' => 'size-10',
])

<button type="button" x-data="{ spilling: false, closing: false }" x-on:mouseenter="spilling = true" x-on:mouseleave="if (!closing) spilling = false" x-on:focus="spilling = true" x-on:blur="if (!closing) spilling = false" x-on:click="if (!closing) { closing = true; spilling = true; setTimeout(() => { {!! $action !!} }, 520) }" aria-label="{{ $label }}" title="{{ $label }}" {{ $attributes->merge(['class' => "relative flex {$buttonSize} items-center justify-center rounded-full text-muted transition-colors hover:bg-soft focus-visible:outline-2 focus-visible:outline-brand"]) }}>
    <svg viewBox="0 0 32 32" class="size-6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <g x-bind:class="spilling ? 'coffee-spill-cup' : ''">
            <path d="M7 13h14v8a5 5 0 0 1-5 5h-4a5 5 0 0 1-5-5v-8Z"/>
            <path d="M21 15h2a3 3 0 0 1 0 6h-2"/>
            <path d="M5 28h19"/>
        </g>
        <path x-cloak x-show="spilling" class="coffee-spill-pour" d="M22 14c2 0 2 2 4 2m-3 3c2 0 2 2 4 2"/>
        <path x-cloak x-show="spilling" class="coffee-spill-drop" d="M29 20c-1 1-1 2 0 2s1-1 0-2Z"/>
    </svg>
    <span x-cloak x-show="spilling" x-transition.opacity aria-hidden="true" class="absolute right-0 top-0 flex size-4 items-center justify-center rounded-full bg-surface text-brand shadow-sm"><x-lucide-x class="size-3"/></span>
</button>
