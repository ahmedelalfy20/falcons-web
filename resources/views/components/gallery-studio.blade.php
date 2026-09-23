@props(['images', 'label' => __('Gallery'), 'interval' => 5000])
{{--
    Auto-playing photo "studio": one large stage with a crossfade + slow zoom, story-style
    progress bars, thumbnails, swipe, keyboard and a full-screen lightbox.
    Performance: only the current and next photos are requested; autoplay pauses when the
    stage is off-screen, the tab is hidden, the pointer hovers it, or the lightbox is open.
    Motion uses opacity/transform only and is disabled for prefers-reduced-motion.
--}}
@php
    $items = $images->values()->map(fn ($img) => [
        'full' => media($img->path),
        'thumb' => media($img->thumb_path ?: $img->path),
        'w' => $img->width ?: 1600,
        'h' => $img->height ?: 1000,
        'caption' => $img->tr('caption'),
    ]);
@endphp
<div x-data="studio(@js($items), {{ (int) $interval }})" {{ $attributes->class(['relative']) }}
     @keydown.arrow-right="dir() === 'rtl' ? prev() : next()" @keydown.arrow-left="dir() === 'rtl' ? next() : prev()">

    {{-- Stage --}}
    <div class="group relative aspect-[4/3] overflow-hidden rounded-[1.5rem] border border-line bg-ink-900 sm:aspect-[16/9] lg:rounded-[2rem]"
         x-ref="stage" role="region" aria-roledescription="{{ __('carousel') }}" aria-label="{{ $label }}"
         @mouseenter="hover = true" @mouseleave="hover = false"
         @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)">

        @foreach ($items as $i => $img)
            <figure class="absolute inset-0 transition-opacity duration-[900ms] ease-out"
                    :class="index === {{ $i }} ? 'opacity-100 z-10' : 'opacity-0 z-0'"
                    role="group" aria-roledescription="{{ __('slide') }}" aria-label="{{ __(':n of :total', ['n' => $i + 1, 'total' => $items->count()]) }}"
                    :aria-hidden="index !== {{ $i }}">
                <button type="button" class="block h-full w-full cursor-zoom-in" @click="openLightbox()" tabindex="-1" aria-label="{{ __('Open image :n of :total', ['n' => $i + 1, 'total' => $items->count()]) }}">
                    <img @if ($i === 0) src="{{ $img['full'] }}" loading="lazy" @else :src="seen[{{ $i }}] ? @js($img['full']) : ''" @endif
                         alt="{{ $img['caption'] ?: $label.' '.($i + 1) }}" width="{{ $img['w'] }}" height="{{ $img['h'] }}" decoding="async"
                         class="studio-kenburns h-full w-full object-cover"
                         :class="index === {{ $i }} && playing ? 'is-active' : ''">
                </button>
            </figure>
        @endforeach

        {{-- Top: story-style progress --}}
        @if ($items->count() > 10)
            {{-- Many photos: one continuous progress bar --}}
            <div class="pointer-events-none absolute inset-x-0 top-0 z-20 p-4 sm:p-5" aria-hidden="true">
                <span class="relative block h-[3px] overflow-hidden rounded-full bg-white/25">
                    <span class="absolute inset-y-0 start-0 rounded-full bg-white transition-[width] duration-700 ease-out" :style="{ width: ((index + 1) / images.length * 100) + '%' }"></span>
                </span>
            </div>
        @else
        <div class="pointer-events-none absolute inset-x-0 top-0 z-20 flex gap-1.5 p-4 sm:p-5" aria-hidden="true">
            <template x-for="(img, i) in images" :key="i">
                <span class="relative h-[3px] flex-1 overflow-hidden rounded-full bg-white/25">
                    <span class="absolute inset-0 origin-left rounded-full bg-white rtl:origin-right" x-show="i < index" style="transform: scaleX(1)"></span>
                    <span class="studio-progress absolute inset-0 origin-left rounded-full bg-white rtl:origin-right" x-show="i === index"
                          :style="{ animationDuration: interval + 'ms', animationPlayState: playing ? 'running' : 'paused' }"></span>
                </span>
            </template>
        </div>
        @endif

        {{-- Bottom: caption, counter, controls --}}
        <div class="absolute inset-x-0 bottom-0 z-20 bg-gradient-to-t from-ink-950/85 via-ink-950/35 to-transparent p-4 pt-16 sm:p-6 sm:pt-20">
            <div class="flex items-end justify-between gap-4">
                <div class="min-w-0">
                    <p class="num text-sm font-semibold text-white/80" dir="ltr"><span x-text="String(index + 1).padStart(2, '0')">01</span> <span class="text-white/40">/ {{ str_pad($items->count(), 2, '0', STR_PAD_LEFT) }}</span></p>
                    <p class="mt-1 truncate text-base font-semibold text-white sm:text-lg" x-text="images[index].caption || @js(__('Inside the Academy'))"></p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <button type="button" @click="toggle()" class="flex size-11 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur transition-colors hover:bg-white/25"
                            :aria-label="userPaused ? @js(__('Play slideshow')) : @js(__('Pause slideshow'))">
                        <x-icon name="pause" class="size-4" x-show="!userPaused" />
                        <x-icon name="play" class="ms-0.5 size-4" x-show="userPaused" x-cloak />
                    </button>
                    <button type="button" @click="prev()" class="flex size-11 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur transition-colors hover:bg-white/25" aria-label="{{ __('Previous') }}"><x-icon name="chevron-left" class="size-5" /></button>
                    <button type="button" @click="next()" class="flex size-11 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur transition-colors hover:bg-white/25" aria-label="{{ __('Next') }}"><x-icon name="chevron-right" class="size-5" /></button>
                    <button type="button" @click="openLightbox()" class="hidden size-11 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur transition-colors hover:bg-white/25 sm:flex" aria-label="{{ __('View full screen') }}"><x-icon name="expand" class="size-4" /></button>
                </div>
            </div>
        </div>
    </div>

    {{-- Thumbnails --}}
    <div class="mt-3 flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] sm:gap-3 [&::-webkit-scrollbar]:hidden" role="tablist" aria-label="{{ __('Choose a photo') }}">
        @foreach ($items as $i => $img)
            <button type="button" role="tab" @click="go({{ $i }})" :aria-selected="index === {{ $i }}"
                    x-effect="if (index === {{ $i }}) { const p = $el.parentElement, a = $el.getBoundingClientRect(), b = p.getBoundingClientRect(); p.scrollBy({ left: (a.left + a.width / 2) - (b.left + b.width / 2), behavior: 'smooth' }) }"
                    class="relative aspect-[4/3] w-24 shrink-0 overflow-hidden rounded-xl bg-ink-800 transition-[opacity,box-shadow] duration-300 sm:w-32"
                    :class="index === {{ $i }} ? 'opacity-100 ring-2 ring-brand-400 ring-offset-2 ring-offset-ink-950' : 'opacity-50 hover:opacity-90'"
                    aria-label="{{ __('Show image :n', ['n' => $i + 1]) }}">
                <img src="{{ $img['thumb'] }}" alt="" loading="lazy" decoding="async" width="256" height="192" class="h-full w-full object-cover">
            </button>
        @endforeach
    </div>

    {{-- Full-screen lightbox (full-size image requested only when opened) --}}
    <template x-teleport="body">
        <div x-show="lightbox" x-cloak role="dialog" aria-modal="true" aria-label="{{ $label }}"
             x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="opacity-0"
             x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="opacity-0"
             @keydown.escape.window="lightbox && closeLightbox()" @keydown.arrow-right.window="lightbox && (dir() === 'rtl' ? prev() : next())" @keydown.arrow-left.window="lightbox && (dir() === 'rtl' ? next() : prev())"
             @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)" @click.self="closeLightbox()"
             class="fixed inset-0 z-[150] flex items-center justify-center bg-ink-950/95 p-3 backdrop-blur-sm sm:p-8">
            <button type="button" x-ref="close" @click="closeLightbox()" class="absolute end-3 top-3 z-10 flex size-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:end-6 sm:top-6" aria-label="{{ __('Close') }}"><x-icon name="x" class="size-5" /></button>
            <button type="button" @click="prev()" class="absolute start-2 top-1/2 z-10 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:start-6" aria-label="{{ __('Previous') }}"><x-icon name="chevron-left" class="size-5" /></button>
            <button type="button" @click="next()" class="absolute end-2 top-1/2 z-10 flex size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:end-6" aria-label="{{ __('Next') }}"><x-icon name="chevron-right" class="size-5" /></button>
            <figure class="flex max-h-full w-full max-w-6xl flex-col items-center" @click.self="closeLightbox()">
                <img :src="lightbox ? images[index].full : ''" :alt="images[index].caption || @js($label)"
                     class="max-h-[calc(100dvh-7rem)] w-auto max-w-full rounded-lg object-contain">
                <figcaption class="num mt-3 text-sm text-white/70" dir="ltr" x-text="`${index + 1} / ${images.length}`"></figcaption>
            </figure>
        </div>
    </template>
</div>
