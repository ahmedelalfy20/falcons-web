{{-- Leader gift journey: lifetime points, track to every gift, and the gift cards. --}}
@php
    $list = $g['gifts'];
    $max = max(1, (int) $list->max('points_required'));
    // Levels are evenly spaced; the fill moves through each segment by points earned.
    $n = max(1, $list->count());
    $done = $list->where('unlocked', true)->count();
    $from = $done ? $list[$done - 1]->points_required : 0;
    $to = $g['next']?->points_required;
    $frac = $to ? min(1, max(0, ($g['total'] - $from) / max(1, $to - $from))) : 0;
    $fill = min(100, ($done + $frac) / $n * 100);
@endphp
<section id="gifts" class="mt-5 scroll-mt-24 overflow-hidden rounded-[var(--radius-card)] border border-line bg-ink-850" aria-labelledby="gifts-title">
    <div class="relative px-5 pt-6 pb-7 sm:px-8 sm:pt-8">
        <div class="bg-glow pointer-events-none absolute inset-0 opacity-70" aria-hidden="true"></div>
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="eyebrow">{{ __('Your rewards') }}</p>
                <h2 id="gifts-title" class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">{{ __('Collect points, unlock gifts') }}</h2>
                <p class="mt-2 max-w-xl text-sm text-fg-muted">{{ __('Your points never reset — every gift you unlock keeps them, and they carry you to the next one.') }}</p>
            </div>
            <div class="flex items-end gap-6">
                <div>
                    <p class="text-xs text-fg-subtle">{{ __('Total points') }}</p>
                    <p class="num text-5xl font-extrabold tracking-tight text-brand-300">{{ number_format($g['total']) }}</p>
                </div>
                <div class="border-s border-line ps-6">
                    <p class="text-xs text-fg-subtle">{{ __('Gifts unlocked') }}</p>
                    <p class="num text-3xl font-extrabold">{{ $g['unlockedCount'] }}<span class="text-lg text-fg-subtle">/{{ $list->count() }}</span></p>
                </div>
            </div>
        </div>

        {{-- Track --}}
        <div class="relative mt-8 px-3.5">
            <div class="h-2.5 rounded-full bg-ink-800 ring-1 ring-line" role="progressbar" aria-label="{{ __('Progress to the last gift') }}" aria-valuemin="0" aria-valuemax="{{ $max }}" aria-valuenow="{{ min($g['total'], $max) }}">
                <div class="h-full rounded-full bg-gradient-to-r from-brand-600 via-brand-500 to-brand-400 transition-[width] duration-700 rtl:bg-gradient-to-l" style="width: {{ $fill }}%"></div>
            </div>
            @foreach ($list as $gift)
                @php $f = round($loop->iteration / $n, 4); @endphp
                <div class="absolute top-[5px] -translate-x-1/2 -translate-y-1/2 rtl:translate-x-1/2" style="inset-inline-start: calc((100% - 1.75rem) * {{ $f }} + 0.875rem)">
                    <span @class(['flex size-7 items-center justify-center rounded-full ring-4 ring-ink-850',
                        'bg-brand-500 text-ink-950' => $gift->unlocked, 'bg-ink-700 text-fg-muted' => ! $gift->unlocked])>
                        <x-icon :name="$gift->unlocked ? 'check' : 'gift'" class="size-3.5" />
                    </span>
                    <span class="num absolute start-1/2 top-9 -translate-x-1/2 whitespace-nowrap text-xs font-semibold rtl:translate-x-1/2 {{ $gift->unlocked ? 'text-brand-300' : 'text-fg-subtle' }}">{{ number_format($gift->points_required) }}</span>
                </div>
            @endforeach
        </div>

        <div class="relative mt-12">
            @if ($g['next'])
                <p class="text-sm text-fg-muted">
                    {!! __(':n more points to unlock :gift', [
                        'n' => '<span class="num font-bold text-fg">'.number_format($g['remaining']).'</span>',
                        'gift' => '<span class="font-semibold text-fg">'.e($g['next']->tr('name')).'</span>',
                    ]) !!}
                </p>
            @else
                <p class="flex items-center gap-2 text-sm font-medium text-brand-300"><x-icon name="trophy" class="size-4" />{{ __('You unlocked every gift. Legend!') }}</p>
            @endif
        </div>
    </div>

    <div class="border-t border-line bg-ink-900/40 px-5 py-6 sm:px-8 sm:py-8">
        @error('gift')<x-alert level="danger" class="mb-5">{{ $message }}</x-alert>@enderror
        @include('competition.partials.gift-ladder', ['gifts' => $list, 'leader' => true, 'total' => $g['total']])
    </div>
</section>
