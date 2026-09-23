@props(['store', 'url', 'size' => 'lg'])
@php
    $badges = [
        'play' => [
            'label' => __('Get it on'), 'store' => 'Google Play',
            'svg' => '<svg viewBox="0 0 24 24" class="size-7" aria-hidden="true"><path fill="#00D7FE" d="M3.6 1.8 13.5 12 3.6 22.2c-.4-.2-.6-.7-.6-1.2V3c0-.5.2-1 .6-1.2Z"/><path fill="#FFCE00" d="M17 8.6 13.5 12l3.5 3.4 3.9-2.2c1.1-.6 1.1-1.8 0-2.4L17 8.6Z"/><path fill="#FF3A44" d="M13.5 12 3.6 22.2c.3.2.8.2 1.3-.1L17 15.4 13.5 12Z"/><path fill="#00F076" d="M13.5 12 17 8.6 4.9 1.9c-.5-.3-1-.3-1.3-.1L13.5 12Z"/></svg>',
        ],
        'appstore' => [
            'label' => __('Download on the'), 'store' => 'App Store',
            'svg' => '<svg viewBox="0 0 24 24" class="size-7" aria-hidden="true"><path fill="currentColor" d="M16.4 12.7c0-2.4 2-3.6 2.1-3.7-1.2-1.7-3-1.9-3.6-2-1.5-.2-3 .9-3.8.9-.8 0-2-.9-3.3-.9-1.7 0-3.3 1-4.2 2.5-1.8 3.1-.5 7.7 1.3 10.2.9 1.2 1.9 2.6 3.2 2.6 1.3-.1 1.8-.8 3.3-.8 1.6 0 2 .8 3.3.8 1.4 0 2.3-1.3 3.1-2.5 1-1.4 1.4-2.8 1.4-2.9-.1 0-2.8-1.1-2.8-4.2ZM13.9 5.3c.7-.9 1.2-2 1-3.2-1 .1-2.2.7-2.9 1.5-.6.7-1.2 1.9-1 3.1 1.1.1 2.2-.6 2.9-1.4Z"/></svg>',
        ],
    ];
@endphp
@php $b = $badges[$store]; $sm = $size === 'sm'; @endphp
<a href="{{ $url }}" target="_blank" rel="noopener" dir="ltr" aria-label="{{ $b['label'] }} {{ $b['store'] }}"
   {{ $attributes->class(['group flex items-center justify-center gap-3 border border-white/15 bg-black text-start text-white transition hover:-translate-y-0.5 hover:border-white/35 hover:shadow-lg hover:shadow-black/50',
        'rounded-2xl px-6 py-3.5' => ! $sm, 'rounded-xl px-3.5 py-2' => $sm]) }}>
    <span @class(['[&>svg]:size-5' => $sm])>{!! $b['svg'] !!}</span>
    <span class="leading-tight">
        <span @class(['block uppercase tracking-wide text-white/70', 'text-[0.7rem]' => ! $sm, 'text-[0.6rem]' => $sm])>{{ $b['label'] }}</span>
        <span @class(['block font-semibold', 'text-xl' => ! $sm, 'text-sm' => $sm])>{{ $b['store'] }}</span>
    </span>
</a>
