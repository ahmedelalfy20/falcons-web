    {{-- Lightbox dialog: full-size image is only requested when opened --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak role="dialog" aria-modal="true" aria-label="{{ $label }}"
             x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0"
             x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="opacity-0"
             @keydown.escape.window="open && close()" @keydown.arrow-right.window="open && (document.dir === 'rtl' ? prev() : next())" @keydown.arrow-left.window="open && (document.dir === 'rtl' ? next() : prev())"
             @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)" @click.self="close()"
             class="fixed inset-0 z-[150] flex items-center justify-center bg-ink-950/95 p-3 backdrop-blur-sm sm:p-8">
            <button type="button" x-ref="close" @click="close()" class="absolute end-3 top-3 z-10 flex size-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:end-6 sm:top-6" aria-label="{{ __('Close') }}"><x-icon name="x" class="size-5" /></button>
            <button type="button" @click="prev()" x-show="images.length > 1" class="absolute start-2 top-1/2 z-10 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:start-6" aria-label="{{ __('Previous') }}"><x-icon name="chevron-left" class="size-5" /></button>
            <button type="button" @click="next()" x-show="images.length > 1" class="absolute end-2 top-1/2 z-10 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:end-6" aria-label="{{ __('Next') }}"><x-icon name="chevron-right" class="size-5" /></button>

            <figure class="relative flex max-h-full w-full max-w-6xl flex-col items-center" @click.self="close()">
                <div class="relative flex w-full items-center justify-center" :style="`aspect-ratio: ${current.w} / ${current.h}; max-height: calc(100dvh - 7rem)`">
                    <div x-show="!loaded" class="absolute inset-0 flex items-center justify-center" aria-hidden="true">
                        <span class="size-8 animate-spin rounded-full border-2 border-white/20 border-t-white"></span>
                    </div>
                    <img x-ref="img" :src="open ? current.full : ''" :alt="current.caption || @js($label)" @load="loaded = true"
                         class="max-h-[calc(100dvh-7rem)] w-auto max-w-full rounded-lg object-contain transition-opacity duration-300"
                         :class="loaded ? 'opacity-100' : 'opacity-0'">
                </div>
                <figcaption class="mt-3 text-sm text-white/70"><span class="num" x-text="`${index + 1} / ${images.length}`"></span><span x-show="current.caption" x-text="' — ' + current.caption"></span></figcaption>
            </figure>
        </div>
    </template>
