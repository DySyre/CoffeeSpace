@props(['inside' => false])
    <div x-ref="{{ $inside ? 'cropToast' : 'imageToast' }}" popover="manual" x-cloak x-show="toastVisible" x-bind:role="toastKind === 'error' ? 'alert' : 'status'" aria-live="polite" aria-atomic="true" class="fixed top-4 right-4 bottom-auto left-auto z-50 m-0 w-[min(90vw,380px)] rounded-md border border-line bg-surface p-4 text-ink shadow-lg">
        <div class="flex items-start gap-3">
            <span x-show="toastKind !== 'error'"><x-coffee-toast-motion/></span><span x-show="toastKind === 'error'" class="mt-1 shrink-0 text-red-700 dark:text-red-300"><x-coffee-icon name="warning"/></span>
            <div class="min-w-0 flex-1"><p class="text-sm font-semibold" x-text="toastTitle || (toastKind === 'loading' ? 'Saving photo' : toastKind === 'success' ? 'Photo updated' : 'Photo not saved')"></p><p class="mt-1 text-sm leading-6 text-muted" x-text="toastMessage"></p></div>
            <button type="button" x-on:click="hideToast()" aria-label="Dismiss notification" class="flex size-8 shrink-0 items-center justify-center text-xl text-muted">&times;</button>
        </div>
    </div>
