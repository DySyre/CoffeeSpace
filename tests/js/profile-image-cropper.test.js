import test from 'node:test';
import assert from 'node:assert/strict';
import profileImageCropper from '../../resources/js/profile-image-cropper.js';

function editor() {
    globalThis.Image = class {
        naturalWidth = 3000;
        naturalHeight = 2000;
        async decode() {}
    };
    const draws = [];
    const uploads = [];
    const canvas = {
        width: 0, height: 0,
        getContext: () => ({ fillRect() {}, drawImage: (...args) => draws.push(args) }),
        getBoundingClientRect: () => ({ width: 300, height: 100 }),
        toBlob: callback => callback(new Blob(['cropped-image'], { type: 'image/jpeg' })),
    };
    const cropper = profileImageCropper({ upload: (...args) => uploads.push(args) });
    cropper.$refs = { cropCanvas: canvas, cropDialog: { open: false, showModal() { this.open = true; }, close() { this.open = false; } } };
    return { cropper, canvas, draws, uploads };
}

const selection = file => ({ target: { files: [file], value: 'photo.jpg' } });
const photo = () => new File(['source'], 'photo.jpg', { type: 'image/jpeg' });

test('choosing and cancelling a profile crop never uploads the original', async () => {
    const { cropper, canvas, uploads } = editor();
    await cropper.chooseImage(selection(photo()), 'photo');
    assert.equal(canvas.width, canvas.height);
    assert.equal(cropper.cropReady, true);
    assert.equal(uploads.length, 0);
    cropper.closeCrop();
    assert.equal(cropper.$refs.cropDialog.open, false);
    assert.equal(uploads.length, 0);
});

test('zoomed cover crops stay filled even after dragging far beyond every edge', async () => {
    const { cropper, canvas, draws } = editor();
    await cropper.chooseImage(selection(photo()), 'cover');
    assert.equal(canvas.width / canvas.height, 3);
    cropper.zoom = 3;
    for (const direction of [-1, 1]) {
        cropper.nudge(direction * 1000, direction * 1000);
        const [, left, top, width, height] = draws.at(-1);
        assert.ok(left <= 0 && top <= 0);
        assert.ok(left + width >= canvas.width && top + height >= canvas.height);
    }
    cropper.destroy();
});

test('saving exports the cover as a JPEG to the cover field only', async () => {
    const { cropper, uploads } = editor();
    await cropper.chooseImage(selection(photo()), 'cover');
    await cropper.saveCrop();
    assert.equal(uploads.length, 1);
    assert.equal(uploads[0][0], 'cover');
    assert.equal(uploads[0][1].type, 'image/jpeg');
    assert.equal(uploads[0][1].name, 'cover-crop.jpg');
    assert.equal(cropper.cropBusy, true);
    uploads[0][2]();
    assert.equal(cropper.$refs.cropDialog.open, false);
    assert.equal(cropper.cropBusy, false);
});

test('failed uploads keep the crop open for retry', async () => {
    const { cropper, uploads } = editor();
    await cropper.chooseImage(selection(photo()), 'photo');
    await cropper.saveCrop();
    uploads[0][3]();
    assert.equal(cropper.$refs.cropDialog.open, true);
    assert.equal(cropper.cropBusy, false);
    assert.match(cropper.cropError, /unchanged/);
    cropper.destroy();
});

test('unsupported and oversized source images cannot be cropped or uploaded', async () => {
    for (const file of [new File(['gif'], 'photo.gif', { type: 'image/gif' }), new File([new Uint8Array(10 * 1024 * 1024 + 1)], 'large.jpg', { type: 'image/jpeg' })]) {
        const { cropper, uploads } = editor();
        await cropper.chooseImage(selection(file), 'photo');
        assert.equal(cropper.cropReady, false);
        assert.match(cropper.cropError, /10 MB/);
        await cropper.saveCrop();
        assert.equal(uploads.length, 0);
        cropper.destroy();
    }
});

test('upload completion does not announce success before the server confirms persistence', async () => {
    const { cropper, uploads } = editor();
    await cropper.chooseImage(selection(photo()), 'cover');
    await cropper.saveCrop();
    assert.equal(cropper.toastKind, 'loading');
    uploads[0][2]();
    assert.equal(cropper.toastKind, 'loading');
    cropper.handleImageResult({ success: true, message: 'Your cover photo has been updated.' });
    assert.equal(cropper.toastKind, 'success');
    assert.equal(cropper.toastVisible, true);
    cropper.destroy();
});

test('server save failures replace the loading toast with an error', async () => {
    const { cropper } = editor();
    await cropper.chooseImage(selection(photo()), 'cover');
    await cropper.saveCrop();
    cropper.handleImageResult({ success: false, message: 'Your photo could not be saved. Please try again.' });
    assert.equal(cropper.toastKind, 'error');
    assert.equal(cropper.coverUploading, false);
    cropper.destroy();
});
