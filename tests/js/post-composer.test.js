import test from 'node:test';
import assert from 'node:assert/strict';
import postComposer from '../../resources/js/post-composer.js';

function editor() {
    globalThis.Image = class { naturalWidth = 1200; naturalHeight = 800; async decode() {} };
    const uploads = [];
    const composer = postComposer({ upload: (...args) => uploads.push(args) });
    composer.$root = { querySelectorAll: () => [] };
    composer.$refs = {
        cropCanvas: { width: 0, height: 0, getContext: () => ({ fillRect() {}, drawImage() {} }), toBlob: cb => cb(new Blob(['crop'], { type: 'image/jpeg' })) },
        cropDialog: { open: false, showModal() { this.open = true; }, close() { this.open = false; } },
    };
    return { composer, uploads };
}
const photo = name => new File(['source'], name, { type: 'image/jpeg' });

test('multiple selections crop photos before upload and preserve video order', async () => {
    const { composer, uploads } = editor();
    composer.queueFiles([photo('first.jpg'), new File(['video'], 'visit.mp4', { type: 'video/mp4' }), photo('last.jpg')]);
    await Promise.resolve();
    assert.equal(uploads.length, 0);
    assert.equal(composer.cropKind, 'post');
    await composer.saveCrop();
    assert.equal(uploads[0][0], 'upload');
    assert.equal(uploads[0][1].name, 'post-crop.jpg');
    uploads[0][2]();
    assert.equal(uploads[1][1].name, 'visit.mp4');
    uploads[1][2]();
    await Promise.resolve();
    assert.equal(composer.batchPosition, 3);
    await composer.saveCrop();
    uploads[2][2]();
    assert.equal(composer.batchBusy, false);
    assert.equal(uploads.length, 3);
});

test('skipping a photo moves to the next crop without uploading the skipped file', async () => {
    const { composer, uploads } = editor();
    composer.queueFiles([photo('skip.jpg'), photo('keep.jpg')]);
    await Promise.resolve();
    composer.closeCrop();
    await Promise.resolve();
    assert.equal(composer.batchPosition, 2);
    assert.equal(composer.currentMedia.name, 'keep.jpg');
    assert.equal(uploads.length, 0);
    composer.destroy();
    assert.equal(composer.pendingMedia.length, 0);
});

test('invalid files are skipped and cannot stall the rest of the selection', async () => {
    const { composer, uploads } = editor();
    composer.queueFiles([new File(['bad'], 'bad.gif'), new File(['video'], 'valid.mov', { type: 'video/quicktime' })]);
    assert.match(composer.mediaError, /bad.gif/);
    assert.equal(uploads.length, 1);
    assert.equal(uploads[0][1].name, 'valid.mov');
    uploads[0][2]();
    assert.equal(composer.batchBusy, false);
});

test('the chosen post ratio stays identical across later photos and later selections', async () => {
    const { composer, uploads } = editor();
    composer.queueFiles([photo('first.jpg'), photo('second.jpg')]);
    await Promise.resolve();
    composer.setPostRatio('portrait');
    assert.equal(composer.$refs.cropCanvas.width / composer.$refs.cropCanvas.height, 4 / 5);
    await composer.saveCrop();
    assert.equal(composer.postRatioLocked, true);
    uploads[0][2]();
    await Promise.resolve();
    composer.setPostRatio('landscape');
    assert.equal(composer.postRatio, 'portrait');
    assert.equal(composer.$refs.cropCanvas.width / composer.$refs.cropCanvas.height, 4 / 5);
    await composer.saveCrop();
    uploads[1][2]();
    composer.queueFiles([photo('later.jpg')]);
    await Promise.resolve();
    assert.equal(composer.$refs.cropCanvas.width / composer.$refs.cropCanvas.height, 4 / 5);
    composer.destroy();
});

test('all three crop options export the selected dimensions before the first save', async () => {
    const { composer } = editor();
    composer.queueFiles([photo('first.jpg')]);
    await Promise.resolve();
    for (const [choice, ratio] of [['square', 1], ['landscape', 16 / 9], ['portrait', 4 / 5]]) {
        composer.setPostRatio(choice);
        assert.equal(composer.$refs.cropCanvas.width / composer.$refs.cropCanvas.height, ratio);
    }
    composer.closeCrop();
    composer.syncMedia(0);
    assert.equal(composer.postRatioLocked, false);
    composer.destroy();
});

test('video upload progress and transport failures use toasts', () => {
    const { composer, uploads } = editor();
    composer.queueFiles([new File(['video'], 'coffee.mp4', { type: 'video/mp4' })]);
    assert.equal(composer.toastKind, 'loading');
    assert.equal(composer.toastTitle, 'Uploading video');
    uploads[0][4]({ detail: { progress: 42 } });
    assert.match(composer.toastMessage, /42%/);
    uploads[0][3]();
    assert.equal(composer.toastKind, 'error');
    assert.equal(composer.batchBusy, false);
    composer.destroy();
});

test('Original retains the first photos native shape for the rest of the post', async () => {
    const { composer, uploads } = editor();
    composer.queueFiles([photo('first.jpg'), photo('second.jpg')]);
    await Promise.resolve();
    composer.setPostRatio('original');
    const firstDimensions = [composer.$refs.cropCanvas.width, composer.$refs.cropCanvas.height];
    assert.ok(Math.abs(firstDimensions[0] / firstDimensions[1] - 1.5) < 0.002);
    await composer.saveCrop();
    globalThis.Image = class { naturalWidth = 600; naturalHeight = 1200; async decode() {} };
    uploads[0][2]();
    await Promise.resolve();
    assert.equal(composer.originalRatio, 1.5);
    assert.deepEqual([composer.$refs.cropCanvas.width, composer.$refs.cropCanvas.height], firstDimensions);
    composer.destroy();
});

test('post video files accept 25 MB and skip files above that limit', () => {
    const { composer, uploads } = editor();
    composer.queueFiles([new File([new Uint8Array(25 * 1024 * 1024)], 'limit.mp4', { type: 'video/mp4' })]);
    assert.equal(uploads.length, 1);
    uploads[0][2]();
    composer.queueFiles([new File([new Uint8Array(25 * 1024 * 1024 + 1)], 'too-large.mp4', { type: 'video/mp4' })]);
    assert.equal(uploads.length, 1);
    assert.match(composer.mediaError, /25 MB/);
    assert.equal(composer.batchBusy, false);
    composer.destroy();
});
