let activeProfileUuid = null;
let presenceChannel = null;
let presenceUsers = new Set();
let realtimeStarted = false;
let presenceConnectionBound = false;
let lastSeenSentAt = 0;

function dispatchPresence(ready, recentlyOffline = []) {
    window.dispatchEvent(new CustomEvent('coffee-presence-updated', {
        detail: { ready, profileUuids: [...presenceUsers], recentlyOffline },
    }));
}

function recordLastSeen() {
    const endpoint = document.body?.dataset.presenceUrl;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (! endpoint || ! token || Date.now() - lastSeenSentAt < 60000) return;

    lastSeenSentAt = Date.now();
    const body = new URLSearchParams({ _token: token });
    if (navigator.sendBeacon?.(endpoint, body)) return;

    fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        keepalive: true,
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        body,
    }).catch(() => {});
}

function joinOnlinePresence() {
    if (! window.Echo) return;

    presenceUsers = new Set();
    dispatchPresence(false);
    presenceChannel = window.Echo.join('online')
        .here(users => {
            presenceUsers = new Set(users.map(user => user.profile_uuid).filter(Boolean));
            dispatchPresence(true);
        })
        .joining(user => {
            if (user.profile_uuid) presenceUsers.add(user.profile_uuid);
            dispatchPresence(true);
        })
        .leaving(user => {
            if (user.profile_uuid) presenceUsers.delete(user.profile_uuid);
            dispatchPresence(true, user.profile_uuid ? [user.profile_uuid] : []);
        })
        .error(() => {});
}

function bindPresenceConnection() {
    const connection = window.Echo?.connector?.pusher?.connection;
    if (! connection || presenceConnectionBound) return;

    presenceConnectionBound = true;
    connection.bind('state_change', ({ current, previous }) => {
        if (['disconnected', 'unavailable'].includes(current) || (current === 'connected' && ['disconnected', 'unavailable'].includes(previous))) {
            recordLastSeen();
        }
        if (current === 'connected' && ['disconnected', 'unavailable'].includes(previous)) {
            window.dispatchEvent(new CustomEvent('coffee-activity-updated', { detail: { category: 'SYNC' } }));
        }
    });
}

function startCoffeeRealtime() {
    if (! window.Echo) return;

    const profileUuid = document.body?.dataset.profileUuid || '';
    if (profileUuid === activeProfileUuid) return;

    if (activeProfileUuid) {
        window.Echo.leave(`activity.${activeProfileUuid}`);
        window.Echo.leave('online');
    }

    activeProfileUuid = profileUuid;
    presenceUsers = new Set();

    if (! profileUuid) {
        dispatchPresence(true);
        return;
    }

    window.Echo.private(`activity.${profileUuid}`)
        .listen('.user.activity.updated', activity => {
            window.dispatchEvent(new CustomEvent('coffee-activity-updated', {
                detail: { category: activity.category },
            }));
        });

    joinOnlinePresence();
    bindPresenceConnection();
}

function reconnectPresence() {
    if (! activeProfileUuid || ! window.Echo) return;

    window.Echo.leave('online');
    presenceChannel = null;
    joinOnlinePresence();
}

if (! realtimeStarted) {
    realtimeStarted = true;
    document.addEventListener('DOMContentLoaded', startCoffeeRealtime);
    document.addEventListener('livewire:navigated', startCoffeeRealtime);
    window.addEventListener('presence-preference-updated', reconnectPresence);
    window.addEventListener('pagehide', recordLastSeen);
    startCoffeeRealtime();
}
