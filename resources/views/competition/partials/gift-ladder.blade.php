{{--
    Gift ladder cards.
    $gifts  — Collection<Gift> (options loaded). In leader mode each gift also has ->unlocked and ->claim.
    $leader — true on the leader dashboard (shows lock state, progress and the claim form).
    $total  — leader's lifetime points (leader mode only).
--}}
@php
    $leader = $leader ?? false;
    $total = $total ?? 0;
    $prev = 0;
@endphp
<ol class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3" role="list">
    @foreach ($gifts as $gift)
        @php
            $opts = $gift->options;
            $choice = $opts->count() > 1;
            $unlocked = $leader && $gift->unlocked;
            $claim = $leader ? $gift->claim : null;
            $locked = $leader && ! $unlocked;
            $need = max(0, $gift->points_required - $total);
            $pct = $locked ? (int) floor(min(100, max(0, ($total - $prev) / max(1, $gift->points_required - $prev) * 100))) : 100;
            $prev = $gift->points_required;
        @endphp
        <li @class([
                'gift-card group relative flex flex-col overflow-hidden rounded-[1.75rem] border bg-ink-850 transition-[border-color,box-shadow,transform] duration-300',
                'border-brand-500/45 shadow-[0_0_0_1px_rgb(34_197_132/0.15),0_24px_60px_-30px_rgb(34_197_132/0.55)]' => $unlocked,
                'border-line hover:-translate-y-0.5 hover:border-line-strong' => ! $unlocked,
            ])
            x-data="{ pick: @js($claim?->gift_option_id ?? ($choice ? null : $opts->first()?->id)) }">

            {{-- Header: level + target --}}
            <div class="flex items-center justify-between gap-3 px-5 pt-5">
                <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.14em] text-fg-subtle rtl:tracking-normal">
                    <span @class(['num flex size-7 items-center justify-center rounded-full text-[0.8rem] tracking-normal',
                        'bg-brand-500 text-ink-950' => $unlocked, 'bg-ink-800 text-fg-muted ring-1 ring-line' => ! $unlocked])>
                        @if ($unlocked)<x-icon name="check" class="size-4" />@else{{ $loop->iteration }}@endif
                    </span>
                    {{ __('Level :n', ['n' => $loop->iteration]) }}
                </span>
                <span @class(['num inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-bold',
                    'bg-brand-500/15 text-brand-300' => ! $locked, 'bg-ink-800 text-fg-muted ring-1 ring-line' => $locked])>
                    <x-icon name="sparkle" class="size-3.5" />{{ number_format($gift->points_required) }} <span class="text-xs font-medium">{{ __('points') }}</span>
                </span>
            </div>

            {{-- Photos --}}
            <div class="relative mx-5 mt-4">
                <div @class(['grid gap-2', 'grid-cols-2' => $choice])>
                    @foreach ($opts->take(4) as $o)
                        @php $selectable = $unlocked && $choice && ! $claim?->isDelivered(); @endphp
                        <{{ $selectable ? 'label' : 'div' }} @class(['gift-photo relative block overflow-hidden rounded-2xl', 'cursor-pointer' => $selectable, 'aspect-[4/3]' => ! $choice, 'aspect-square' => $choice])
                            @if ($choice && $leader) :class="pick === {{ $o->id }} ? 'ring-[3px] ring-brand-500 ring-offset-2 ring-offset-ink-850' : ''" @endif>
                            @if ($selectable)<input type="radio" name="gift-{{ $gift->id }}" value="{{ $o->id }}" x-model.number="pick" class="sr-only">@endif
                            <img src="{{ $o->imageUrl() }}" alt="{{ $o->tr('label') ?: $gift->tr('name') }}" loading="lazy" decoding="async"
                                 @class(['h-full w-full object-contain p-2 transition-transform duration-500 group-hover:scale-[1.03]', 'grayscale-[0.35] opacity-80' => $locked])>
                            @if ($choice && $o->tr('label'))
                                <span class="theme-dark absolute inset-x-2 bottom-2 truncate rounded-full bg-ink-950/75 px-2.5 py-1 text-center text-xs font-medium text-fg backdrop-blur">{{ $o->tr('label') }}</span>
                            @endif
                            @if ($choice && $leader)
                                <span x-show="pick === {{ $o->id }}" x-cloak class="absolute end-2 top-2 flex size-7 items-center justify-center rounded-full bg-brand-500 text-ink-950 shadow"><x-icon name="check" class="size-4" /></span>
                            @endif
                        </{{ $selectable ? 'label' : 'div' }}>
                    @endforeach
                </div>
                @if ($opts->count() === 2)
                    <span class="num pointer-events-none absolute start-1/2 top-1/2 flex size-9 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-ink-800 text-xs font-bold text-fg ring-4 ring-ink-850 shadow-sm rtl:translate-x-1/2">{{ __('or') }}</span>
                @endif
                @if ($locked)
                    <span class="theme-dark absolute start-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-ink-950/80 px-3 py-1.5 text-xs font-semibold text-fg backdrop-blur"><x-icon name="lock" class="size-3.5" />{{ __('Locked') }}</span>
                @endif
            </div>

            {{-- Text --}}
            <div class="flex flex-1 flex-col px-5 pb-5 pt-4">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-lg font-bold leading-snug">{{ $gift->tr('name') }}</h3>
                    @if ($choice)<span class="chip px-2 py-0.5 text-xs">{{ __('Choose 1 of :n', ['n' => $opts->count()]) }}</span>@endif
                </div>
                @if ($gift->tr('description'))<p class="mt-1.5 text-sm leading-relaxed text-fg-muted">{{ $gift->tr('description') }}</p>@endif

                @if ($leader)
                    <div class="mt-auto pt-5">
                        @if ($locked)
                            <div class="flex items-center justify-between text-xs text-fg-subtle">
                                <span>{{ __(':n more points to unlock', ['n' => number_format($need)]) }}</span>
                                <span class="num" dir="ltr">{{ number_format(min($total, $gift->points_required)) }} / {{ number_format($gift->points_required) }}</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-ink-800" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}">
                                <div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-brand-400" style="width: {{ $pct }}%"></div>
                            </div>
                        @elseif ($claim?->isDelivered())
                            <p class="flex items-center gap-2 rounded-xl bg-brand-500/10 px-3 py-2.5 text-sm font-medium text-brand-300"><x-icon name="check" class="size-4" />{{ __('Delivered — enjoy it!') }}@if ($choice && $claim->option?->tr('label')) <span class="text-fg-muted">· {{ $claim->option->tr('label') }}</span>@endif</p>
                        @else
                            <form method="POST" action="{{ route('leader.gifts.claim', $gift) }}" class="space-y-2">
                                @csrf
                                <input type="hidden" name="option" :value="pick">
                                @if ($claim)
                                    <p class="flex items-center gap-2 text-sm text-fg-muted"><x-icon name="clock" class="size-4 text-warning" />{{ __('Requested — our team will contact you.') }}</p>
                                @endif
                                @if (! $claim || $choice)
                                    <button class="btn btn-primary w-full" :disabled="!pick" @if ($claim) x-show="pick !== {{ (int) $claim->gift_option_id }}" x-cloak @endif>
                                        <x-icon name="gift" class="size-4" />
                                        @if ($claim){{ __('Change my choice') }}@elseif ($choice)<span x-text="pick ? @js(__('Get this gift')) : @js(__('Pick one above'))">{{ __('Pick one above') }}</span>@else{{ __('Claim my gift') }}@endif
                                    </button>
                                @endif
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </li>
    @endforeach
</ol>
