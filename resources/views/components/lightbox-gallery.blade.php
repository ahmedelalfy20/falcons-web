@props(['images' => null, 'items' => null, 'label' => __('Gallery'), 'variant' => 'mosaic'])
@php
    // Accepts GalleryImage models ($images) or pre-normalised arrays ($items: full, thumb, w, h, caption).
    $items = $items !== null
        ? collect($items)->values()
        : $images->values()->map(fn ($img) => [
            'full' => media($img->path),
            'thumb' => media($img->thumb_path ?: $img->path),
            'w' => $img->width ?: 1600,
            'h' => $img->height ?: 1000,
            'caption' => $img->tr('caption'),
        ]);
@endphp
<div x-data="lightbox(@js($items))" {{ $attributes }}>
    <ul @class([
            'grid gap-2 sm:gap-3',
            'grid-cols-2 md:grid-cols-4 md:auto-rows-[11rem] lg:auto-rows-[13rem]' => $variant === 'mosaic',
            'grid-cols-3 sm:grid-cols-4' => $variant === 'squares',
            'grid-cols-2 md:grid-cols-3' => $variant === 'wide' && ! in_array($items->count(), [2, 4], true),
            'grid-cols-2' => $variant === 'wide' && in_array($items->count(), [2, 4], true),
        ]) role="list" aria-label="{{ $label }}">
        @foreach ($items as $i => $img)
            @php
                // Mosaic: after the big tile + 4 small ones, rows hold 4; stretch a short last row so it has no gaps.
                $n = $items->count(); $tail = $n > 5 ? ($n - 5) % 4 : 0; $fromEnd = $n - 1 - $i;
                $span = $variant === 'mosaic' && $i >= 5 && $tail && $fromEnd < $tail
                    ? match ($tail) { 1 => 'md:col-span-4 md:row-span-2', 2 => 'md:col-span-2', 3 => $fromEnd === 0 ? 'md:col-span-2' : '', default => '' }
                    : '';
                // Mobile (2 columns): an odd last tile spans the full row.
                $mobileFull = $variant === 'mosaic' && $i > 0 && $i === $n - 1 && ($n - 1) % 2 === 1;
            @endphp
            <li @class([
                    'col-span-2 md:row-span-2' => $variant === 'mosaic' && $i === 0,
                    $span => $span !== '',
                    'col-span-2 md:col-span-1' => $mobileFull && $span === '',
                    'col-span-2' => $mobileFull && $span !== '',
                ])>
                <button type="button" @click="show({{ $i }})"
                        class="group relative block h-full w-full overflow-hidden rounded-xl bg-ink-800 {{ match ($variant) { 'squares' => 'aspect-square', 'wide' => 'aspect-video bg-white', default => $i === 0 ? 'aspect-[16/10] md:aspect-auto' : 'aspect-[4/3] md:aspect-auto' } }}"
                        aria-label="{{ __('Open image :n of :total', ['n' => $i + 1, 'total' => $items->count()]) }}">
                    <img src="{{ ($variant === 'mosaic' && $i === 0) ? $img['full'] : $img['thumb'] }}"
                         @if ($variant === 'mosaic' && $i === 0) srcset="{{ $img['thumb'] }} 640w, {{ $img['full'] }} 1600w" sizes="(min-width: 768px) 50vw, 100vw" @endif
                         alt="{{ $img['caption'] ?: $label.' '.($i + 1) }}" loading="lazy" decoding="async" width="640" height="400"
                         class="h-full w-full object-cover object-[50%_22%] transition-transform duration-500 ease-out group-hover:scale-[1.03]">
                    <span class="absolute inset-0 bg-ink-950/0 transition-colors duration-300 group-hover:bg-ink-950/20" aria-hidden="true"></span>
                </button>
            </li>
        @endforeach
    </ul>

    @include('partials.lightbox-dialog', ['label' => $label])
</div>
