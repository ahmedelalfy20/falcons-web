{{-- Signed-in leader: points chip in the header with a quick account panel. --}}
@php $next = $account['next']; @endphp
<div class="relative" x-data="{ acct: false }" @click.outside="acct = false" @keydown.escape="acct = false">
    <button type="button" @click="acct = !acct" :aria-expanded="acct" aria-haspopup="true" aria-label="{{ __('Your account') }}"
            class="flex min-h-10 items-center gap-2 rounded-full border border-brand-500/30 bg-brand-500/[0.08] py-1 ps-1 pe-3 text-sm transition-colors hover:border-brand-500/60">
        <x-leader-avatar :leader="$leader" class="size-8 text-xs" />
        <span class="num font-extrabold text-brand-300">{{ number_format($account['total']) }}</span>
        <span class="hidden text-xs text-fg-muted sm:inline">{{ __('points') }}</span>
    </button>
    <div x-show="acct" x-cloak x-transition.origin.top
         class="fixed inset-x-4 top-[4.25rem] z-50 overflow-hidden sm:absolute sm:inset-x-auto sm:end-0 sm:top-full sm:mt-2 sm:w-80 rounded-2xl border border-line bg-ink-850 shadow-2xl">
        <div class="border-b border-line p-4">
            <p class="truncate font-bold">{{ $leader->name }}</p>
            <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-xl bg-ink-900 py-2"><p class="num text-lg font-extrabold text-brand-300">{{ number_format($account['total']) }}</p><p class="text-[0.7rem] text-fg-subtle">{{ __('Total points') }}</p></div>
                <div class="rounded-xl bg-ink-900 py-2"><p class="num text-lg font-extrabold">#{{ $account['rank'] ?? '—' }}</p><p class="text-[0.7rem] text-fg-subtle">{{ __('Your rank') }}</p></div>
                <div class="rounded-xl bg-ink-900 py-2"><p class="num text-lg font-extrabold">{{ $account['unlocked'] }}/{{ $account['gifts'] }}</p><p class="text-[0.7rem] text-fg-subtle">{{ __('Gifts') }}</p></div>
            </div>
            @if ($next)
                <div class="mt-3 flex items-center gap-3 rounded-xl bg-ink-900 p-3">
                    @if ($next['image'])<img src="{{ $next['image'] }}" alt="" @class(['size-11 shrink-0', 'rounded-lg object-cover' => $next['photo'] ?? false, 'object-contain' => ! ($next['photo'] ?? false)])>@endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold">{{ $next['name'] }}</p>
                        <p class="text-xs text-fg-muted">{{ __(':n points left', ['n' => number_format($account['remaining'])]) }}</p>
                        <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-ink-700"><div class="h-full rounded-full bg-brand-500" style="width: {{ $account['percent'] }}%"></div></div>
                    </div>
                </div>
            @endif
        </div>
        <div class="grid p-2 text-sm">
            <a href="{{ route('leader.dashboard') }}" class="flex min-h-11 items-center gap-3 rounded-xl px-3 hover:bg-ink-800"><x-icon name="dashboard" class="size-4 text-fg-subtle" />{{ __('My dashboard') }}</a>
            <a href="{{ route('leader.dashboard') }}#gifts" class="flex min-h-11 items-center gap-3 rounded-xl px-3 hover:bg-ink-800"><x-icon name="gift" class="size-4 text-fg-subtle" />{{ __('My gifts') }}</a>
            <a href="{{ route('competition') }}#board-title" class="flex min-h-11 items-center gap-3 rounded-xl px-3 hover:bg-ink-800"><x-icon name="trophy" class="size-4 text-fg-subtle" />{{ __('Leaderboard') }}</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex min-h-11 w-full items-center gap-3 rounded-xl px-3 text-start text-fg-muted hover:bg-ink-800"><x-icon name="logout" class="size-4" />{{ __('Log out') }}</button></form>
        </div>
    </div>
</div>
