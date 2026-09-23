<x-layouts.site :title="__('Competition')" :description="__('Live leaderboard of the Falcons Leader Referral Competition.')">
    <section class="relative overflow-hidden pt-28 pb-14 sm:pt-32 lg:pb-20">
        <div class="bg-glow pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="container-x relative">
            @if (! $competition || ! $round)
                <div class="mx-auto max-w-xl text-center">
                    <p class="eyebrow justify-center">{{ __('Leader Referral Competition') }}</p>
                    <h1 class="h-section mt-4">{{ __('The next competition is coming soon') }}</h1>
                    <p class="lead mt-4">{{ __('There is no active competition right now. Follow Falcons on WhatsApp to hear when the next round opens.') }}</p>
                    <a href="{{ whatsapp_url() }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg mt-8">{{ __('Contact us on WhatsApp') }}</a>
                </div>
            @else
                <div class="grid items-end gap-10 lg:grid-cols-12">
                    <div class="lg:col-span-7">
                        <p class="eyebrow">{{ $competition->tr('name') ?? $competition->name }}</p>
                        <h1 class="h-display mt-5 text-balance">{{ $round->displayName() }}</h1>
                        <p class="lead mt-5 max-w-xl">{{ __('Leaders invite participants with their personal QR code. Every registration is reviewed by our team — only approved registrations count toward the leaderboard.') }}</p>
                        <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('register') }}" class="btn btn-primary btn-lg">{{ __('I have a referral code') }} <x-icon name="arrow-right" class="size-4" /></a>
                            @guest
                                <a href="{{ route('leader.signup') }}" class="btn btn-outline btn-lg">{{ __('Become a leader') }}</a>
                            @else
                                <a href="{{ auth()->user()->homeRoute() }}" class="btn btn-outline btn-lg">{{ __('Open my dashboard') }}</a>
                            @endguest
                        </div>
                    </div>
                    <div class="lg:col-span-5">
                        <div class="card card-pad">
                            @include('competition.partials.timer', ['round' => $round, 'big' => true])
                            <div class="mt-6 flex items-center gap-2 border-t border-line pt-5 text-sm" x-data="{ open: @js($round->acceptsRegistrations()) }" @round:update.window="open = $event.detail.registration_open">
                                <span class="size-2 rounded-full" :class="open ? 'bg-success' : 'bg-fg-subtle'"></span>
                                <span x-show="open" class="text-fg-muted">{{ __('Registration is open') }}</span>
                                <span x-show="!open" x-cloak class="text-fg-muted">{{ __('Registration is currently closed.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>

    @if ($competition && $round)
        {{-- How it works — four steps, one line each --}}
        <section class="border-y border-line bg-ink-900/50" aria-label="{{ __('How it works') }}">
            <ol class="container-x grid gap-px sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['icon' => 'qr', 'title' => __('Leader shares a QR'), 'text' => __('Every leader gets a unique code and QR.')],
                    ['icon' => 'user', 'title' => __('Participant registers'), 'text' => __('Scan, fill a short form, submit.')],
                    ['icon' => 'shield-check', 'title' => __('Team reviews'), 'text' => __('Each registration is checked by our team.')],
                    ['icon' => 'trophy', 'title' => __('Approved = 1 point'), 'text' => __('The leaderboard updates automatically.')],
                ] as $step)
                    <li class="flex gap-4 py-6 sm:px-4 lg:py-8">
                        <span class="num text-sm font-semibold text-fg-subtle">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <div>
                            <p class="flex items-center gap-2 font-semibold"><x-icon :name="$step['icon']" class="size-4 text-brand-400" />{{ $step['title'] }}</p>
                            <p class="mt-1 text-sm text-fg-muted">{{ $step['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>

        @if ($showBoard)
            @php
                $initial = $leaderboard->map(fn ($r) => ['rank' => $r->rank, 'name' => $r->name, 'score' => $r->score, 'photo' => $r->photoUrl(), 'initials' => $r->initials()])->values();
            @endphp
            <section class="py-16 sm:py-20" aria-labelledby="board-title"
                     x-data="{ rows: @js($initial), flash: {} }"
                     @live-data.window="
                        if ($event.detail.leaderboard) {
                            const prev = Object.fromEntries(rows.map(r => [r.name, r.score]));
                            rows = $event.detail.leaderboard;
                            rows.forEach(r => { if (prev[r.name] !== undefined && prev[r.name] !== r.score) { flash[r.name] = Date.now(); } });
                        }">
                <div x-data="poller(@js(route('live.competition')), 8000)"></div>
                <div class="container-x">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="eyebrow">{{ __('Live leaderboard') }}</p>
                            <h2 id="board-title" class="h-section mt-3">{{ __('Top leaders') }}</h2>
                        </div>
                        <p class="flex items-center gap-2 text-sm text-fg-subtle"><span class="relative flex size-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-400 opacity-60 motion-reduce:hidden"></span><span class="relative inline-flex size-2 rounded-full bg-brand-400"></span></span>{{ __('Updates automatically') }}</p>
                    </div>

                    <div class="mt-8">
                        <template x-if="rows.length === 0">
                            <x-empty icon="trophy" :title="__('No leaders yet')" :text="__('Be the first leader on the board.')" class="card" />
                        </template>
                        {{-- Podium: top three with their photos --}}
                        <ol class="grid gap-3 sm:grid-cols-3 sm:items-end" x-show="rows.length > 0" aria-label="{{ __('Top three') }}">
                            <template x-for="row in rows.slice(0, 3)" :key="row.name">
                                <li class="relative flex flex-col items-center overflow-hidden rounded-3xl border px-5 pb-6 text-center transition-colors"
                                    :class="[
                                        row.rank === 1 ? 'border-brand-500/40 bg-gradient-to-b from-brand-500/[0.14] to-ink-850 pt-8 sm:order-2 sm:pb-9' : 'border-line bg-ink-850 pt-6',
                                        row.rank === 2 ? 'sm:order-1' : '', row.rank === 3 ? 'sm:order-3' : '',
                                        flash[row.name] && Date.now() - flash[row.name] < 2000 ? 'row-flash' : ''
                                    ]">
                                    <span class="relative">
                                        <x-avatar-live ::class="row.rank === 1 ? 'size-28 text-3xl ring-4 ring-brand-500/60' : 'size-20 text-xl ring-2 ring-white/15'" />
                                        <span class="num absolute -bottom-1 start-1/2 flex size-8 -translate-x-1/2 items-center justify-center rounded-full text-sm font-extrabold ring-4 ring-ink-850 rtl:translate-x-1/2"
                                              :class="row.rank === 1 ? 'bg-brand-500 text-ink-950' : (row.rank === 2 ? 'bg-sand-200 text-ink-950' : 'bg-sand-400 text-ink-950')" x-text="row.rank"></span>
                                    </span>
                                    <p class="mt-4 w-full truncate font-bold" :class="row.rank === 1 ? 'text-lg' : ''" x-text="row.name"></p>
                                    <p class="num mt-1"><span class="font-extrabold" :class="row.rank === 1 ? 'text-3xl text-brand-300' : 'text-2xl'" x-text="row.score"></span> <span class="text-xs text-fg-subtle">{{ __('points') }}</span></p>
                                </li>
                            </template>
                        </ol>
                        {{-- Everyone else --}}
                        <ol class="mt-3 space-y-2" x-show="rows.length > 3">
                            <template x-for="row in rows.slice(3)" :key="row.name">
                                <li class="flex items-center gap-3 rounded-2xl border border-line bg-ink-850 px-4 py-3 transition-colors sm:gap-4 sm:px-6"
                                    :class="{ 'row-flash': flash[row.name] && Date.now() - flash[row.name] < 2000 }">
                                    <span class="num w-7 shrink-0 text-center text-sm font-bold text-fg-subtle" x-text="row.rank"></span>
                                    <x-avatar-live class="size-11 text-sm" />
                                    <span class="min-w-0 flex-1 truncate font-semibold" x-text="row.name"></span>
                                    <span class="num text-end"><span class="text-xl font-extrabold" x-text="row.score"></span> <span class="text-xs text-fg-subtle">{{ __('points') }}</span></span>
                                </li>
                            </template>
                        </ol>
                    </div>
                    <p class="mt-6 text-xs text-fg-subtle">{{ __('Ranking: approved registrations first; on a tie, the leader who reached the score earlier ranks higher.') }}</p>
                </div>
            </section>
        @else
            <div x-data="poller(@js(route('live.competition')), 15000)"></div>
            <div class="h-16"></div>
        @endif
    @endif
</x-layouts.site>
