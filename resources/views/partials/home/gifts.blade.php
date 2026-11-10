{{-- Homepage gift showcase: the reward ladder as a row of product cards. --}}
@php
    $list = $giftProgress['gifts'] ?? $gifts ?? collect();
    $total = $giftProgress['total'] ?? null;
    $per = $competition?->pointsPerRegistration() ?? 10;
@endphp
@if ($list->isNotEmpty())
    <section id="gifts" class="relative overflow-hidden pt-8 pb-16 sm:pt-10 sm:pb-20" aria-labelledby="home-gifts-title">
        <div class="pointer-events-none absolute inset-x-0 top-1/3 h-2/3 bg-[radial-gradient(50%_60%_at_50%_40%,rgb(34_197_132/0.10),transparent_70%)]" aria-hidden="true"></div>
        <div class="container-x relative">
            <div class="flex flex-wrap items-end justify-between gap-6" data-reveal>
                <div class="max-w-2xl">
                    <p class="eyebrow">{{ __('Leader rewards') }}</p>
                    <h2 id="home-gifts-title" class="h-section mt-3">{{ __('Collect points, unlock gifts') }}</h2>
                    <p class="lead mt-4">{{ __('Every approved registration earns :n points. Points never reset — each gift you unlock keeps them, and they carry you to the next one.', ['n' => $per]) }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    @if ($total !== null)
                        <a href="{{ route('leader.dashboard') }}#gifts" class="btn btn-primary"><x-icon name="gift" class="size-4" />{{ __('My gifts') }}</a>
                    @else
                        <a href="{{ route('leader.signup') }}" class="btn btn-primary">{{ __('Become a leader') }} <x-icon name="arrow-right" class="size-4" /></a>
                    @endif
                    <a href="{{ route('competition') }}#gifts" class="btn btn-outline">{{ __('All gift details') }}</a>
                </div>
            </div>

            {{-- Cards: grid on desktop, swipeable row on phones --}}
            <ol class="-mx-4 mt-8 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-4 [scrollbar-width:none] sm:mx-0 sm:grid sm:grid-cols-2 sm:overflow-visible sm:px-0 sm:pb-0 lg:mt-10 lg:grid-cols-4" role="list">
                @foreach ($list as $gift)
                    @php
                        $opts = $gift->options->take(4)->values();
                        $open = $total !== null && $total >= $gift->points_required;
                        $need = $total !== null ? max(0, $gift->points_required - $total) : null;
                        // The last gift (the grand prize) widens to fill an incomplete final row.
                        $n = $list->count();
                        $wide = $loop->last && ($n % 2 === 1 || $n % 4 === 3);
                    @endphp
                    <li class="gift-card group relative flex w-[78%] shrink-0 snap-center flex-col overflow-hidden rounded-[1.6rem] border border-line bg-ink-850 transition-[transform,border-color,box-shadow] duration-500 hover:-translate-y-1.5 hover:border-brand-500/40 hover:shadow-[0_30px_70px_-40px_rgb(34_197_132/0.6)] sm:w-auto {{ $wide ? ($n % 2 === 1 ? 'sm:col-span-2' : '').($n % 4 === 3 ? ' lg:col-span-2' : '') : '' }}" data-reveal
                        x-data="{ view: 0 }" @if ($opts->count() > 1) x-init="setInterval(() => view = (view + 1) % {{ $opts->count() }}, 2600)" @endif>
                        <div @class(['gift-hero theme-dark relative overflow-hidden', 'aspect-[4/3.4]' => ! $wide, 'aspect-[4/3.4] sm:aspect-[16/7.4]' => $wide])>
                            <span class="gift-hero-num num pointer-events-none absolute -bottom-6 start-3 select-none text-[7.5rem] font-extrabold leading-none" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <span class="gift-spot absolute inset-0" aria-hidden="true"></span>
                            @foreach ($opts as $i => $o)
                                @if ($o->isPhoto())
                                    <div class="absolute inset-0" x-show="view === {{ $i }}" @if ($i) x-cloak @endif x-transition:enter="transition duration-700 ease-out" x-transition:enter-start="opacity-0">
                                        <img src="{{ $o->imageUrl() }}" alt="{{ $o->tr('label') ?: $gift->tr('name') }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-[1.2s] ease-[var(--ease-out-soft)] group-hover:scale-105">
                                        <span class="gift-photo-shade-bottom absolute inset-0" aria-hidden="true"></span>
                                    </div>
                                    @continue
                                @endif
                                <img src="{{ $o->imageUrl() }}" alt="{{ $o->tr('label') ?: $gift->tr('name') }}" loading="lazy" decoding="async"
                                     x-show="view === {{ $i }}" @if ($i) x-cloak @endif
                                     x-transition:enter="transition duration-700 ease-out" x-transition:enter-start="opacity-0 scale-95"
                                     @class(['gift-product absolute object-contain object-bottom transition-transform duration-700 ease-[var(--ease-out-soft)] group-hover:-translate-y-2 group-hover:scale-105', 'inset-x-[12%] bottom-[10%] top-[16%] h-[74%] w-[76%]' => ! $wide, 'inset-x-[8%] bottom-[9%] top-[20%] h-[71%] w-[84%]' => $wide])>
                            @endforeach
                            <span class="absolute start-3 top-3 inline-flex items-center gap-1.5 rounded-full border border-white/10 bg-white/[0.07] px-2.5 py-1 text-[0.7rem] font-medium text-fg-muted backdrop-blur">
                                <x-icon :name="$open ? 'check' : ($total !== null ? 'lock' : 'gift')" class="size-3" />{{ __('Level :n', ['n' => $loop->iteration]) }}
                            </span>
                            @if ($opts->count() > 1)
                                <span class="absolute end-3 top-3 rounded-full bg-black/40 px-2.5 py-1 text-[0.7rem] font-medium text-fg backdrop-blur">{{ __('Choose 1 of :n', ['n' => $opts->count()]) }}</span>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                            <p class="num text-[0.7rem] font-bold uppercase tracking-[0.18em] text-brand-400 rtl:tracking-normal">{{ number_format($gift->points_required) }} {{ __('points') }}</p>
                            <h3 class="mt-1 text-lg font-bold leading-snug">{{ $gift->tr('name') }}</h3>
                            @if ($gift->tr('description'))<p class="mt-1 line-clamp-2 text-sm text-fg-muted">{{ $gift->tr('description') }}</p>@endif
                            <p class="mt-auto flex items-center gap-1.5 pt-4 text-xs font-medium">
                                @if ($open)
                                    <span class="flex items-center gap-1.5 text-brand-300"><x-icon name="check" class="size-3.5" />{{ __('Unlocked') }}</span>
                                @elseif ($need !== null)
                                    <span class="flex items-center gap-1.5 text-fg-subtle"><x-icon name="lock" class="size-3.5" />{{ __(':n points left', ['n' => number_format($need)]) }}</span>
                                @else
                                    <span class="flex items-center gap-1.5 text-fg-subtle"><x-icon name="lock" class="size-3.5" />{{ __('Unlocks at :n points', ['n' => number_format($gift->points_required)]) }}</span>
                                @endif
                            </p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
@endif
