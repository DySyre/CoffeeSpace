@props(['title' => 'CoffeeSpace', 'section' => 'discover'])
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => $title])
    <meta name="description" content="Find your next favorite café. CoffeeSpace is your space for coffee stories, cozy corners, and everyday discoveries." />
    @livewireStyles
</head>
<body class="coffee-app antialiased">
@php
    $viewerKey = auth()->check() ? auth()->id().'-'.md5(auth()->user()->name) : 'guest';
    $navigation = [
        ['discover', 'Discover', route('home')],
        ['map', 'Coffee Map', route('coffee.map')],
        ['post', 'Post', route('coffee.post')],
        ['budget', 'Coffee Budget', route('coffee.budget')],
        ['profile', 'My Profile', route('coffee.profile')],
    ];
@endphp
<a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-sm focus:bg-white focus:p-4 focus:text-stone-900">Skip to content</a>
<link rel="icon" type="image/x-icon" href="{{ asset('favicon.png') }}">
@persist('coffee-sidebar-'.$viewerKey)
<aside class="fixed inset-y-0 left-0 z-30 hidden w-60 flex-col border-r border-line bg-surface px-6 py-9 lg:flex xl:w-64">
    <a href="{{ route('home') }}" wire:navigate aria-label="CoffeeSpace home"><x-brand/></a>
    <div class="mt-12"></div>
    <nav class="flex flex-col gap-2" aria-label="Main navigation">
        @foreach($navigation as [$key, $label, $href])
            <a href="{{ $href }}" wire:navigate.hover data-coffee-navigation-link="{{ $key }}" data-nav-style="desktop" @if($section === $key) aria-current="page" @endif class="flex items-center gap-3 rounded-sm px-4 py-3.5 text-sm transition hover:bg-soft {{ $section === $key ? 'bg-soft font-semibold text-brand' : 'text-muted hover:text-ink' }}"><x-coffee-icon :name="$key"/>{{ $label }}<span data-nav-dot @if($section !== $key) hidden @endif class="ml-auto size-1.5 rounded-full bg-brand"></span></a>
        @endforeach
    </nav>
    <div class="mt-auto border-t border-line pt-6">
        @auth
            <a href="{{ route('coffee.profile') }}" wire:navigate class="flex items-center gap-3"><span class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span class="min-w-0"><span class="block truncate text-sm font-semibold">{{ auth()->user()->name }}</span><span class="text-xs text-muted">Your coffee journey</span></span></a>
            <form method="post" action="{{ route('logout') }}" class="mt-4">@csrf<button type="submit" class="text-xs text-muted hover:text-brand">Log out</button></form>
        @else
            <a href="{{ route('login') }}" class="flex items-center gap-3"><span class="avatar"><x-coffee-icon name="profile"/></span><span><span class="block text-sm font-semibold">Your coffee journey</span><span class="text-xs text-muted">Log in to make it yours <span aria-hidden="true">↗</span></span></span></a>
        @endauth
    </div>
</aside>
@endpersist
<div class="lg:ml-60 xl:ml-64">
    <div @class(['sticky top-0 z-20', 'hidden lg:block' => $section === 'post'])>
    @persist('coffee-header-'.$viewerKey)
    <header class="flex h-20 items-center justify-between gap-4 border-b border-line bg-page/95 px-5 backdrop-blur-md sm:px-9 xl:px-12">
        <a href="{{ route('home') }}" wire:navigate aria-label="CoffeeSpace home" class="flex items-center gap-2 font-display text-lg font-bold text-brand lg:hidden"><x-brand class="max-[360px]:[&_.brand-wordmark]:hidden"/></a>
        <form data-coffee-search action="{{ route('home') }}" method="get" role="search" class="hidden w-full max-w-md items-center gap-3 rounded-sm border border-line bg-surface px-4 py-2.5 sm:flex">
            <x-coffee-icon name="search" class="size-4 text-muted"/><label for="global-search" class="sr-only">Search sample café stories</label><input id="global-search" name="search" value="{{ request('search') }}" maxlength="100" placeholder="Search cafés and stories" class="w-full bg-transparent text-xs outline-none placeholder:text-muted"/>
        </form>
        <div class="flex shrink-0 items-center gap-2 sm:gap-4">
            <x-theme-toggle/>
            <a href="{{ route('home', ['focus' => 'search']) }}" wire:navigate aria-label="Search café stories" class="rounded-sm p-2 sm:hidden"><x-coffee-icon name="search"/></a>
            
            @auth <a href="{{ route('coffee.profile') }}" wire:navigate aria-label="My profile" class="avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</a><form method="post" action="{{ route('logout') }}" class="lg:hidden">@csrf<button type="submit" class="text-xs text-muted">Log out</button></form>
            @else <a href="{{ route('login') }}" class="text-xs font-semibold text-brand">Log in</a><a href="{{ route('register') }}" class="hidden rounded-sm bg-brand px-4 py-2.5 text-xs font-semibold text-on-brand sm:block">Sign up</a> @endauth
        </div>
    </header>
    @endpersist
    </div>
    <main id="main-content" @class(['mx-auto max-w-[1400px] lg:pb-12 lg:px-9 xl:px-12', 'px-4 pt-8 pb-28 sm:px-9' => $section !== 'post', 'lg:pt-8' => $section === 'post'])>{{ $slot }}</main>
</div>
<div @class(['hidden' => $section === 'post'])>
@persist('coffee-mobile-navigation-'.$viewerKey)
<nav aria-label="Mobile navigation" class="fixed inset-x-0 bottom-0 z-30 flex justify-around border-t border-line bg-surface px-2 pt-2 pb-[max(.75rem,env(safe-area-inset-bottom))] lg:hidden">
    @foreach($navigation as [$key, $label, $href])
        <a href="{{ $href }}" wire:navigate.hover data-coffee-navigation-link="{{ $key }}" data-nav-style="mobile" @if($section === $key) aria-current="page" @endif class="flex min-h-12 min-w-0 flex-1 flex-col items-center justify-center gap-1 text-[11px] {{ $section === $key ? 'font-bold text-brand' : 'text-muted' }}"><span class="{{ $key === 'post' ? 'rounded-full bg-brand p-2 text-on-brand' : 'p-1' }}"><x-coffee-icon :name="$key" class="size-5"/></span>{{ ['discover' => 'Discover', 'map' => 'Map', 'post' => 'Post', 'budget' => 'Budget', 'profile' => 'Profile'][$key] }}</a>
    @endforeach
</nav>
@endpersist
</div>
@fluxScripts
</body>
</html>
