export default function pullToRefresh(wire) {
    let start = null;
    let moveListener;
    let startListener;
    let endListener;
    let cancelListener;
    return {
        pullDistance: 0,
        refreshing: false,
        refreshError: '',
        get refreshReady() { return this.pullDistance >= window.innerHeight * 0.3; },
        get indicatorHeight() { return this.refreshing ? 64 : Math.min(80, this.pullDistance * 0.35); },
        init() {
            startListener = event => this.startPull(event);
            moveListener = event => this.movePull(event);
            endListener = () => this.endPull();
            cancelListener = () => { start = null; this.pullDistance = 0; };
            this.$root.addEventListener('touchstart', startListener, { passive: true });
            this.$root.addEventListener('touchmove', moveListener, { passive: false });
            this.$root.addEventListener('touchend', endListener);
            this.$root.addEventListener('touchcancel', cancelListener);
        },
        startPull(event) {
            start = null;
            if (this.refreshing || window.scrollY > 0 || window.innerWidth >= 640 || event.touches.length !== 1 || event.target.closest('button, a, input, textarea, select, video, [role="slider"]')) return;
            start = { x: event.touches[0].clientX, y: event.touches[0].clientY };
            this.refreshError = '';
        },
        movePull(event) {
            if (!start) return;
            if (event.touches.length !== 1 || window.scrollY > 0) { start = null; this.pullDistance = 0; return; }
            const dy = event.touches[0].clientY - start.y;
            const dx = Math.abs(event.touches[0].clientX - start.x);
            if (dy <= 0 || dx > Math.abs(dy)) { start = null; this.pullDistance = 0; return; }
            if (dy < 8) return;
            if (event.cancelable) event.preventDefault();
            this.pullDistance = dy;
        },
        endPull() {
            const ready = start && this.refreshReady;
            start = null;
            if (ready) this.refreshFeed();
            else this.pullDistance = 0;
        },
        async refreshFeed() {
            if (this.refreshing) return;
            this.refreshing = true;
            this.refreshError = '';
            try { await wire.$refresh(); }
            catch { this.refreshError = 'The feed could not refresh. Pull down to try again.'; }
            finally { this.refreshing = false; this.pullDistance = 0; }
        },
        destroy() {
            start = null;
            this.$root.removeEventListener('touchstart', startListener);
            this.$root.removeEventListener('touchmove', moveListener);
            this.$root.removeEventListener('touchend', endListener);
            this.$root.removeEventListener('touchcancel', cancelListener);
        },
    };
}
