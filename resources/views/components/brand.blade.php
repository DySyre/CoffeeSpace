@props(['compact' => false, 'responsive' => false])
<span {{ $attributes->merge(['class' => $responsive ? 'inline-flex min-w-0 items-center gap-2 text-brand max-sm:gap-1' : 'inline-flex min-w-0 items-center gap-3 text-brand']) }}>
    <svg @class(['shrink-0' => true, 'size-11' => ! $responsive, 'size-10 sm:size-11' => $responsive]) viewBox="0 0 64 64" fill="none" aria-hidden="true">
        <!-- A coffee planet: the cup rim and orbit share one ellipse. -->
        <path d="M17 26h29v13c0 10-7 16-14.5 16S17 49 17 39V26Z" stroke="currentColor" stroke-width="2.6"/>
        <path d="M46 29h4a7 7 0 0 1 0 14h-5" stroke="currentColor" stroke-width="2.6"/>
        <ellipse cx="30" cy="29" rx="26" ry="8" transform="rotate(-22 30 29)" stroke="currentColor" stroke-width="2.2"/>
        <path d="m45 6 1.4 4.6L51 12l-4.6 1.4L45 18l-1.4-4.6L39 12l4.6-1.4L45 6Z" fill="currentColor"/>
        <path d="M26 18c-4-5 5-7 1-12M34 17c-2-3 3-5 2-8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <circle cx="10" cy="13" r="1.5" fill="currentColor"/>
    </svg>
    @unless($compact)<span class="brand-wordmark flex min-w-0 flex-col"><span @class(['font-display font-semibold leading-none tracking-tight', 'text-[23px]' => ! $responsive, 'min-w-0 whitespace-nowrap text-[clamp(0.75rem,4.5vw,1.4375rem)]' => $responsive])>coffee<span @class(['font-sans font-medium tracking-[-.02em]', 'text-[17px]' => ! $responsive, 'text-[clamp(0.55rem,3.2vw,1.0625rem)]' => $responsive])>SPACE</span></span><span @class(['mt-1.5 text-[9px] tracking-[.16em] uppercase', 'max-sm:hidden' => $responsive])>Your daily orbit</span></span>@endunless
</span>
