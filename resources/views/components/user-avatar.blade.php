@props(['user'])
<span class="avatar overflow-hidden shrink-0" x-data="{ photoUrl: @js($user->profilePhotoUrl()) }" x-on:profile-photo-saved.window="if ($event.detail.userId === {{ $user->id }}) photoUrl = $event.detail.url">
    <img x-cloak x-show="photoUrl" x-bind:src="photoUrl" alt="{{ $user->name }} profile photo" class="size-full object-cover">
    <span x-show="!photoUrl">{{ mb_substr($user->name, 0, 1) }}</span>
</span>
