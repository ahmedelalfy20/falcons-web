{{--
    Gift ladder — showcase cards: a dark product banner (big cut-out photo,
    title, points pill) over a details footer.
    $gifts  — Collection<Gift> (options loaded). In leader mode each gift also has ->unlocked and ->claim.
    $leader — true on the leader dashboard (lock state, progress and the claim form).
    $total  — leader's lifetime points (leader mode only).
--}}
@php
    $leader = $leader ?? false;
    $total = $total ?? 0;
    $prev = 0;
@endphp
<ol class="grid gap-6 lg:grid-cols-2" role="list">
    @foreach ($gifts as $gift)
        @php
            $opts = $gift->options->take(4)->values();
            $choice = $opts->count() > 1;
            $unlocked = $leader && $gift->unlocked;
            $claim = $leader ? $gift->claim : null;
            $delivered = (bool) $claim?->isDelivered();
            $locked = $leader && ! $unlocked;
            $need = max(0, $gift->points_required - $total);
            $pct = $locked ? (int) floor(min(100, max(0, ($total - $prev) / max(1, $gift->points_required - $prev) * 100))) : 100;
            $prev = $gift->points_required;
            $chosen = $claim?->gift_option_id;
            $startIndex = max(0, $chosen ? $opts->search(fn ($o) => $o->id === $chosen) : 0);
            $canPick = $unlocked && ! $delivered;
        @endphp
        <li @class([
                'gift-card group relative flex flex-col overflow-hidden rounded-[1.75rem] border bg-ink-850 transition-[transform,box-shadow,border-color] duration-500',
                'border-brand-500/50 shadow-[0_30px_80px_-40px_rgb(34_197_132/0.7)]' => $unlocked,
                'border-line hover:-translate-y-1 hover:border-brand-500/30 hover:shadow-[0_30px_70px_-45px_rgb(0_0_0/0.8)]' => ! $unlocked,
            ])
            x-data="{ view: {{ $startIndex }}, pick: @js($chosen ?? ($choice ? null : $opts->first()?->id)), ids: @js($opts->pluck('id')) }">

            {{-- ── Banner ── --}}
            <div class="gift-hero theme-dark relative h-60 overflow-hidden sm:h-64">
                <span class="gift-hero-num num pointer-events-none absolute -bottom-10 end-[18%] select-none text-[11rem] font-extrabold leading-none" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>

                {{-- Full-bleed photos (e.g. a trip) --}}
                @foreach ($opts as $i => $o)
                    @if ($o->isPhoto())
                        <div class="absolute inset-0" x-show="view === {{ $i }}" @if ($i !== $startIndex) x-cloak @endif x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="opacity-0">
                            <img src="{{ $o->imageUrl() }}" alt="{{ $o->tr('label') ?: $gift->tr('name') }}" loading="lazy" decoding="async"
                                 class="h-full w-full object-cover object-left transition-transform duration-[1.2s] rtl:object-[78%_50%] ease-[var(--ease-out-soft)] group-hover:scale-[1.05]">
                            <span class="gift-photo-shade absolute inset-0" aria-hidden="true"></span>
                        </div>
                    @endif
                @endforeach

                {{-- Product photo(s), cross-fading when the leader switches options --}}
                <div class="absolute inset-y-0 end-0 w-[50%]">
                    <span class="gift-spot absolute inset-0" aria-hidden="true" @if ($opts->every(fn ($o) => $o->isPhoto())) hidden @endif></span>
                    @foreach ($opts as $i => $o)
                        @continue($o->isPhoto())
                        <img src="{{ $o->imageUrl() }}" alt="{{ $o->tr('label') ?: $gift->tr('name') }}" loading="lazy" decoding="async"
                             x-show="view === {{ $i }}" @if ($i !== $startIndex) x-cloak @endif
                             x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                             class="gift-product absolute bottom-[8%] end-[-2%] h-[78%] w-[100%] object-contain object-bottom transition-transform duration-700 ease-[var(--ease-out-soft)] group-hover:-translate-y-2 group-hover:scale-[1.04]">
                    @endforeach
                </div>

                {{-- Copy --}}
                <div class="relative flex h-full max-w-[56%] flex-col justify-between p-5 sm:p-6">
                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full border border-white/10 bg-black/40 px-3 py-1 text-xs font-medium text-fg backdrop-blur">
                        @if ($unlocked)<x-icon name="check" class="size-3.5 text-brand-300" />@elseif ($locked)<x-icon name="lock" class="size-3.5" />@else<x-icon name="gift" class="size-3.5" />@endif
                        {{ __('Level :n', ['n' => $loop->iteration]) }}
                    </span>
                    <div>
                        <h3 class="text-balance text-[1.7rem] font-extrabold leading-[1.05] tracking-tight text-white sm:text-[2.1rem]">{{ $gift->tr('name') }}</h3>
                        <span class="gift-pill num mt-4 inline-flex items-center gap-2 rounded-full px-4 py-2 text-base font-bold text-white">
                            <x-icon name="sparkle" class="size-4" />{{ number_format($gift->points_required) }} <span class="text-sm font-medium opacity-80">{{ __('points') }}</span>
                        </span>
                    </div>
                </div>

                @if ($unlocked)
                    <span class="absolute end-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-brand-500 px-3 py-1 text-xs font-bold text-ink-950 shadow-lg"><x-icon name="check" class="size-3.5" />{{ $delivered ? __('Delivered') : __('Unlocked') }}</span>
                @endif
            </div>

            {{-- ── Details ── --}}
            <div class="flex flex-1 flex-col p-5 sm:p-6">
                <p class="num text-xs font-bold uppercase tracking-[0.18em] text-brand-400 rtl:tracking-normal">{{ number_format($gift->points_required) }} {{ __('points') }}</p>
                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                    <p class="text-xl font-bold">{{ $gift->tr('name') }}</p>
                    @if ($choice)<span class="chip px-2.5 py-0.5 text-xs">{{ __('Choose 1 of :n', ['n' => $opts->count()]) }}</span>@endif
                </div>
                @if ($gift->tr('description'))<p class="mt-1.5 text-sm leading-relaxed text-fg-muted">{{ $gift->tr('description') }}</p>@endif

                {{-- Options --}}
                @if ($choice)
                    <div class="mt-4 grid gap-2" style="grid-template-columns: repeat({{ $opts->count() }}, minmax(0, 1fr))" @if ($canPick) role="radiogroup" aria-label="{{ __('Choose your gift') }}" @endif>
                        @foreach ($opts as $i => $o)
                            <button type="button" @mouseenter="view = {{ $i }}" @focus="view = {{ $i }}"
                                    @click="view = {{ $i }}; @if ($canPick) pick = {{ $o->id }} @endif"
                                    @if ($canPick) role="radio" :aria-checked="pick === {{ $o->id }}" @endif
                                    class="relative flex items-center gap-2.5 rounded-xl border p-2 text-start transition-colors"
                                    :class="{{ $canPick ? "pick === {$o->id}" : "view === {$i}" }} ? 'border-brand-500 bg-brand-500/[0.08]' : 'border-line hover:border-line-strong'">
                                <span class="gift-thumb theme-dark flex size-11 shrink-0 items-center justify-center overflow-hidden rounded-lg"><img src="{{ $o->imageUrl(true) }}" alt="" @class(['h-full w-full object-cover' => $o->isPhoto(), 'max-h-[85%] max-w-[85%] object-contain' => ! $o->isPhoto()])></span>
                                <span class="min-w-0 truncate text-sm font-medium">{{ $o->tr('label') ?: __('Option :n', ['n' => $i + 1]) }}</span>
                                @if ($canPick)<span x-show="pick === {{ $o->id }}" x-cloak class="absolute -top-1.5 -end-1.5 flex size-5 items-center justify-center rounded-full bg-brand-500 text-ink-950"><x-icon name="check" class="size-3" /></span>@endif
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Status / actions --}}
                <div class="mt-auto pt-5">
                    @if (! $leader)
                        <p class="flex items-center gap-2 text-sm text-fg-subtle"><x-icon name="lock" class="size-4" />{{ __('Unlocks at :n points', ['n' => number_format($gift->points_required)]) }}</p>
                    @elseif ($locked)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="flex items-center gap-1.5 font-medium text-fg-muted"><x-icon name="lock" class="size-4" />{{ __('Locked') }}</span>
                            <span class="text-fg-subtle">{{ __(':n more points to unlock', ['n' => number_format($need)]) }}</span>
                        </div>
                        <div class="mt-2.5 h-2 overflow-hidden rounded-full bg-ink-800" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $pct }}">
                            <div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-brand-400" style="width: {{ $pct }}%"></div>
                        </div>
                        <p class="num mt-1.5 text-end text-xs text-fg-subtle" dir="ltr">{{ number_format(min($total, $gift->points_required)) }} / {{ number_format($gift->points_required) }}</p>
                    @elseif ($delivered)
                        <p class="flex items-center gap-2 rounded-xl bg-brand-500/10 px-3 py-2.5 text-sm font-medium text-brand-300"><x-icon name="check" class="size-4" />{{ __('Delivered — enjoy it!') }}@if ($choice && $claim->option?->tr('label')) <span class="text-fg-muted">· {{ $claim->option->tr('label') }}</span>@endif</p>
                    @else
                        <form method="POST" action="{{ route('leader.gifts.claim', $gift) }}" class="space-y-2.5">
                            @csrf
                            <input type="hidden" name="option" :value="pick">
                            @if ($claim)
                                <p class="flex items-center gap-2 text-sm text-fg-muted"><x-icon name="clock" class="size-4 text-warning" />{{ __('Requested — our team will contact you.') }}</p>
                            @endif
                            @if (! $claim || $choice)
                                <button class="btn btn-primary w-full" :disabled="!pick" @if ($claim) x-show="pick !== {{ (int) $chosen }}" x-cloak @endif>
                                    <x-icon name="gift" class="size-4" />
                                    @if ($claim){{ __('Change my choice') }}@elseif ($choice)<span x-text="pick ? @js(__('Get this gift')) : @js(__('Pick one above'))">{{ __('Pick one above') }}</span>@else{{ __('Claim my gift') }}@endif
                                </button>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </li>
    @endforeach
</ol>
