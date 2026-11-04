<section class="w-full" x-data="profileImageCropper($wire)" x-on:account-unblocked.window="showToast('success', $event.detail.name + ' has been unblocked.', 'Account updated')">
    @include('partials.settings-heading')

    <x-settings.layout heading="Account" subheading="Manage your blocked accounts and safety settings">
        <section class="my-6 w-full" aria-labelledby="blocked-accounts-heading">
            <div class="border-b border-zinc-200 pb-5 dark:border-zinc-700">
                <h2 id="blocked-accounts-heading" class="text-base font-semibold text-zinc-900 dark:text-white">Blocked accounts</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">People you unblock can see your public posts and contact you according to your privacy settings.</p>
            </div>

            @if($blockedUsers->isNotEmpty())
                <ul class="divide-y divide-line" aria-label="Accounts you have blocked">
                    @foreach($blockedUsers as $blockedUser)
                        <li wire:key="blocked-account-{{ $blockedUser->id }}" class="flex items-center gap-3 py-4">
                            <x-user-avatar :user="$blockedUser"/>
                            <span class="min-w-0 flex-1 truncate text-sm font-medium text-ink">{{ $blockedUser->name }}</span>
                            <button type="button" wire:click="unblock({{ $blockedUser->id }})" wire:loading.attr="disabled" wire:target="unblock({{ $blockedUser->id }})" class="inline-flex min-h-11 items-center justify-center rounded-sm border border-line px-4 text-sm font-medium text-brand transition hover:bg-soft disabled:opacity-50">Unblock</button>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="py-12 text-center">
                    <x-coffee-icon name="check" class="mx-auto size-8 text-brand"/>
                    <h3 class="mt-3 font-semibold text-ink">No blocked accounts</h3>
                    <p class="mt-1 text-sm text-muted">Accounts you block from their profile will appear here.</p>
                </div>
            @endif
        </section>
    </x-settings.layout>
    <x-photo-toast/>
</section>
