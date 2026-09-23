@php
    $cfg = (array) site('companies', [], false);
    $enabled = $cfg['enabled'] ?? true;
    $ar = app()->getLocale() === 'ar';
    $title = ($ar ? ($cfg['title_ar'] ?? null) : null) ?: ($cfg['title'] ?? null) ?: __('Companies Under Our Management');
    $subtitle = ($ar ? ($cfg['subtitle_ar'] ?? null) : null) ?: ($cfg['subtitle'] ?? null) ?: __('A growing group of businesses run and supported by the Falcons team.');
    // Repeat the list so one copy is always wider than the screen, then render it twice for a seamless loop.
    $count = $companies->count();
    $reps = $count ? (int) ceil(8 / $count) : 0;
    $strip = $count ? collect(array_fill(0, $reps, $companies))->flatten(1) : collect();
    $perItem = ['slow' => 6, 'normal' => 4, 'fast' => 2.5][$cfg['speed'] ?? 'normal'] ?? 4;
    $duration = max(20, $strip->count() * $perItem);
@endphp
@if ($enabled && $count)
    <section id="companies" class="relative overflow-hidden py-20 sm:py-24" aria-labelledby="companies-title">
        <div class="container-x text-center" data-reveal>
            <p class="eyebrow justify-center">{{ __('Our group') }}</p>
            <h2 id="companies-title" class="h-section mt-4">{{ $title }}</h2>
            <p class="lead mx-auto mt-4 max-w-2xl">{{ $subtitle }}</p>
        </div>

        <div class="marquee mt-12 sm:mt-14" style="--marquee-duration: {{ $duration }}s">
            <div class="marquee-track">
                @foreach ([false, true] as $clone)
                    <ul class="flex shrink-0 items-center" role="list" @if ($clone) aria-hidden="true" @else aria-label="{{ $title }}" @endif>
                        @foreach ($strip as $c)
                            @php $tag = $c->url ? 'a' : 'div'; @endphp
                            <li class="shrink-0 px-6 sm:px-10">
                                <{{ $tag }} @if ($c->url) href="{{ $c->url }}" target="_blank" rel="noopener" @if ($clone) tabindex="-1" @endif @endif
                                    title="{{ $c->tr('name') }}"
                                    @class([
                                        'group flex h-24 items-center justify-center transition-transform duration-300 hover:-translate-y-0.5 sm:h-28',
                                        'rounded-2xl bg-white/95 px-6' => $c->on_light,
                                    ])>
                                    <img src="{{ media($c->logo) }}" alt="{{ $clone ? '' : $c->tr('name') }}" loading="lazy" decoding="async"
                                         class="h-16 w-auto max-w-[200px] object-contain opacity-80 transition-opacity duration-300 group-hover:opacity-100 sm:h-20 sm:max-w-[240px]">
                                </{{ $tag }}>
                            </li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>
    </section>
@endif
