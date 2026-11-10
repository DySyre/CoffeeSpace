<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('code') - @yield('title') | CoffeeSpace</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <script>
        try {
            const appearance = localStorage.getItem('flux.appearance');
            if (appearance === 'dark' || (appearance === 'system' && matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');
        } catch (error) {}
    </script>
    {{-- Keep error recovery independent of Vite, Livewire, authentication, and sessions. --}}
    <style>
        :root { color-scheme: light; --page:#fff; --ink:#35271f; --muted:#79685c; --brand:#805437; --soft:#f3ece4; --line:#e8e0d8; --on-brand:#fffaf5; }
        .dark { color-scheme:dark; --page:#191614; --ink:#f7eee6; --muted:#c1ad9b; --brand:#d6a87c; --soft:#382e27; --line:#493b32; --on-brand:#251b14; }
        * { box-sizing:border-box; }
        body { margin:0; background:var(--page); color:var(--ink); font-family:'DM Sans Variable','DM Sans',Arial,sans-serif; -webkit-font-smoothing:antialiased; }
        ::selection { background:var(--soft); color:var(--ink); }
        a { color:inherit; text-underline-offset:4px; }
        a:focus-visible { outline:3px solid var(--brand); outline-offset:5px; }
        .error-layout { width:min(calc(100% - 48px),960px); min-height:100vh; min-height:100svh; margin:auto; display:flex; flex-direction:column; }
        .error-header { padding:28px 0; }
        .brand { display:inline-flex; align-items:center; gap:12px; color:var(--brand); text-decoration:none; }
        .brand svg { display:block; width:48px; height:48px; }
        .brand-text { font-size:23px; font-weight:600; letter-spacing:-.025em; }
        .brand-text span { font-size:17px; font-weight:500; }
        main { flex:1; display:flex; align-items:center; padding:48px 0 72px; }
        .error-content { display:grid; gap:32px; width:100%; }
        .error-code { margin:0; font-size:96px; font-weight:600; letter-spacing:-.04em; line-height:1; color:var(--brand); font-variant-numeric:tabular-nums; }
        h1 { margin:0; max-width:18ch; font-size:32px; line-height:1.2; font-weight:600; letter-spacing:-.025em; text-wrap:balance; }
        .message { max-width:44ch; margin:18px 0 0; color:var(--muted); font-size:16px; line-height:1.75; }
        .actions { display:flex; flex-wrap:wrap; align-items:center; gap:12px; margin-top:28px; }
        .button { display:inline-flex; align-items:center; justify-content:center; min-height:46px; padding:12px 18px; border:1px solid var(--brand); border-radius:4px; background:var(--brand); color:var(--on-brand); font-size:14px; line-height:1.4; font-weight:600; text-decoration:none; }
        .button:hover { filter:brightness(.92); }
        .button-secondary { border-color:var(--line); background:transparent; color:var(--ink); }
        .button-secondary:hover { background:var(--soft); filter:none; }
        .supporting { margin:22px 0 0; font-size:13px; line-height:1.65; color:var(--muted); }
        footer { padding:24px 0; border-top:1px solid var(--line); color:var(--muted); font-size:12px; line-height:1.7; }
        @media (min-width:640px) { .error-content { grid-template-columns:180px minmax(0,1fr); align-items:start; gap:48px; } h1 { font-size:38px; } .error-header { padding:36px 0; } }
        @media (max-width:360px) { .actions { align-items:stretch; flex-direction:column; } }
    </style>
</head>
<body>
    <div class="error-layout">
        <header class="error-header"><a href="{{ url('/') }}" class="brand" aria-label="CoffeeSpace home"><x-brand :compact="true"/><span class="brand-text">coffee<span>SPACE</span></span></a></header>
        <main id="main-content">
            <div class="error-content">
                <p class="error-code" aria-hidden="true">@yield('code')</p>
                <section aria-labelledby="error-title">
                    <h1 id="error-title">@yield('title')</h1>
                    <p class="message">@yield('message')</p>
                    <div class="actions">@yield('actions')</div>
                    <p class="supporting">@yield('supporting')</p>
                </section>
            </div>
        </main>
        <footer>CoffeeSpace &middot; Your daily orbit</footer>
    </div>
</body>
</html>
