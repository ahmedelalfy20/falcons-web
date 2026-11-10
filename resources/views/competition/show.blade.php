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
                    ['icon' => 'trophy', 'title' => __('Approved = :n points', ['n' => $competition->pointsPerRegistration()]), 'text' => __('Points add up to unlock gifts.')],
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

        @if ($account)
            <section class="container-x pt-12" aria-label="{{ __('Your account') }}">@include('competition.partials.account-card', ['account' => $account, 'leader' => $leader])</section>
        @endif

        @if ($showBoard)
            <section class="py-16 sm:py-20" aria-labelledby="board-title"
                     x-data="{ rows: @js($board['rows']), me: @js($board['me']), flash: {} }"
                     @live-data.window="
                        if ($event.detail.leaderboard) {
                            const prev = Object.fromEntries(rows.map(r => [r.name, r.score]));
                            rows = $event.detail.leaderboard; me = $event.detail.me ?? null;
                            rows.forEach(r => { if (prev[r.name] !== undefined && prev[r.name] !== r.score) { flash[r.name] = Date.now(); } });
                        }">
                <div x-data="poller(@js(route('live.competition')), 8000)"></div>
                <div class="container-x">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="eyebrow">{{ __('Live leaderboard') }}</p>
                            <h2 id="board-title" class="h-section mt-3">{{ __('Top One') }}</h2>
                            <p class="mt-2 text-sm text-fg-muted">{{ $round->displayName() }} · {{ __('Top 20 leaders') }}</p>
                        </div>
                        <p class="flex items-center gap-2 text-sm text-fg-subtle"><span class="relative flex size-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-400 opacity-60 motion-reduce:hidden"></span><span class="relative inline-flex size-2 rounded-full bg-brand-400"></span></span>{{ __('Updates automatically') }}</p>
                    </div>
                    <div class="mt-8">@include('competition.partials.board-table')</div>
                    <p class="mt-6 text-xs text-fg-subtle">{{ __('Ranking: most points first; on a tie, the leader who reached the score earlier ranks higher.') }}</p>
                </div>
            </section>
        @else
            <div x-data="poller(@js(route('live.competition')), 15000)"></div>
            <div class="h-16"></div>
        @endif

        @if ($gifts->isNotEmpty())
            <section id="gifts" class="relative overflow-hidden border-t border-line py-16 sm:py-20" aria-labelledby="gifts-title">
                <div class="bg-glow pointer-events-none absolute inset-0 opacity-60" aria-hidden="true"></div>
                <div class="container-x relative">
                    <div class="flex flex-wrap items-end justify-between gap-6">
                        <div class="max-w-2xl">
                            <p class="eyebrow">{{ __('Leader rewards') }}</p>
                            <h2 id="gifts-title" class="h-section mt-3">{{ __('Collect points, unlock gifts') }}</h2>
                            <p class="lead mt-4">{{ __('Every approved registration earns :n points. Points never reset — each gift you unlock keeps them, and they carry you to the next one.', ['n' => $competition->pointsPerRegistration()]) }}</p>
                        </div>
                        @guest
                            <a href="{{ route('leader.signup') }}" class="btn btn-outline">{{ __('Become a leader') }} <x-icon name="arrow-right" class="size-4" /></a>
                        @endguest
                    </div>
                    <div class="mt-10">@include('competition.partials.gift-ladder', ['gifts' => $gifts])</div>
                </div>
            </section>
        @endif
    @endif
</x-layouts.site>
