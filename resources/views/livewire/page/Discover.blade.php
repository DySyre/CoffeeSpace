<div x-data="profileImageCropper($wire)" x-on:post-report-result.window="showToast('success', $event.detail.message, $event.detail.title)" x-on:comment-report-result.window="showToast('success', $event.detail.message, $event.detail.title)" x-on:post-hidden.window="showToast('success', 'This post is hidden from your Discover feed.', 'Post hidden')" @if(session('post-status')) x-init="showToast('success', @js(session('post-status')), 'Post saved')" @endif>
    <livewire:discover.discover-feed :lazy="request()->hasHeader('X-Livewire-Navigate') ? false : 'on-load'" />
    <x-photo-toast/>
</div>
