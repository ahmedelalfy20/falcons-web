{{-- Avatar for Alpine-rendered rows: expects {photo, initials} on the given JS variable. --}}
@props(['row' => 'row'])
<span {{ $attributes->merge(['class' => 'relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-brand-600/40 to-ink-700 font-bold text-fg ring-1 ring-white/10']) }} aria-hidden="true">
    <template x-if="{{ $row }}.photo"><img :src="{{ $row }}.photo" alt="" class="size-full object-cover" loading="lazy" decoding="async"></template>
    <template x-if="!{{ $row }}.photo"><span x-text="{{ $row }}.initials"></span></template>
</span>
