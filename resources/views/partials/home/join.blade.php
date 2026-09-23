@php $s = (array) site('stats', [], false); @endphp
<section aria-labelledby="join-title" class="relative overflow-hidden py-24 sm:py-32">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(45%_60%_at_50%_100%,rgba(34,197,132,0.13),transparent_70%)]" aria-hidden="true"></div>
    <div class="container-x relative text-center">
        <div class="flex flex-wrap justify-center gap-2" data-reveal>
            @if (! empty($s['active_members']))<span class="chip">{{ number_format($s['active_members']) }}+ {{ __('Members') }}</span>@endif
            @if (! empty($s['countries']))<span class="chip">{{ $s['countries'] }} {{ __('Countries') }}</span>@endif
        </div>
        <h2 id="join-title" class="h-display mx-auto mt-6 max-w-3xl text-balance" data-reveal>{!! __('Join the :word', ['word' => '<span class="text-brand-400">'.e(__('Movement')).'</span>']) !!}</h2>
        <p class="mt-5 text-xl font-semibold text-fg sm:text-2xl" data-reveal>{{ __('Take control of your future today.') }}</p>
        <p class="lead mx-auto mt-4 max-w-2xl" data-reveal>{{ __('Transform your financial destiny with one of the most comprehensive trading academies. Join thousands who have already changed their lives.') }}</p>
        <div class="mt-10" data-reveal>
            <a href="{{ whatsapp_url(__('Hello, I want to start my trading journey with Falcons Academy')) }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg">
                {{ __('Start Your Journey') }} <x-icon name="arrow-right" class="size-4" />
            </a>
        </div>
        <ul class="mx-auto mt-10 flex max-w-3xl flex-col items-center justify-center gap-3 text-sm text-fg-muted sm:flex-row sm:gap-8" data-reveal>
            @foreach ([__('Instant access to premium content'), __('Direct mentorship from elite traders'), __('Proven strategies that work')] as $b)
                <li class="flex items-center gap-2"><x-icon name="check-circle" class="size-4 text-brand-400" /> {{ $b }}</li>
            @endforeach
        </ul>
        <p class="mt-12 text-sm text-fg-subtle" data-reveal><span class="font-semibold text-fg-muted">{{ __('Falcons Academy') }}</span> — {{ __('Building Traders, Building Leaders.') }}</p>
    </div>
</section>
