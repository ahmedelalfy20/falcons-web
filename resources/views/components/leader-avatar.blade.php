{{-- Leader profile photo with an initials fallback. --}}
@props(['leader', 'full' => false])
@php $src = $leader->photoUrl(! $full); @endphp
<span {{ $attributes->merge(['class' => 'relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-brand-600/40 to-ink-700 font-bold text-fg ring-1 ring-white/10']) }}>
    @if ($src)
        <img src="{{ $src }}" alt="{{ $leader->name }}" class="size-full object-cover" loading="lazy" decoding="async">
    @else
        <span aria-hidden="true">{{ $leader->initials() }}</span><span class="sr-only">{{ $leader->name }}</span>
    @endif
</span>
