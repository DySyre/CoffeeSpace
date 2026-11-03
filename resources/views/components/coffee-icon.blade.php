@props(['name' => 'coffee'])
<svg {{ $attributes->merge(['class' => 'size-5 shrink-0']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('settings') <path d="m9.5 3-.7 2.3-2.3 1-2.2-.6-2 3.5L4 11v2l-1.7 1.8 2 3.5 2.2-.6 2.3 1 .7 2.3h5l.7-2.3 2.3-1 2.2.6 2-3.5L20 13v-2l1.7-1.8-2-3.5-2.2.6-2.3-1L14.5 3Z"/><circle cx="12" cy="12" r="3"/> @break
        @case('camera') <path d="M3 7h4l2-3h6l2 3h4v14H3Z"/><circle cx="12" cy="13" r="4"/> @break
        @case('image') <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8" cy="8" r="2"/><path d="m3 17 5-5 4 4 4-6 5 7"/> @break
        @case('video') <rect x="3" y="5" width="13" height="14" rx="2"/><path d="m16 10 5-4v12l-5-4"/> @break
        @case('refresh') <path d="M20 7v5h-5M4 17v-5h5M5 7a8 8 0 0 1 13-2l2 2M4 17l2 2a8 8 0 0 0 13-2"/> @break
        @case('comment') <path d="M21 11a8 8 0 0 1-8 8H7l-5 3 2-6a8 8 0 1 1 17-5Z"/> @break
        @case('moon') <path d="M20.5 13a8.5 8.5 0 0 1-9.5-9.5A8.5 8.5 0 1 0 20.5 13Z"/> @break
        @case('sun') <circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1.5 1.5m11 11L19 19M5 19l1.5-1.5m11-11L19 5"/> @break
        @case('discover') <path d="m3 10 9-7 9 7v10H3Z"/><path d="M9 20v-7h6v7"/> @break
        @case('map') <path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/> @break
        @case('post') <path d="M12 5v14M5 12h14"/> @break
        @case('budget') <rect x="5" y="5" width="14" height="16" rx="2"/><path d="M9 5V3h6v2M8 10h8M8 14h3M8 17h3M15 14h1M15 17h1"/> @break
        @case('profile') <circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/> @break
        @case('search') <circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/> @break
        @case('heart') <path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/> @break
        @case('bookmark') <path d="M6 3h12v18l-6-4-6 4Z"/> @break
        @case('arrow') <path d="M4 12h16m-6-6 6 6-6 6"/> @break
        @case('share') <path d="M12 16V3m-5 5 5-5 5 5M5 13v8h14v-8"/> @break
        @case('star') <path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9Z"/> @break
        @default <path d="M4 9h13v7a5 5 0 0 1-5 5H9a5 5 0 0 1-5-5ZM17 10h2a3 3 0 1 1 0 6h-2M7 3v2M11 2v3M15 3v2"/>
    @endswitch
</svg>
