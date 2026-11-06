<span
    x-data="{
        online: @js($online),
        label: @js($label),
        offlineLabel: @js($offlineLabel),
        profileUuid: @js($profileUuid),
        offlineSince: 0,
        offlineTimer: null,
        refreshOfflineLabel() {
            if (!this.offlineSince) return;
            const minutes = Math.floor((Date.now() - this.offlineSince) / 60000);
            this.label = minutes < 1 ? 'Last seen just now' : minutes === 1 ? 'Last seen 1 minute ago' : `Last seen ${minutes} minutes ago`;
            this.offlineTimer = setTimeout(() => this.refreshOfflineLabel(), 60000);
        },
        stopOfflineTimer() {
            clearTimeout(this.offlineTimer);
            this.offlineTimer = null;
        },
        destroy() {
            this.stopOfflineTimer();
        },
    }"
    x-on:coffee-presence-updated.window="if ($event.detail.ready) { online = $event.detail.profileUuids.includes(profileUuid); if (online) { stopOfflineTimer(); offlineSince = 0; label = 'Online now' } else if ($event.detail.recentlyOffline.includes(profileUuid)) { stopOfflineTimer(); offlineSince = Date.now(); refreshOfflineLabel() } else if (!offlineSince) { label = offlineLabel } }"
    x-on:presence-preference-updated.window="if ($event.detail.profileUuid === profileUuid) { stopOfflineTimer(); online = false; offlineSince = 0; label = $event.detail.showActivity ? offlineLabel : 'Activity status hidden' }"
    role="status" aria-live="polite" class="inline-flex items-center gap-2 text-xs text-muted"
>
    <span x-cloak x-show="online" aria-hidden="true" class="size-2 rounded-full bg-emerald-600"></span>
    <span x-text="label">{{ $label }}</span>
</span>
