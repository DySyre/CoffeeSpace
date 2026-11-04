export default function profileImageCropper(wire, options = {}) {
    let sourceImage = null;
    let objectUrl = null;
    let request = 0;
    let pointer = null;
    let toastTimer = null;

    return {
        shareKind: 'profile', profileQr: '', profileShareUrl: '', profileShareError: '', profileLinkCopied: false,
        toastTitle: '', toastVisible: false, toastKind: 'loading', toastMessage: '',
        tab: 'stories', editingBio: false, photoUploading: false, coverUploading: false,
        postRatio: 'original', postRatioLocked: false, originalRatio: 1,
        cropKind: 'photo', cropReady: false, cropBusy: false, cropError: '', cropProgress: 0,
        zoom: 1, offsetX: 0, offsetY: 0, baseScale: 1, disposed: false,

        async openProfileShare(url, name = 'CoffeeSpace', kind = 'profile') {
            this.shareKind = kind;
            this.profileQr = ''; 
            this.profileShareUrl = url;
            this.profileLinkCopied = false;
            this.profileShareError = '';
            this.$refs.shareDialog.showModal();
            try {
                const { default: QRCode } = await import('qrcode');
                const qr = await QRCode.toDataURL(url, { width: 540, margin: 4, errorCorrectionLevel: 'H', color: { dark: '#4b2c20', light: '#fffaf3' } });
                const { renderShareCard } = await import('./share-card.js');
                this.profileQr = await renderShareCard(qr, this.$refs.shareBrand?.querySelector('svg')?.outerHTML, name, kind, url);
            } catch { this.profileShareError = 'The QR code could not be generated. You can still copy the profile link.'; }
        },

        async copyProfileLink() {
            try { await navigator.clipboard.writeText(this.profileShareUrl); this.profileLinkCopied = true; }
            catch { this.profileShareError = 'Select the profile link above and copy it manually.'; }
        },

        showToast(kind, message, title = '') {
            clearTimeout(toastTimer);
            this.toastKind = kind;
            this.toastTitle = title;
            this.toastMessage = message;
            this.toastVisible = true;
            this.$nextTick?.(() => this.presentToast());
            if (kind === 'success') {
                toastTimer = setTimeout(() => this.hideToast(), 4500);
                toastTimer.unref?.();
            }
        },

        presentToast() {
            if (this.disposed || !this.toastVisible) return;
            const toast = this.$refs.cropDialog?.open ? this.$refs.cropToast : this.$refs.imageToast;
            const other = toast === this.$refs.cropToast ? this.$refs.imageToast : this.$refs.cropToast;
            other?.hidePopover?.();
            if (toast && !toast.matches(':popover-open')) toast.showPopover?.();
        },

        hideToast() {
            clearTimeout(toastTimer);
            this.toastVisible = false;
            this.$refs.imageToast?.hidePopover?.();
            this.$refs.cropToast?.hidePopover?.();
        },

        handleImageResult(result) {
            this.photoUploading = false;
            this.coverUploading = false;
            this.showToast(result.success ? 'success' : 'error', result.message);
        },

        async chooseImage(event, kind) {
            const file = event.target.files?.[0];
            event.target.value = '';
            if (!file || this.cropBusy) return;
            this.cropError = '';
            this.cropKind = kind;
            this.cropReady = false;
            this.$refs.cropDialog.showModal();
            const token = ++request;
            if (file.size > 10 * 1024 * 1024 || !/\.(jpe?g|png)$/i.test(file.name) || (file.type && !['image/jpeg', 'image/png'].includes(file.type))) {
                this.cropError = 'Choose a JPEG or PNG no larger than 10 MB.';
                return;
            }
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = URL.createObjectURL(file);
            const image = new Image();
            try {
                image.src = objectUrl;
                await image.decode();
                if (this.disposed || token !== request) return;
                sourceImage = image;
                if (kind === 'post' && !this.postRatioLocked) this.originalRatio = image.naturalWidth / image.naturalHeight;
                this.cropReady = true;
                this.resetCropFrame();
            } catch {
                if (token === request) this.cropError = 'This image could not be opened. Choose another JPEG or PNG.';
            }
        },

        setPostRatio(ratio) {
            if (this.cropKind !== 'post' || this.postRatioLocked || this.cropBusy || !['square', 'landscape', 'portrait', 'original'].includes(ratio)) return;
            this.postRatio = ratio;
            this.resetCropFrame();
        },

        resetCropFrame() {
            if (!sourceImage || !this.cropReady) return;
            const originalWidth = this.originalRatio >= 1 ? 1600 : Math.max(1, Math.round(1600 * this.originalRatio));
            const originalHeight = this.originalRatio >= 1 ? Math.max(1, Math.round(1600 / this.originalRatio)) : 1600;
            const sizes = { square: [1024, 1024], landscape: [1440, 810], portrait: [1080, 1350], original: [originalWidth, originalHeight] };
            const [width, height] = this.cropKind === 'cover' ? [1500, 500] : this.cropKind === 'post' ? sizes[this.postRatio] : [1024, 1024];
            const canvas = this.$refs.cropCanvas;
            canvas.width = width;
            canvas.height = height;
            this.baseScale = Math.max(width / sourceImage.naturalWidth, height / sourceImage.naturalHeight);
            this.zoom = 1;
            this.offsetX = 0;
            this.offsetY = 0;
            this.drawCrop();
        },

        get cropFrameStyle() {
            const ratio = this.cropKind === 'cover' ? 3 : this.cropKind === 'post' ? ({ square: 1, landscape: 16 / 9, portrait: 4 / 5, original: this.originalRatio }[this.postRatio]) : 1;
            return this.cropKind === 'cover' ? 'width:100%' : 'width:min(100%,' + (52 * ratio) + 'dvh)';
        },

        drawCrop() {
            if (!sourceImage || !this.cropReady) return;
            const canvas = this.$refs.cropCanvas;
            const width = sourceImage.naturalWidth * this.baseScale * Number(this.zoom);
            const height = sourceImage.naturalHeight * this.baseScale * Number(this.zoom);
            const maxX = Math.max(0, (width - canvas.width) / 2);
            const maxY = Math.max(0, (height - canvas.height) / 2);
            this.offsetX = Math.max(-maxX, Math.min(maxX, this.offsetX));
            this.offsetY = Math.max(-maxY, Math.min(maxY, this.offsetY));
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(sourceImage, (canvas.width - width) / 2 + this.offsetX, (canvas.height - height) / 2 + this.offsetY, width, height);
        },

        startDrag(event) {
            if (!this.cropReady || this.cropBusy || pointer) return;
            pointer = { id: event.pointerId, x: event.clientX, y: event.clientY };
            event.currentTarget.setPointerCapture(event.pointerId);
        },

        drag(event) {
            if (!pointer || pointer.id !== event.pointerId || this.cropBusy) return;
            const canvas = this.$refs.cropCanvas;
            const bounds = canvas.getBoundingClientRect();
            this.offsetX += (event.clientX - pointer.x) * canvas.width / bounds.width;
            this.offsetY += (event.clientY - pointer.y) * canvas.height / bounds.height;
            pointer.x = event.clientX;
            pointer.y = event.clientY;
            this.drawCrop();
        },

        nudge(x, y) {
            if (this.cropBusy) return;
            this.offsetX += x * this.$refs.cropCanvas.width / 30;
            this.offsetY += y * this.$refs.cropCanvas.height / 30;
            this.drawCrop();
        },

        stopDrag() { pointer = null; },

        async saveCrop() {
            if (!this.cropReady || this.cropBusy) return;
            this.cropBusy = true;
            this.cropError = '';
            const token = request;
            const blob = await new Promise(resolve => this.$refs.cropCanvas.toBlob(resolve, 'image/jpeg', 0.92));
            if (this.disposed || token !== request) return;
            if (!blob || blob.size > 10 * 1024 * 1024) {
                this.cropBusy = false;
                this.cropError = 'The cropped photo could not be prepared. Please choose a smaller image.';
                return;
            }
            const field = this.cropKind;
            this.photoUploading = field === 'photo';
            this.coverUploading = field === 'cover';
            this.cropProgress = 0;
            if (field !== 'post') this.showToast('loading', field === 'cover' ? 'Saving your cover photo...' : 'Saving your profile photo...');
            options.onUploadStart?.(this);
            wire.upload(options.uploadField ?? field, new File([blob], `${field}-crop.jpg`, { type: 'image/jpeg' }),
                () => { this.cropBusy = false; this.photoUploading = false; this.coverUploading = false; options.onUploadEnd?.(this); this.closeCrop(); },
                () => { this.cropBusy = false; this.photoUploading = false; this.coverUploading = false; options.onUploadEnd?.(this); this.cropError = 'Upload failed. Your current photo is unchanged. Please try again.'; if (field !== 'post') this.showToast('error', this.cropError); options.onUploadError?.(this); },
                event => { this.cropProgress = event.detail.progress; options.onProgress?.(this, this.cropProgress); },
            );
        },

        closeCrop() {
            if (this.cropBusy) return;
            request++;
            pointer = null;
            sourceImage = null;
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
            this.cropReady = false;
            if (this.$refs.cropDialog?.open) this.$refs.cropDialog.close();
            this.$nextTick?.(() => this.presentToast());
            options.onClosed?.(this);
        },

        destroy() {
            this.disposed = true;
            this.hideToast();
            this.cropBusy = false;
            this.closeCrop();
        },
    };
}
