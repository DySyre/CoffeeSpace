<button type="button" aria-label="Switch between white and dark theme" title="Switch theme"
    onclick="const dark = !document.documentElement.classList.contains('dark'); document.documentElement.classList.toggle('dark', dark); localStorage.setItem('flux.appearance', dark ? 'dark' : 'light')"
    {{ $attributes->merge(['class' => 'flex size-10 shrink-0 items-center justify-center rounded-sm border border-line text-brand hover:bg-soft']) }}>
    <span class="dark:hidden"><x-coffee-icon name="moon"/></span>
    <span class="hidden dark:inline"><x-coffee-icon name="sun"/></span>
</button>
