<div x-data="profileImageCropper($wire)" @if(session('post-status')) x-init="showToast('success', @js(session('post-status')), 'Post saved')" @endif>
    <livewire:discover.discover-feed :lazy="request()->hasHeader('X-Livewire-Navigate') ? false : 'on-load'" />
    <x-photo-toast/>
</div>
