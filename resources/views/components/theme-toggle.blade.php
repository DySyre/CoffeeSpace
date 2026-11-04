<button type="button" aria-label="Toggle day and night theme" title="Toggle day and night theme"
    onclick="const dark = !document.documentElement.classList.contains('dark'); document.documentElement.classList.toggle('dark', dark); localStorage.setItem('flux.appearance', dark ? 'dark' : 'light')"
    {{ $attributes->merge(['class' => 'relative flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-sm border border-line text-brand hover:bg-soft']) }}>
    <span aria-hidden="true" class="theme-toggle-icon theme-toggle-sun"><x-coffee-icon name="sun"/></span>
    <span aria-hidden="true" class="theme-toggle-icon theme-toggle-moon"><x-coffee-icon name="moon"/></span>
</button>
