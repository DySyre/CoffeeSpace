@props(['title' => 'CoffeeSpace', 'section' => 'discover'])
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $title])
    <meta name="description" content="Find your next favorite café. CoffeeSpace is your space for coffee stories, cozy corners, and everyday discoveries." />
    @livewireStyles
</head>
<body class="coffee-app antialiased" @auth data-profile-uuid="{{ auth()->user()->profile_uuid }}" data-presence-url="{{ route('activity.last-seen', [], false) }}" @endauth>
@php
    $viewerKey = auth()->check() ? auth()->id().'-'.md5(auth()->user()->name.(auth()->user()->profile_photo['path'] ?? '')) : 'guest';
    $navigation = [
        ['discover', 'Discover', route('home')],
        ['map', 'Coffee Map', route('coffee.map')],
        ['post', 'Post', route('coffee.post')],
        ['budget', 'Coffee Budget', route('coffee.budget')],
        ['profile', 'My Profile', (auth()->check() ? auth()->user()->profileUrl() : route('coffee.profile'))],
    ];
@endphp
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-sm focus:bg-white focus:p-4 focus:text-stone-900">Skip to content</a>

<aside class="fixed inset-y-0 left-0 z-30 hidden w-60 flex-col border-r border-line bg-surface px-6 py-9 lg:flex xl:w-64">
    <a href="{{ route('home') }}" wire:navigate aria-label="CoffeeSpace home"><x-brand/></a>
    <div class="mt-12"></div>
    <nav class="flex flex-col gap-2" aria-label="Main navigation">
        @foreach($navigation as [$key, $label, $href])
            <a href="{{ $href }}" wire:navigate.hover x-data="{ tabPressed: false }" x-on:click="tabPressed = true; window.setTimeout(() => tabPressed = false, 380)" data-coffee-navigation-link="{{ $key }}" data-nav-style="desktop" @if($section === $key) aria-current="page" @endif class="flex items-center gap-3 rounded-sm px-4 py-3.5 text-sm transition hover:bg-soft {{ $section === $key ? 'bg-soft font-semibold text-brand' : 'text-muted hover:text-ink' }}">
                <span class="nav-icon-shell nav-icon-{{ $key }}" x-bind:class="tabPressed ? 'nav-tab-active' : ''">
                    <x-coffee-icon :name="$key" :data-nav-icon="$key" x-bind:class="tabPressed ? 'nav-tab-press' : ''"/>
                    @if($key === 'budget')<span class="nav-wallet-coin" aria-hidden="true"></span>@endif
                    @if($key === 'profile')<span class="nav-profile-rays" aria-hidden="true"><i></i><i></i><i></i></span>@endif
                </span>
                {{ $label }}<span data-nav-dot @if($section !== $key) hidden @endif class="ml-auto size-1.5 rounded-full bg-brand"></span>
            </a>
        @endforeach
    </nav>
    <div class="mt-auto border-t border-line pt-6">
        @auth
            <a href="{{ (auth()->check() ? auth()->user()->profileUrl() : route('coffee.profile')) }}" wire:navigate class="flex items-center gap-3"><x-user-avatar :user="auth()->user()"/><span class="min-w-0"><span class="block truncate text-sm font-semibold">{{ auth()->user()->name }}</span><span class="text-xs text-muted">Your coffee journey</span></span></a>
            <form method="post" action="{{ route('logout') }}" class="mt-4">@csrf<button type="submit" class="text-xs text-muted hover:text-brand">Log out</button></form>
        @else
            <a href="{{ route('login') }}" class="flex items-center gap-3"><span class="avatar"><x-coffee-icon name="profile"/></span><span><span class="block text-sm font-semibold">Your coffee journey</span><span class="text-xs text-muted">Log in to make it yours <span aria-hidden="true">↗</span></span></span></a>
        @endauth
    </div>
</aside>
<div class="lg:ml-60 xl:ml-64">
    <div @class(['sticky top-0 z-20', 'hidden lg:block' => $section === 'post'])>
    <header x-data="{ searchOpen: false, isMobile: window.matchMedia('(max-width: 639px)').matches }" x-on:resize.window="isMobile = window.matchMedia('(max-width: 639px)').matches; if (!isMobile) searchOpen = false" x-on:livewire:navigating.window="searchOpen = false" x-on:keydown.escape="if (searchOpen) { searchOpen = false; $nextTick(() => $refs.searchTrigger.focus()); }" class="flex h-20 items-center justify-between gap-4 border-b border-line bg-page/95 px-5 backdrop-blur-md sm:px-9 xl:px-12">
        <a href="{{ route('home') }}" wire:navigate aria-label="CoffeeSpace home" x-bind:class="searchOpen ? 'max-sm:hidden' : ''" class="flex items-center gap-2 font-display text-lg font-bold text-brand lg:hidden"><x-brand class="max-sm:[&_.brand-wordmark]:hidden"/></a>
        <form data-coffee-search action="{{ route('home') }}" method="get" role="search" x-bind:class="searchOpen ? 'max-sm:w-full max-sm:max-w-full max-sm:flex-1 max-sm:translate-x-0 max-sm:opacity-100' : 'max-sm:w-0 max-sm:max-w-0 max-sm:flex-none max-sm:translate-x-2 max-sm:opacity-0 max-sm:px-0 max-sm:border-transparent'" x-bind:inert="isMobile && !searchOpen" x-bind:aria-hidden="isMobile && !searchOpen" class="flex min-w-0 flex-1 items-center gap-2 overflow-hidden rounded-sm border border-line bg-surface px-3 py-2.5 transition-all duration-400 ease-out motion-reduce:transition-none sm:max-w-sm sm:gap-3 sm:px-4 sm:focus-within:max-w-3xl">
            <button type="submit" aria-label="Submit search" class="flex size-7 shrink-0 items-center justify-center text-brand"><x-coffee-icon name="search" class="size-4"/></button>
            <label for="global-search" class="sr-only">Search caf&eacute;s and stories</label><input id="global-search" x-ref="headerSearch" name="search" value="{{ request('search') }}" minlength="3" maxlength="100" enterkeyhint="search" placeholder="Search caf&eacute;s and stories" class="min-w-0 w-full bg-transparent text-base outline-none placeholder:text-muted sm:text-sm"/>
        </form>
        <div class="flex shrink-0 items-center gap-2 sm:gap-4">
            <button type="button" x-ref="searchTrigger" x-on:click="if (searchOpen) { searchOpen = false; $nextTick(() => $refs.searchTrigger.focus()); } else { searchOpen = true; $nextTick(() => $refs.headerSearch.focus()); }" x-bind:aria-expanded="searchOpen" x-bind:aria-label="searchOpen ? 'Close search' : 'Open search'" aria-controls="global-search" class="flex size-11 shrink-0 items-center justify-center rounded-sm text-brand sm:hidden">
                <span x-show="!searchOpen" x-transition:enter="transition ease-out duration-400" x-transition:enter-start="scale-75 -rotate-45 opacity-0" x-transition:enter-end="scale-100 rotate-0 opacity-100" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="scale-100 rotate-0 opacity-100" x-transition:leave-end="scale-75 rotate-45 opacity-0"><x-coffee-icon name="search"/></span>
                <span x-cloak x-show="searchOpen" x-transition:enter="transition ease-out duration-400" x-transition:enter-start="scale-75 rotate-45 opacity-0" x-transition:enter-end="scale-100 rotate-0 opacity-100" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="scale-100 rotate-0 opacity-100" x-transition:leave-end="scale-75 -rotate-45 opacity-0" aria-hidden="true" class="text-xl leading-none">&times;</span>
            </button>
            @auth
                <div x-bind:class="searchOpen ? 'max-sm:hidden' : ''"><livewire:components.chat-inbox-link /></div>
                <div x-bind:class="searchOpen ? 'max-sm:hidden' : ''"><livewire:components.notification-menu /></div>
            @endauth
            @auth
                <a href="{{ (auth()->check() ? auth()->user()->profileUrl() : route('coffee.profile')) }}" wire:navigate aria-label="My profile" class="hidden lg:block"><x-user-avatar :user="auth()->user()"/></a>
                <div class="relative lg:hidden" x-bind:class="searchOpen ? 'max-sm:hidden' : ''" x-data="{ accountOpen: false }" x-on:click.outside="accountOpen = false" x-on:keydown.escape.stop="accountOpen = false; $refs.accountButton.focus()" x-on:livewire:navigating.window="accountOpen = false">
                    <button type="button" x-ref="accountButton" x-on:click="accountOpen = !accountOpen" x-on:keydown.arrow-down.prevent="accountOpen = true; $nextTick(() => $refs.profileLink.focus())" x-bind:aria-expanded="accountOpen" aria-controls="mobile-account-options" aria-label="Open account menu" class="block rounded-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand"><x-user-avatar :user="auth()->user()"/></button>
                    <div id="mobile-account-options" x-cloak x-show="accountOpen" class="absolute top-full right-0 z-40 mt-3 w-52 rounded-sm border border-line bg-surface p-2">
                        <a x-ref="profileLink" href="{{ (auth()->check() ? auth()->user()->profileUrl() : route('coffee.profile')) }}" wire:navigate x-on:click="accountOpen = false" class="flex min-h-11 items-center gap-2 rounded-sm px-3 text-sm text-ink hover:bg-soft"><x-coffee-icon name="profile"/>My Profile</a>
                        <a href="{{ route('settings.profile') }}" wire:navigate x-on:click="accountOpen = false" class="flex min-h-11 items-center gap-2 rounded-sm px-3 text-sm text-ink hover:bg-soft"><x-coffee-icon name="settings"/>Settings</a>
                        <form method="post" action="{{ route('logout') }}" class="mt-1 border-t border-line pt-1">@csrf<button type="submit" class="flex min-h-11 w-full items-center rounded-sm px-3 text-left text-sm text-brand hover:bg-soft">Log out</button></form>
                    </div>
                </div>
            @else <a href="{{ route('login') }}" x-bind:class="searchOpen ? 'max-sm:hidden' : ''" class="text-xs font-semibold text-brand">Log in</a><a href="{{ route('register') }}" class="hidden rounded-sm bg-brand px-4 py-2.5 text-xs font-semibold text-on-brand sm:block">Sign up</a> @endauth
        </div>
    </header>
    </div>
    <main id="main-content" @class(['mx-auto max-w-[1400px] lg:pb-12 lg:px-9 xl:px-12', 'px-4 pt-8 pb-28 sm:px-9' => $section !== 'post', 'lg:pt-8' => $section === 'post'])>{{ $slot }}</main>
</div>
<div @class(['hidden' => $section === 'post'])>
@persist('coffee-mobile-navigation-'.$viewerKey)
<nav aria-label="Mobile navigation" class="fixed inset-x-0 bottom-0 z-30 flex justify-around border-t border-line bg-surface px-2 pt-2 pb-[max(.75rem,env(safe-area-inset-bottom))] lg:hidden">
    @foreach($navigation as [$key, $label, $href])
        <a href="{{ $href }}" wire:navigate.hover x-data="{ tabPressed: false }" x-on:click="tabPressed = true; window.setTimeout(() => tabPressed = false, 380)" data-coffee-navigation-link="{{ $key }}" data-nav-style="mobile" @if($section === $key) aria-current="page" @endif class="flex min-h-12 min-w-0 flex-1 flex-col items-center justify-center gap-1 text-[11px] {{ $section === $key ? 'font-bold text-brand' : 'text-muted' }}">
            <span class="{{ $key === 'post' ? 'rounded-full bg-brand p-2 text-on-brand' : 'p-1' }}">
                <span class="nav-icon-shell nav-icon-{{ $key }}" x-bind:class="tabPressed ? 'nav-tab-active' : ''">
                    <x-coffee-icon :name="$key" :data-nav-icon="$key" x-bind:class="tabPressed ? 'nav-tab-press' : ''" class="size-5"/>
                    @if($key === 'budget')<span class="nav-wallet-coin" aria-hidden="true"></span>@endif
                    @if($key === 'profile')<span class="nav-profile-rays" aria-hidden="true"><i></i><i></i><i></i></span>@endif
                </span>
            </span>
            {{ ['discover' => 'Discover', 'map' => 'Map', 'post' => 'Post', 'budget' => 'Budget', 'profile' => 'Profile'][$key] }}
        </a>
    @endforeach
</nav>
@endpersist
</div>
@fluxScripts
</body>
</html>
