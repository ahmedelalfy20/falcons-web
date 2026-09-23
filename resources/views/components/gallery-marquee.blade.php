@props(['images', 'label' => __('Gallery')])
{{--
    Modern photo wall: two rows of rounded photos drifting in opposite directions (pure CSS,
    GPU transforms). Hover/focus pauses a row; clicking any photo opens the full-screen lightbox.
    Rows are rendered twice for a seamless loop; the copy is hidden from assistive tech.
    With prefers-reduced-motion the rows become a static, scrollable strip.
--}}
@php
    $items = $images->values()->map(fn ($img) => [
        'full' => media($img->path),
        'thumb' => media($img->thumb_path ?: $img->path),
        'w' => $img->width ?: 1600,
        'h' => $img->height ?: 1066,
        'caption' => $img->tr('caption'),
    ]);
    $rows = $items->count() >= 8
        ? [$items->filter(fn ($_, $i) => $i % 2 === 0), $items->filter(fn ($_, $i) => $i % 2 === 1)]
        : [$items];
    $speed = fn ($row) => max(40, $row->count() * 7);
@endphp
<div x-data="lightbox(@js($items))" {{ $attributes->class(['space-y-3 sm:space-y-4']) }}>
    @foreach ($rows as $r => $row)
        <div class="marquee marquee-gallery group/row" style="--marquee-duration: {{ $speed($row) }}s">
            <div @class(['marquee-track', 'marquee-reverse' => $r === 1])>
                @foreach ([false, true] as $clone)
                    <ul class="flex shrink-0 gap-3 pe-3 sm:gap-4 sm:pe-4" role="list" @if ($clone) aria-hidden="true" @else aria-label="{{ $label }}" @endif>
                        @foreach ($row as $i => $img)
                            <li class="shrink-0">
                                <button type="button" @click="show({{ $i }})" @if ($clone) tabindex="-1" @endif
                                        class="group relative block h-44 overflow-hidden rounded-2xl bg-ink-800 ring-1 ring-white/5 transition duration-500 ease-out hover:ring-brand-400/40 focus-visible:outline-2 focus-visible:outline-brand-400 sm:h-60 lg:h-72 sm:rounded-3xl"
                                        style="aspect-ratio: {{ $img['w'] }} / {{ $img['h'] }}"
                                        aria-label="{{ __('Open image :n of :total', ['n' => $i + 1, 'total' => $items->count()]) }}">
                                    <img src="{{ $img['thumb'] }}" alt="{{ $clone ? '' : ($img['caption'] ?: $label.' '.($i + 1)) }}" loading="lazy" decoding="async"
                                         width="640" height="{{ (int) round(640 * $img['h'] / $img['w']) }}"
                                         class="h-full w-full object-cover object-[50%_30%] transition duration-700 ease-out group-hover:scale-[1.05]">
                                    <span class="pointer-events-none absolute inset-0 bg-gradient-to-t from-ink-950/50 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100" aria-hidden="true"></span>
                                    <span class="pointer-events-none absolute end-3 bottom-3 flex size-9 scale-90 items-center justify-center rounded-full bg-white/90 text-ink-950 opacity-0 transition duration-300 group-hover:scale-100 group-hover:opacity-100" aria-hidden="true"><x-icon name="expand" class="size-4" /></span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>
    @endforeach

    @include('partials.lightbox-dialog', ['label' => $label])
</div>
