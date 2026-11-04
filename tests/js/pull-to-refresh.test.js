import test from 'node:test';
import assert from 'node:assert/strict';
import pullToRefresh from '../../resources/js/pull-to-refresh.js';

function feed(refresh = async () => {}) {
    globalThis.window = { innerWidth: 390, innerHeight: 800, scrollY: 0 };
    const state = pullToRefresh({ $refresh: refresh });
    return state;
}
function touch(x, y, interactive = false) {
    return { touches: [{ clientX: x, clientY: y }], target: { closest: () => interactive }, cancelable: true, preventDefault() { this.prevented = true; } };
}

test('a pull under 30 percent never refreshes and resets on release', () => {
    let calls = 0;
    const state = feed(async () => { calls++; });
    state.startPull(touch(100, 100));
    state.movePull(touch(100, 339));
    assert.equal(state.refreshReady, false);
    state.endPull();
    assert.equal(calls, 0);
    assert.equal(state.pullDistance, 0);
});

test('a 30 percent pull refreshes exactly once on release', async () => {
    let calls = 0;
    const state = feed(async () => { calls++; });
    state.startPull(touch(100, 100));
    const move = touch(100, 340);
    state.movePull(move);
    assert.equal(state.refreshReady, true);
    assert.equal(move.prevented, true);
    assert.equal(calls, 0);
    state.endPull();
    state.endPull();
    await Promise.resolve();
    assert.equal(calls, 1);
    assert.equal(state.refreshing, false);
    assert.equal(state.pullDistance, 0);
});

test('scrolling below the top, desktop and interactive controls do not claim the gesture', () => {
    for (const mode of ['scrolled', 'desktop', 'control', 'horizontal']) {
        let calls = 0;
        const state = feed(async () => { calls++; });
        if (mode === 'scrolled') window.scrollY = 100;
        if (mode === 'desktop') window.innerWidth = 1280;
        state.startPull(touch(100, 100, mode === 'control'));
        const move = touch(mode === 'horizontal' ? 500 : 100, 350);
        state.movePull(move);
        state.endPull();
        assert.equal(calls, 0);
        assert.equal(move.prevented, undefined);
    }
});

test('refresh failures release the indicator and allow retry', async () => {
    const state = feed(async () => { throw new Error('Offline'); });
    await state.refreshFeed();
    assert.equal(state.refreshing, false);
    assert.equal(state.pullDistance, 0);
    assert.match(state.refreshError, /could not refresh/);
});
