@props(['src', 'title'])

<div
    x-data="{
        thumbnail: '',
        failed: false,
        loadPreview() {
            const video = this.$refs.preview;
            video.src = @js($src);
            video.load();
        },
        captureFrame(event) {
            const video = event.target;
            if (!video.videoWidth || !video.videoHeight) return;

            const size = 480;
            const scale = Math.max(size / video.videoWidth, size / video.videoHeight);
            const cropWidth = size / scale;
            const cropHeight = size / scale;
            const canvas = document.createElement('canvas');
            canvas.width = size;
            canvas.height = size;
            const context = canvas.getContext('2d');
            if (!context) return;

            context.drawImage(
                video,
                (video.videoWidth - cropWidth) / 2,
                (video.videoHeight - cropHeight) / 2,
                cropWidth,
                cropHeight,
                0,
                0,
                size,
                size,
            );
            this.thumbnail = canvas.toDataURL('image/jpeg', 0.72);
            video.pause();
        },
    }"
    x-intersect.once="loadPreview()"
    class="relative size-full overflow-hidden bg-black"
>
    <video
        x-show="!thumbnail && !failed"
        x-ref="preview"
        muted
        playsinline
        preload="metadata"
        tabindex="-1"
        aria-hidden="true"
        x-on:loadedmetadata="$el.currentTime = Math.min(.1, $el.duration / 2)"
        x-on:seeked="captureFrame($event)"
        x-on:error="failed = true"
        class="size-full object-cover"
    ></video>
    <img x-cloak x-show="thumbnail" x-bind:src="thumbnail" alt="" class="absolute inset-0 size-full object-cover">
    <span x-cloak x-show="failed" class="absolute inset-0 flex items-center justify-center text-sm font-medium text-white">Video</span>
    <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-gradient-to-t from-black/40 via-transparent to-transparent text-white">
        <span class="flex size-11 items-center justify-center rounded-full border border-white/70 bg-black/35">
            <x-lucide-play class="ml-0.5 size-5 fill-current" aria-hidden="true"/>
        </span>
    </span>
    <span class="pointer-events-none absolute right-2 bottom-2 rounded-sm bg-black/65 px-2 py-1 text-[10px] font-semibold tracking-wide text-white">VIDEO</span>
    <span class="sr-only">Video thumbnail: {{ $title }}</span>
</div>
