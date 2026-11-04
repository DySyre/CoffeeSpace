import profileImageCropper from './profile-image-cropper.js';

export default function postComposer(wire) {
    const cropper = profileImageCropper(wire, {
        uploadField: 'upload',
        onUploadStart: editor => { editor.uploading = true; editor.postRatioLocked = true; editor.showToast('loading', 'Uploading your cropped photo...', 'Uploading photo'); },
        onProgress: (editor, progress) => { editor.toastMessage = 'Uploading photo... ' + progress + '%'; },
        onUploadError: editor => editor.showToast('error', 'Your photo could not be uploaded. Please try again.', 'Upload failed'),
        onUploadEnd: editor => { editor.uploading = false; },
        onClosed: editor => { editor.currentMedia = null; editor.processNextMedia(); },
    });
    return {
        ...cropper,
        pinOpen: false,
        pendingMedia: [],
        currentMedia: null,
        batchBusy: false,
        batchTotal: 0,
        batchPosition: 0,

        selectFiles(event) {
            const files = Array.from(event.target.files ?? []);
            event.target.value = '';
            this.queueFiles(files);
        },

        queueFiles(files) {
            if (this.batchBusy || !files.length) return;
            this.pausePreviews();
            this.pendingMedia = files;
            this.batchTotal = files.length;
            this.batchPosition = 0;
            this.batchBusy = true;
            this.mediaError = '';
            this.processNextMedia();
        },

        processNextMedia() {
            if (this.disposed) return;
            const file = this.pendingMedia.shift();
            if (!file) {
                this.batchBusy = false;
                this.currentMedia = null;
                return;
            }
            this.currentMedia = file;
            this.batchPosition++;
            if (file.size > (/\.(mp4|mov)$/i.test(file.name) ? 25 : 10) * 1024 * 1024 || !/\.(jpe?g|png|mp4|mov)$/i.test(file.name)) {
                this.mediaError = file.name + ' was skipped. Photos: JPEG/PNG up to 10 MB. Videos: MP4/MOV up to 25 MB and 60 seconds.';
                this.showToast('error', this.mediaError, 'File skipped');
                this.processNextMedia();
                return;
            }
            if (/\.(jpe?g|png)$/i.test(file.name)) {
                this.chooseImage({ target: { files: [file], value: '' } }, 'post');
            } else {
                this.uploading = true;
                this.progress = 0;
                this.showToast('loading', 'Uploading video ' + this.batchPosition + ' of ' + this.batchTotal + '...', 'Uploading video');
                wire.upload('upload', file,
                    () => { this.uploading = false; this.processNextMedia(); },
                    () => { this.uploading = false; this.mediaError = file.name + ' could not be uploaded.'; this.showToast('error', this.mediaError, 'Upload failed'); this.processNextMedia(); },
                    event => { this.progress = event.detail.progress; this.toastMessage = 'Uploading video... ' + this.progress + '%'; },
                );
            }
        },
        step: 'media',
        activeMedia: 0,
        mediaCount: 0,

        syncMedia(count) {
            if (count > this.mediaCount) {
                this.activeMedia = count - 1;
                this.uploading = false;
                this.progress = 100;
                if (!this.batchBusy) this.showToast('success', 'Your media is ready. Continue to write your story.', 'Media added');
            }
            this.mediaCount = count;
            this.activeMedia = Math.max(0, Math.min(this.activeMedia, count - 1));
            if (!count) {
                this.step = 'media';
                if (!this.batchBusy) this.postRatioLocked = false;
            }
        },

        pausePreviews() {
            this.$root.querySelectorAll('video').forEach(video => {
                if (video !== this.$refs.cameraPreview) video.pause();
            });
        },

        selectMedia(index) {
            this.pausePreviews();
            this.activeMedia = index;
        },

        nextStep() {
            if (!this.mediaCount || this.uploading || this.batchBusy) return;
            this.pausePreviews();
            this.step = 'details';
            this.$nextTick(() => this.$root.querySelector('fieldset input')?.focus());
        },

        uploading: false,
        progress: 0,
        mediaError: '',
        cameraStarting: false,
        cameraReady: false,
        cameraError: '',
        locating: false,
        locationError: '',
        candidate: null,
        attachedPin: false,
        stream: null,
        cameraRequest: 0,
        locationRequest: 0,
        disposed: false,

        async openCamera() {
            this.cameraError = '';
            if (!navigator.mediaDevices?.getUserMedia || !window.isSecureContext) {
                this.cameraError = 'Live camera needs HTTPS or localhost. You can still choose a photo or use your device camera.';
                return;
            }
            this.$refs.cameraDialog.showModal();
            this.cameraStarting = true;
            const request = ++this.cameraRequest;
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: false,
                });
                if (this.disposed || request !== this.cameraRequest) {
                    stream.getTracks().forEach(track => track.stop());
                    return;
                }
                this.stream = stream;
                this.$refs.cameraPreview.srcObject = stream;
                await this.$refs.cameraPreview.play();
                this.cameraReady = true;
            } catch (error) {
                if (this.disposed || request !== this.cameraRequest) return;
                this.cameraError = error.name === 'NotAllowedError'
                    ? 'Camera access was denied. Allow it in your browser settings or choose a photo instead.'
                    : 'The camera is unavailable or already in use. Try your device camera or choose a photo instead.';
                this.stream?.getTracks().forEach(track => track.stop());
                this.stream = null;
            } finally {
                if (!this.disposed && request === this.cameraRequest) this.cameraStarting = false;
            }
        },

        closeCamera() {
            this.cameraRequest++;
            this.stream?.getTracks().forEach(track => track.stop());
            this.stream = null;
            this.cameraReady = false;
            this.cameraStarting = false;
            if (this.$refs.cameraPreview) this.$refs.cameraPreview.srcObject = null;
            if (this.$refs.cameraDialog?.open) this.$refs.cameraDialog.close();
        },

        async takePhoto() {
            const video = this.$refs.cameraPreview;
            if (!this.cameraReady || !video.videoWidth) return;
            const request = this.cameraRequest;
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.9));
            if (!blob || this.disposed || request !== this.cameraRequest) return;
            this.closeCamera();
            this.sendPhoto(new File([blob], `coffee-photo-${Date.now()}.jpg`, { type: 'image/jpeg' }));
        },

        sendPhoto(file) {
            this.queueFiles([file]);
        },

        recordOnDevice() {
            this.closeCamera();
            this.$refs.deviceVideo.click();
        },

        previewPlace(place) {
            this.locationRequest++;
            this.locating = false;
            this.locationError = '';
            this.candidate = { latitude: place.latitude, longitude: place.longitude, accuracy: null, name: place.name };
            this.attachedPin = false;
            wire.set('latitude', null, false);
            wire.set('longitude', null, false);
        },

        locate() {
            this.locationError = '';
            if (!navigator.geolocation || !window.isSecureContext) {
                this.locationError = 'Location is unavailable here. You can continue with the café name and area.';
                return;
            }
            this.locating = true;
            const request = ++this.locationRequest;
            navigator.geolocation.getCurrentPosition(position => {
                if (this.disposed || request !== this.locationRequest) return;
                this.locating = false;
                this.candidate = {
                    latitude: Number(position.coords.latitude.toFixed(7)),
                    longitude: Number(position.coords.longitude.toFixed(7)),
                    accuracy: Math.round(position.coords.accuracy),
                };
                this.attachedPin = false;
                wire.set('latitude', null, false);
                wire.set('longitude', null, false);
            }, error => {
                if (this.disposed || request !== this.locationRequest) return;
                this.locating = false;
                this.locationError = error.code === 1
                    ? 'Location permission was denied. You can still enter the café name and area.'
                    : 'We could not get your location. Try again or continue without a pin.';
            }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
        },

        mapUrl() {
            if (!this.candidate) return '';
            const { latitude, longitude } = this.candidate;
            const params = new URLSearchParams({
                bbox: `${longitude - 0.003},${latitude - 0.002},${longitude + 0.003},${latitude + 0.002}`,
                layer: 'mapnik', marker: `${latitude},${longitude}`,
            });
            return `https://www.openstreetmap.org/export/embed.html?${params}`;
        },

        attachPin() {
            if (!this.candidate) return;
            wire.set('latitude', this.candidate.latitude, false);
            wire.set('longitude', this.candidate.longitude, false);
            this.attachedPin = true;
        },

        removePin() {
            this.locationRequest++;
            this.locating = false;
            this.candidate = null;
            this.attachedPin = false;
            this.locationError = '';
            wire.set('latitude', null, false);
            wire.set('longitude', null, false);
        },

        destroy() {
            this.disposed = true;
            this.pendingMedia = [];
            cropper.destroy.call(this);
            this.locationRequest++;
            this.closeCamera();
        },
    };
}
