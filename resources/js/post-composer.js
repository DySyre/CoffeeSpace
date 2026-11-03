export default function postComposer(wire) {
    return {
        step: 'media',
        activeMedia: 0,
        mediaCount: 0,

        syncMedia(count) {
            if (count > this.mediaCount) {
                this.activeMedia = count - 1;
                this.uploading = false;
                this.progress = 100;
            }
            this.mediaCount = count;
            this.activeMedia = Math.max(0, Math.min(this.activeMedia, count - 1));
            if (!count) this.step = 'media';
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
            if (!this.mediaCount || this.uploading) return;
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
            this.mediaError = '';
            if (file.size > 10 * 1024 * 1024) {
                this.mediaError = 'This photo exceeds 10 MB. Choose a smaller photo.';
                return;
            }
            this.uploading = true;
            this.progress = 0;
            wire.upload('upload', file,
                () => { this.uploading = false; },
                () => { this.uploading = false; this.mediaError = 'The photo could not be added. Please try again.'; },
                event => { this.progress = event.detail.progress; },
            );
        },

        recordOnDevice() {
            this.closeCamera();
            this.$refs.deviceVideo.click();
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
            this.locationRequest++;
            this.closeCamera();
        },
    };
}
