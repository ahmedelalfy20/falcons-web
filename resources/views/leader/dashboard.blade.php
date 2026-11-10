@php
    $s = [
        'rank' => $stats?->rank,
        'accepted' => (int) ($stats?->accepted ?? 0),
        'points' => (int) ($stats?->score ?? 0),
        'total' => $gifts['total'],
        'pending' => (int) ($stats?->pending ?? 0),
        'rejected' => (int) ($stats?->rejected ?? 0),
    ];
    $url = $leader->referralUrl();
@endphp
<x-layouts.site :title="__('Leader dashboard')" :noindex="true">
    <div class="pt-24 pb-16 sm:pt-28" x-data="{ s: @js($s), status: @js($leader->status) }"
         @live-data.window="$event.detail.stats && (s = $event.detail.stats); if ($event.detail.leader_status && $event.detail.leader_status !== status) { location.reload() }">
        <div x-data="poller(@js(route('leader.live')), {{ $leader->isPending() ? 8000 : 10000 }})"></div>
        <div class="container-x">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-center gap-4">
                    {{-- Profile photo — click to change --}}
                    <form method="POST" action="{{ route('leader.photo') }}" enctype="multipart/form-data" x-data x-ref="pf" class="shrink-0">
                        @csrf
                        <label class="group relative block cursor-pointer" title="{{ __('Change photo') }}">
                            <x-leader-avatar :leader="$leader" class="size-16 text-lg sm:size-20 sm:text-xl" />
                            <span class="absolute inset-0 flex items-center justify-center rounded-full bg-ink-950/60 opacity-0 transition-opacity group-hover:opacity-100 group-focus-within:opacity-100"><x-icon name="edit" class="size-5" /></span>
                            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="$refs.pf.submit()" aria-label="{{ __('Change photo') }}">
                        </label>
                    </form>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="eyebrow">{{ $competition->tr('name') }}</p>
                            @if ($leader->team)
                                <span class="chip border-brand-500/30 bg-brand-500/[0.1] text-brand-300 text-xs px-2 py-0.5 inline-flex items-center gap-1 font-medium"><x-icon name="users" class="size-3.5" />{{ $leader->team }}</span>
                            @endif
                        </div>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ __('Hi, :name', ['name' => \Illuminate\Support\Str::before($leader->name, ' ')]) }}</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($leader->isActive())<span class="badge badge-accepted">{{ __('Active leader') }}</span>@else<span class="badge {{ $leader->statusBadge() }}">{{ $leader->statusLabel() }}</span>@endif
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-sm"><x-icon name="logout" class="size-4" />{{ __('Log out') }}</button></form>
                </div>
            </div>

            @error('photo')<x-alert level="danger" class="mt-6">{{ $message }}</x-alert>@enderror
            @if ($leader->isPending())
                <div class="mt-6 flex items-start gap-4 rounded-2xl border border-warning/30 bg-warning/[0.07] p-5">
                    <span class="relative mt-1 flex size-3 shrink-0"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-warning opacity-60 motion-reduce:hidden"></span><span class="relative inline-flex size-3 rounded-full bg-warning"></span></span>
                    <div>
                        <p class="font-semibold text-amber-100">{{ __('Your request is waiting for admin approval') }}</p>
                        <p class="mt-1 text-sm text-fg-muted">{{ __('Your code and QR are ready below, but they start accepting registrations — and you appear on the live leaderboard — once an admin approves you. This page updates automatically.') }}</p>
                    </div>
                </div>
            @elseif ($leader->status === 'rejected')
                <x-alert level="danger" class="mt-6">{{ __('Your leader request was not approved. Please contact the Falcons team if you think this is a mistake.') }}</x-alert>
            @elseif (! $leader->isActive())
                <x-alert level="warning" class="mt-6">{{ __('Your leader account is suspended. Your QR code will not accept new registrations until an admin reactivates it.') }}</x-alert>
            @endif

            <div class="mt-8">@include('competition.partials.account-card', ['account' => $account, 'leader' => $leader, 'cta' => false])</div>

            <div x-data="{ tab: 'overview', tabs: ['overview', 'gifts', 'board', 'history'], set(t) { this.tab = t; history.replaceState(null, '', '#' + t) } }"
                 x-init="const h = location.hash.slice(1); if (tabs.includes(h)) tab = h; window.addEventListener('hashchange', () => { const t = location.hash.slice(1); if (tabs.includes(t)) tab = t })">
            <nav class="sticky top-16 z-30 -mx-4 mt-6 overflow-x-auto bg-ink-950/90 px-4 py-2 backdrop-blur [scrollbar-width:none] sm:mx-0 sm:rounded-2xl sm:border sm:border-line sm:bg-ink-850 sm:p-1.5 lg:top-[4.75rem]" role="tablist" aria-label="{{ __('Your account') }}">
                <div class="flex min-w-max gap-1">
                    @foreach (['overview' => ['dashboard', __('Overview')], 'gifts' => ['gift', __('Gifts')], 'board' => ['trophy', __('Leaderboard')], 'history' => ['history', __('Points history')]] as $key => [$icon, $label])
                        <button type="button" role="tab" :aria-selected="tab === '{{ $key }}'" @click="set('{{ $key }}')"
                                class="flex min-h-11 items-center gap-2 rounded-xl px-4 text-sm font-semibold transition-colors"
                                :class="tab === '{{ $key }}' ? 'bg-brand-500 text-ink-950 shadow-[0_8px_24px_-10px_rgb(34_197_132/0.7)]' : 'text-fg-muted hover:bg-ink-800 hover:text-fg'">
                            <x-icon :name="$icon" class="size-4" />{{ $label }}
                        </button>
                    @endforeach
                </div>
            </nav>

            <div x-show="tab === 'overview'" role="tabpanel">
            <div class="mt-5 grid gap-5 lg:grid-cols-12">
                {{-- Referral card --}}
                <section class="card overflow-hidden lg:col-span-5" aria-labelledby="ref-title" x-data="copyable(@js($url))">
                    <div class="flex flex-col items-center gap-6 p-6 sm:flex-row sm:items-start lg:flex-col lg:items-center">
                        <div class="shrink-0 rounded-2xl bg-white p-3">
                            <img src="{{ route('leader.qr.svg') }}" alt="{{ __('Your referral QR code') }}" width="176" height="176" class="size-40 sm:size-44">
                        </div>
                        <div class="w-full min-w-0 text-center sm:text-start lg:text-center">
                            <h2 id="ref-title" class="text-sm font-medium text-fg-muted">{{ __('Your referral code') }}</h2>
                            <p class="num mt-1 text-2xl font-extrabold tracking-[0.06em] whitespace-nowrap" dir="ltr">{{ $leader->unique_code }}</p>
                            <p class="mt-3 truncate rounded-lg bg-ink-900 px-3 py-2 text-xs text-fg-subtle" dir="ltr" title="{{ $url }}">{{ $url }}</p>
                            <div class="mt-4 grid grid-cols-2 gap-2">
                                <button type="button" class="btn btn-outline btn-sm" @click="copy()">
                                    <x-icon name="copy" class="size-4" /><span x-text="copied ? @js(__('Copied!')) : @js(__('Copy link'))">{{ __('Copy link') }}</span>
                                </button>
                                <a href="{{ route('leader.qr.png') }}" class="btn btn-outline btn-sm" download><x-icon name="download" class="size-4" />{{ __('Download QR') }}</a>
                                <button type="button" x-show="canShare" x-cloak class="btn btn-primary btn-sm col-span-2" @click="share(@js(__('Register with Falcons')))"><x-icon name="share" class="size-4" />{{ __('Share') }}</button>
                                <a x-show="!canShare" href="https://wa.me/?text={{ rawurlencode(__('Register with Falcons through my link:').' '.$url) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm col-span-2"><x-icon name="whatsapp" class="size-4" />{{ __('Share on WhatsApp') }}</a>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Score + round --}}
                <section class="grid gap-5 lg:col-span-7" aria-label="{{ __('Your performance') }}">
                    <div class="card card-pad">
                        @if ($round)
                            <div class="flex flex-wrap items-start justify-between gap-6">
                                <div>
                                    <p class="text-sm text-fg-muted">{{ $round->displayName() }} · {{ __('Your score') }}</p>
                                    <p class="num mt-2 text-6xl font-extrabold tracking-tight"><span x-text="s.points">{{ $s['points'] }}</span> <span class="text-lg font-semibold text-fg-subtle">{{ __('points') }}</span></p>
                                    <p class="mt-1 text-sm text-fg-subtle"><span class="num" x-text="s.accepted">{{ $s['accepted'] }}</span> {{ __('approved registrations') }} · {{ __(':n points each', ['n' => $perRegistration]) }}</p>
                                </div>
                                <div class="text-end">
                                    <p class="text-sm text-fg-muted">{{ __('Rank') }}</p>
                                    <p class="num mt-2 text-4xl font-extrabold"><span class="text-brand-400">#</span><span x-text="s.rank ?? '—'">{{ $s['rank'] ?? '—' }}</span></p>
                                    <p class="mt-1 text-sm text-fg-subtle">{{ __('of :n leaders', ['n' => $totalLeaders]) }}</p>
                                </div>
                            </div>
                            <div class="mt-6 grid grid-cols-2 gap-3 border-t border-line pt-5">
                                <div><p class="text-xs text-fg-subtle">{{ __('Pending review') }}</p><p class="num mt-1 text-xl font-bold text-warning" x-text="s.pending">{{ $s['pending'] }}</p></div>
                                <div><p class="text-xs text-fg-subtle">{{ __('Rejected') }}</p><p class="num mt-1 text-xl font-bold text-fg-muted" x-text="s.rejected">{{ $s['rejected'] }}</p></div>
                            </div>
                        @else
                            <x-empty icon="clock" :title="__('The competition has not started yet.')" :text="__('Share your QR now — registrations open when the first round starts.')" />
                        @endif
                    </div>
                    @if ($round)
                        <div class="card card-pad">@include('competition.partials.timer', ['round' => $round])</div>
                    @endif
                </section>
            </div>

            <div class="mt-5 grid gap-5 lg:grid-cols-12">
                <section class="card lg:col-span-7" aria-labelledby="recent-title">
                    <div class="flex items-center justify-between border-b border-line px-5 py-4 sm:px-6">
                        <h2 id="recent-title" class="font-bold">{{ __('Your latest registrations') }}</h2>
                        @if ($round)<span class="text-xs text-fg-subtle">{{ $round->displayName() }}</span>@endif
                    </div>
                    @forelse ($recent as $r)
                        <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-3.5 last:border-0 sm:px-6">
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ $r->full_name }}</p>
                                <p class="text-xs text-fg-subtle">{{ $r->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="badge badge-{{ $r->status->value }}">{{ $r->status->label() }}</span>
                        </div>
                    @empty
                        <x-empty icon="users" :title="__('No registrations yet')" :text="__('Share your QR code or link — new registrations appear here.')" />
                    @endforelse
                </section>

                <section class="card lg:col-span-5" aria-labelledby="history-title">
                    <div class="border-b border-line px-5 py-4 sm:px-6"><h2 id="history-title" class="font-bold">{{ __('Previous rounds') }}</h2></div>
                    @forelse ($history as $h)
                        <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-3.5 last:border-0 sm:px-6">
                            <div>
                                <p class="font-medium">{{ $h['round']->displayName() }}</p>
                                <p class="text-xs text-fg-subtle">{{ $h['round']->finished_at?->translatedFormat('j M Y') }}</p>
                            </div>
                            <div class="text-end">
                                <p class="num font-bold">{{ (int) ($h['row']?->score ?? 0) }} <span class="text-xs font-normal text-fg-subtle">{{ __('points') }}</span></p>
                                <p class="num text-xs text-fg-subtle">{{ __('Rank') }} #{{ $h['row']?->rank ?? '—' }}</p>
                            </div>
                        </div>
                    @empty
                        <x-empty icon="history" :title="__('No finished rounds yet')" />
                    @endforelse
                </section>
            </div>
            </div>{{-- /overview --}}

            <div x-show="tab === 'gifts'" x-cloak role="tabpanel">
                @if ($gifts['gifts']->isNotEmpty())
                    @include('leader.partials.gifts', ['g' => $gifts])
                @else
                    <div class="card mt-5"><x-empty icon="gift" :title="__('Gifts are coming soon')" :text="__('Keep collecting points — they will count toward the gifts once they are announced.')" /></div>
                @endif
            </div>

            <div x-show="tab === 'board'" x-cloak role="tabpanel" class="mt-5"
                 x-data="{ rows: @js($board['rows']), me: @js($board['me']), flash: {} }"
                 @live-data.window="if ($event.detail.leaderboard) { const prev = Object.fromEntries(rows.map(r => [r.name, r.score])); rows = $event.detail.leaderboard; me = $event.detail.me ?? null; rows.forEach(r => { if (prev[r.name] !== undefined && prev[r.name] !== r.score) flash[r.name] = Date.now() }) }">
                <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-bold">{{ __('Top One') }}</h2>
                        <p class="text-sm text-fg-muted">@if ($round){{ $round->displayName() }} · @endif{{ __('Top 20 leaders') }}</p>
                    </div>
                    <p class="flex items-center gap-2 text-sm text-fg-subtle"><span class="size-2 rounded-full bg-brand-400"></span>{{ __('Updates automatically') }}</p>
                </div>
                @include('competition.partials.board-table')
            </div>

            <div x-show="tab === 'history'" x-cloak role="tabpanel" class="mt-5">
                <section class="card" aria-labelledby="log-title">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4 sm:px-6">
                        <div>
                            <h2 id="log-title" class="font-bold">{{ __('Points history') }}</h2>
                            <p class="text-xs text-fg-subtle">{{ __('Every approved registration adds :n points.', ['n' => $perRegistration]) }}</p>
                        </div>
                        <p class="num text-sm text-fg-muted">{{ __('Total points') }}: <span class="font-extrabold text-brand-300">{{ number_format($gifts['total']) }}</span></p>
                    </div>
                    @forelse ($pointsLog as $log)
                        <div class="flex items-center gap-4 border-b border-line px-5 py-3.5 last:border-0 sm:px-6">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-500/10 text-brand-300"><x-icon name="check" class="size-4" /></span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ $log->full_name }}</p>
                                <p class="text-xs text-fg-subtle">{{ __('Approved registration') }} · {{ $log->round?->displayName() }} · {{ ($log->reviewed_at ?? $log->created_at)->translatedFormat('j M Y') }}</p>
                            </div>
                            <span class="num shrink-0 rounded-full bg-brand-500/10 px-3 py-1 text-sm font-extrabold text-brand-300" dir="ltr">+{{ $perRegistration }}</span>
                        </div>
                    @empty
                        <x-empty icon="history" :title="__('No points yet')" :text="__('Share your QR code — each approved registration adds points here.')" />
                    @endforelse
                </section>
            </div>
            </div>{{-- /tabs --}}
        </div>
    </div>
</x-layouts.site>
