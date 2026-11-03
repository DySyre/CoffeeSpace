@props(['title' => 'CoffeeSpace'])
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · CoffeeSpace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme')
</head>
<body class="coffee-app antialiased">
    <div class="absolute top-5 right-5"><x-theme-toggle/></div>
    <main class="flex min-h-svh flex-col items-center justify-center px-6 py-12">
        <a href="{{ route('home') }}" aria-label="CoffeeSpace home" class="mb-12"><x-brand/></a>
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.png') }}">
        <section class="w-full max-w-[340px]">
            <h1 class="mb-7 font-display text-3xl">{{ $title }}</h1>
            @if(session('status'))<p role="status" class="mb-5 border-l-2 border-brand bg-soft p-3 text-sm">{{ session('status') }}</p>@endif
            {{ $slot }}
        </section>
        <a href="{{ route('home') }}" class="mt-10 text-xs text-muted hover:text-brand">← Back to Discover</a>
    </main>
</body>
</html>
