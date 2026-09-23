@props(['eyebrow' => null, 'title', 'lead' => null, 'align' => 'start', 'id' => null])
<div {{ $attributes->class(['max-w-2xl', 'mx-auto text-center' => $align === 'center']) }} data-reveal>
    @if ($eyebrow)
        <p @class(['eyebrow', 'justify-center' => $align === 'center'])>{{ $eyebrow }}</p>
    @endif
    <h2 @if($id) id="{{ $id }}" @endif class="h-section mt-4 text-balance">{!! $title !!}</h2>
    @if ($lead)
        <p class="lead mt-4 text-pretty">{{ $lead }}</p>
    @endif
</div>
