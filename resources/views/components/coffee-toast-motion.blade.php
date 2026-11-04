@php($mugClip = 'toast-mug-'.\Illuminate\Support\Str::uuid())
<svg viewBox="0 0 64 64" class="size-14 shrink-0 text-brand" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <defs><clipPath id="{{ $mugClip }}"><path d="M13 30h30v15c0 7-5 11-11 11h-8c-6 0-11-4-11-11Z"/></clipPath></defs>
    <g clip-path="url(#{{ $mugClip }})" stroke="none"><rect x="12" y="40" width="32" height="20" fill="currentColor" opacity=".18" x-bind:class="toastKind === 'loading' ? 'coffee-toast-fill' : ''"/></g>
    <path d="M13 30h30v15c0 7-5 11-11 11h-8c-6 0-11-4-11-11ZM43 33h5a7 7 0 0 1 0 14h-5M9 59h40"/>
    <g x-show="toastKind === 'loading'"><path d="M15 7h10l5 7-5 7H14Z" fill="currentColor" fill-opacity=".08"/><path d="m25 18 7 4v12" class="coffee-toast-stream" stroke-dasharray="3 4"/><path d="m29 38 3-3 3 3" opacity=".55"/></g>
    <g x-show="toastKind === 'success'" class="coffee-toast-steam"><path d="M20 23c-6-7 6-8 0-15"/><path d="M29 22c-6-7 6-8 0-15" style="animation-delay:.25s"/><path d="M38 23c-6-7 6-8 0-15" style="animation-delay:.5s"/></g>
</svg>
