{{--
    Scanner showcase — fully data-driven. Every active scanner in the database becomes
    a tab (desktop) and a swipe card (mobile). Adding a scanner in the admin needs no template change.
--}}
@if ($scanners->isNotEmpty())
<section id="scanners" aria-labelledby="scanners-title" class="py-20 sm:py-28" x-data="{ active: 0, count: {{ $scanners->count() }} }">
    <div class="container-x">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <x-section-heading id="scanners-title" :eyebrow="__('Trading Tools')" :title="__('Our Scanners')"
                :lead="__('Professional trading indicators designed to give you a competitive edge in the markets with precise entry points and clear targets.')" />
            <a href="{{ whatsapp_url(__('Hello, I want to know more about the scanners')) }}" target="_blank" rel="noopener" class="btn btn-outline self-start lg:self-auto" data-reveal>
                {{ __('Ask about access') }} <x-icon name="arrow-up-right" class="size-4" />
            </a>
        </div>

        {{-- Desktop: list + large preview --}}
        <div class="mt-14 hidden gap-10 lg:grid lg:grid-cols-12" data-reveal>
            <div class="lg:col-span-5" role="tablist" aria-label="{{ __('Scanners') }}" aria-orientation="vertical"
                 @keydown.arrow-down.prevent="active = (active + 1) % count; $nextTick(() => $el.querySelectorAll('[role=tab]')[active].focus())"
                 @keydown.arrow-up.prevent="active = (active - 1 + count) % count; $nextTick(() => $el.querySelectorAll('[role=tab]')[active].focus())">
                @foreach ($scanners as $i => $scanner)
                    <button type="button" role="tab" id="scanner-tab-{{ $scanner->slug }}" aria-controls="scanner-panel-{{ $scanner->slug }}"
                            :aria-selected="active === {{ $i }}" :tabindex="active === {{ $i }} ? 0 : -1"
                            @click="active = {{ $i }}" @mouseenter="active = {{ $i }}"
                            style="--accent: {{ $scanner->accent }}"
                            class="group relative flex w-full items-start gap-5 border-t border-line py-6 text-start last:border-b">
                        <span class="absolute start-0 top-0 h-px transition-[width] duration-500 ease-[var(--ease-out-soft)]" style="background: var(--accent)" :style="{ width: active === {{ $i }} ? '100%' : '0%' }" aria-hidden="true"></span>
                        <span class="num pt-1 text-sm font-semibold transition-colors" :class="active === {{ $i }} ? 'text-fg' : 'text-fg-subtle'">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-2xl font-bold tracking-tight transition-colors xl:text-[1.7rem]" :class="active === {{ $i }} ? 'text-fg' : 'text-fg-muted group-hover:text-fg'">{{ $scanner->tr('name') }}</span>
                            <span class="mt-1 block text-sm transition-colors" :class="active === {{ $i }} ? 'text-[var(--accent)]' : 'text-fg-subtle'"><bdi>{{ $scanner->tr('tagline') }}</bdi></span>
                        </span>
                        <x-icon name="arrow-right" class="mt-2 size-5 shrink-0 transition-all duration-300" x-bind:class="active === {{ $i }} ? 'opacity-100 text-fg' : 'opacity-0 -translate-x-2 rtl:translate-x-2'" />
                    </button>
                @endforeach
            </div>

            <div class="relative lg:col-span-7">
                @foreach ($scanners as $i => $scanner)
                    <article id="scanner-panel-{{ $scanner->slug }}" role="tabpanel" aria-labelledby="scanner-tab-{{ $scanner->slug }}"
                             x-show="active === {{ $i }}" @if ($i > 0) x-cloak @endif
                             x-transition:enter="transition duration-500 ease-[var(--ease-out-soft)]" x-transition:enter-start="opacity-0 translate-y-2"
                             style="--accent: {{ $scanner->accent }}" class="lg:sticky lg:top-28">
                        <a href="{{ route('scanners.show', $scanner) }}" class="group block overflow-hidden rounded-[1.5rem] border border-line bg-ink-900 p-2.5">
                            <div class="relative aspect-[16/10] overflow-hidden rounded-[1.1rem] bg-white">
                                @if ($scanner->coverImage())
                                    <img src="{{ media($scanner->coverImage()) }}" alt="{{ __(':name scanner signal on a chart', ['name' => $scanner->name]) }}"
                                         loading="lazy" decoding="async" width="1600" height="1000" class="h-full w-full object-cover object-top transition-transform duration-700 ease-[var(--ease-out-soft)] group-hover:scale-[1.02]">
                                @endif
                                <span class="absolute start-4 top-4 flex items-center gap-2 rounded-full bg-ink-950/85 px-3 py-1.5 text-xs font-semibold text-white backdrop-blur">
                                    <span class="size-2 rounded-full" style="background: var(--accent)"></span>{{ $scanner->tr('name') }}
                                </span>
                            </div>
                        </a>
                        <div class="mt-6 grid gap-6 sm:grid-cols-5">
                            <p class="leading-relaxed text-fg-muted sm:col-span-3">{{ $scanner->tr('summary') }}</p>
                            <dl class="grid grid-cols-3 gap-3 text-sm sm:col-span-2 sm:grid-cols-1 sm:gap-2">
                                @if ($scanner->timeframe)<div class="flex flex-col sm:flex-row sm:justify-between"><dt class="text-fg-subtle">{{ __('Timeframe') }}</dt><dd class="font-semibold" dir="ltr">{{ $scanner->timeframe }}</dd></div>@endif
                                @if ($scanner->targets)<div class="flex flex-col sm:flex-row sm:justify-between"><dt class="text-fg-subtle">{{ __('Targets') }}</dt><dd class="num font-semibold">{{ $scanner->targets }} TP</dd></div>@endif
                                @if ($scanner->methodology)<div class="flex flex-col sm:flex-row sm:justify-between sm:gap-4"><dt class="shrink-0 text-fg-subtle">{{ __('Method') }}</dt><dd class="font-semibold sm:text-end">{{ $scanner->methodology }}</dd></div>@endif
                            </dl>
                        </div>
                        <a href="{{ route('scanners.show', $scanner) }}" class="btn btn-light mt-6">{{ __('Explore :name', ['name' => $scanner->tr('name')]) }} <x-icon name="arrow-right" class="size-4" /></a>
                    </article>
                @endforeach
            </div>
        </div>

        {{-- Mobile / tablet: swipeable cards with scroll-snap --}}
        <div class="lg:hidden">
            <ul class="-mx-4 mt-10 flex snap-x snap-mandatory gap-3 overflow-x-auto px-4 pb-4 [scrollbar-width:none] sm:-mx-6 sm:px-6 [&::-webkit-scrollbar]:hidden" role="list"
                x-ref="track" @scroll.debounce.60ms="active = Math.round(Math.abs($refs.track.scrollLeft) / ($refs.track.firstElementChild.offsetWidth + 12))">
                @foreach ($scanners as $i => $scanner)
                    <li class="w-[82%] shrink-0 snap-start sm:w-[46%]" style="--accent: {{ $scanner->accent }}">
                        <a href="{{ route('scanners.show', $scanner) }}" class="card flex h-full flex-col overflow-hidden">
                            <div class="relative aspect-[16/10] overflow-hidden bg-white">
                                @if ($scanner->coverImage())
                                    <img src="{{ $scanner->thumb($scanner->coverImage()) }}" alt="{{ __(':name scanner signal on a chart', ['name' => $scanner->name]) }}" loading="lazy" decoding="async" width="640" height="400" class="h-full w-full object-cover object-top">
                                @endif
                                <span class="absolute inset-x-0 top-0 h-1" style="background: var(--accent)"></span>
                            </div>
                            <div class="flex flex-1 flex-col p-5">
                                <div class="flex items-baseline justify-between gap-3">
                                    <h3 class="text-xl font-bold">{{ $scanner->tr('name') }}</h3>
                                    <span class="num text-xs text-fg-subtle">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}/{{ str_pad($scanners->count(), 2, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <p class="mt-1 text-sm font-medium" style="color: var(--accent)"><bdi>{{ $scanner->tr('tagline') }}</bdi></p>
                                <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-fg-muted">{{ $scanner->tr('summary') }}</p>
                                <span class="mt-auto flex items-center gap-2 pt-5 text-sm font-semibold">{{ __('Explore scanner') }} <x-icon name="arrow-right" class="size-4" /></span>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-2 flex justify-center gap-1.5" aria-hidden="true">
                @foreach ($scanners as $i => $scanner)
                    <span class="h-1.5 rounded-full transition-all duration-300" :class="active === {{ $i }} ? 'w-6 bg-fg' : 'w-1.5 bg-fg-subtle/50'"></span>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif
