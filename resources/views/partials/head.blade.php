<meta charset="utf-8" />
<link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />
<link rel="apple-touch-icon" href="{{ asset('favicon.png') }}" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? 'CoffeeSpace' }}</title>

@vite(['resources/css/app.css', 'resources/js/app.js'])
@include('partials.theme')
