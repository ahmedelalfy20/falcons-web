@php
    $s = [
        'rank' => $stats?->rank,
        'accepted' => (int) ($stats?->accepted ?? 0),
        'pending' => (int) ($stats?->pending ?? 0),
        'rejected' => (int) ($stats?->rejected ?? 0),
    ];
    $url = $leader->referralUrl();
@endphp
<x-layouts.site :title="__('Leader dashboard')">
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
                        <p class="eyebrow">{{ $competition->tr('name') }}</p>
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

            <div class="mt-8 grid gap-5 lg:grid-cols-12">
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
                                    <p class="num mt-2 text-6xl font-extrabold tracking-tight" x-text="s.accepted">{{ $s['accepted'] }}</p>
                                    <p class="mt-1 text-sm text-fg-subtle">{{ __('approved registrations') }}</p>
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
                                <p class="num font-bold">{{ (int) ($h['row']?->accepted ?? 0) }} <span class="text-xs font-normal text-fg-subtle">{{ __('points') }}</span></p>
                                <p class="num text-xs text-fg-subtle">{{ __('Rank') }} #{{ $h['row']?->rank ?? '—' }}</p>
                            </div>
                        </div>
                    @empty
                        <x-empty icon="history" :title="__('No finished rounds yet')" />
                    @endforelse
                </section>
            </div>
        </div>
    </div>
</x-layouts.site>
