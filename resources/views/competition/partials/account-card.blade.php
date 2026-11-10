{{--
    Leader account snapshot: lifetime points, rank, gifts unlocked and the
    nearest gift. $account from GiftService::summary(); $cta = show the dashboard link.
--}}
@php $cta = $cta ?? true; $next = $account['next']; @endphp
<div class="theme-dark relative overflow-hidden rounded-[var(--radius-card)] border border-brand-500/25 bg-[radial-gradient(120%_140%_at_100%_0%,rgb(34_197_132/0.22),transparent_55%),linear-gradient(135deg,#0c1512,#08100d)] text-fg">
    <div class="pointer-events-none absolute inset-0 opacity-[0.07] [background-image:linear-gradient(rgb(255_255_255/0.6)_1px,transparent_1px),linear-gradient(90deg,rgb(255_255_255/0.6)_1px,transparent_1px)] [background-size:28px_28px] [mask-image:linear-gradient(to_bottom,black,transparent)]" aria-hidden="true"></div>
    <div class="relative grid gap-6 p-5 sm:p-7 lg:grid-cols-12 lg:items-center">
        {{-- Who + key numbers --}}
        <div class="lg:col-span-7">
            <div class="flex items-center gap-3">
                <x-leader-avatar :leader="$leader" class="size-11 text-sm ring-2 ring-brand-500/50" />
                <div class="min-w-0">
                    <p class="text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-brand-300 rtl:tracking-normal">{{ __('Your account') }}</p>
                    <p class="truncate font-bold">{{ $leader->name }}</p>
                </div>
            </div>
            <dl class="mt-5 grid grid-cols-3 gap-3">
                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                    <dt class="text-xs text-fg-subtle">{{ __('Total points') }}</dt>
                    <dd class="num mt-1 text-2xl font-extrabold text-brand-300 sm:text-3xl">{{ number_format($account['total']) }}</dd>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                    <dt class="text-xs text-fg-subtle">{{ __('Your rank') }}</dt>
                    <dd class="num mt-1 text-2xl font-extrabold sm:text-3xl"><span class="text-brand-400">#</span>{{ $account['rank'] ?? '—' }}</dd>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/[0.04] px-4 py-3">
                    <dt class="text-xs text-fg-subtle">{{ __('Gifts unlocked') }}</dt>
                    <dd class="num mt-1 text-2xl font-extrabold sm:text-3xl">{{ $account['unlocked'] }}<span class="text-base text-fg-subtle">/{{ $account['gifts'] }}</span></dd>
                </div>
            </dl>
        </div>

        {{-- Nearest gift --}}
        <div class="lg:col-span-5">
            @if ($next)
                <div class="flex items-center gap-4 rounded-2xl border border-white/10 bg-black/25 p-4">
                    @if ($next['image'])
                        <span class="relative flex size-20 shrink-0 items-center justify-center rounded-xl bg-[radial-gradient(circle_at_50%_40%,rgb(34_197_132/0.25),transparent_70%)]">
                            <img src="{{ $next['image'] }}" alt="" class="max-h-full max-w-full object-contain drop-shadow-[0_10px_18px_rgb(0_0_0/0.6)]">
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-fg-subtle">{{ __('Next gift') }}</p>
                        <p class="truncate font-bold">{{ $next['name'] }}</p>
                        <p class="mt-1 text-sm text-fg-muted">{!! __(':n points left', ['n' => '<span class="num font-bold text-brand-300">'.number_format($account['remaining']).'</span>']) !!}</p>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-gradient-to-r from-brand-600 to-brand-400" style="width: {{ $account['percent'] }}%"></div></div>
                    </div>
                </div>
            @elseif ($account['gifts'])
                <p class="flex items-center gap-2 rounded-2xl border border-white/10 bg-black/25 p-4 font-medium text-brand-300"><x-icon name="trophy" class="size-5" />{{ __('You unlocked every gift. Legend!') }}</p>
            @endif
            @if ($cta)
                <a href="{{ route('leader.dashboard') }}" class="btn btn-primary mt-3 w-full">{{ __('Open my dashboard') }} <x-icon name="arrow-right" class="size-4" /></a>
            @endif
        </div>
    </div>
</div>
