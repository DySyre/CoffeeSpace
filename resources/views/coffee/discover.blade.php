<x-layouts.coffee title="Discover · CoffeeSpace" section="discover">
    @if(session('post-status'))<p role="status" class="mx-auto mb-6 max-w-[760px] rounded-sm bg-soft p-4 text-sm">{{ session('post-status') }}</p>@endif
    <livewire:discover lazy="on-load" />
</x-layouts.coffee>
