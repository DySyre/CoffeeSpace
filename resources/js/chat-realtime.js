export default function chatRealtime(wire, profileUuid) {
    return {
        channelName: `users.${profileUuid}`,
        pendingUpdates: [],
        refreshTimer: null,

        init() {
            if (! window.Echo || ! profileUuid) return;

            window.Echo.private(this.channelName)
                .listen('.chat.updated', update => this.scheduleRefresh(update));
        },

        scheduleRefresh(update) {
            if (! update?.conversation_uuid) return;

            this.pendingUpdates.push(update);
            if (this.refreshTimer) return;

            this.refreshTimer = setTimeout(async () => {
                this.refreshTimer = null;
                const updates = this.pendingUpdates.splice(0);
                const currentConversation = String(wire.conversationUuid ?? '');
                const draftRecipient = String(wire.recipientUuid ?? '');
                const updateToApply = updates.find(item => item.conversation_uuid === currentConversation)
                    ?? updates.find(item => item.sender_uuid === draftRecipient)
                    ?? updates[updates.length - 1];

                try {
                    await wire.refreshChat(updateToApply.conversation_uuid, updateToApply.sender_uuid);
                    window.dispatchEvent(new CustomEvent('chat-message-updated', {
                        detail: { conversationUuid: updateToApply.conversation_uuid },
                    }));
                } catch (error) {
                    console.error('Could not refresh chat after a WebSocket update.', error);
                }
            }, 100);
        },

        destroy() {
            clearTimeout(this.refreshTimer);
            window.Echo?.leave(this.channelName);
        },
    };
}
