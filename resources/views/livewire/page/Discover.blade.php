<div>
    @if(session('post-status'))<p role="status" class="mx-auto mb-6 max-w-[760px] rounded-sm bg-soft p-4 text-sm">{{ session('post-status') }}</p>@endif
    <livewire:discover.discover-feed :lazy="request()->hasHeader('X-Livewire-Navigate') ? false : 'on-load'" />
</div>
