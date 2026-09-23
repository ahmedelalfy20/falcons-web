@php
    use App\Enums\Permission;
    $u = auth()->user();
    $initial = [
        'counts' => $counts,
        'alerts' => $alerts,
        'leaderboard' => $leaderboard?->map(fn ($r) => ['rank' => $r->rank, 'name' => $r->name, 'photo' => $r->photoUrl(), 'initials' => $r->initials(), 'code' => $r->unique_code, 'accepted' => (int) $r->accepted, 'pending' => (int) $r->pending])->values(),
        'recent' => $recent?->map(fn ($r) => [
            'id' => $r->id, 'name' => $r->full_name, 'leader' => $r->leader?->name, 'status' => $r->status->value,
            'status_label' => $r->status->label(), 'ago' => $r->created_at->diffForHumans(), 'url' => route('admin.registrations.show', $r),
        ])->values(),
    ];
    $maxHour = max(1, collect($hourly)->max('count') ?? 1);
@endphp
<x-layouts.admin :heading="__('Overview')" :subheading="$competition ? ($competition->tr('name')).($round ? ' · '.$round->displayName() : '') : __('No active competition')">
    <x-slot:actions>
        @if ($u->hasPermission(Permission::RegistrationsView))
            <a href="{{ route('admin.registrations.index', ['status' => 'pending']) }}" class="btn btn-primary"><x-icon name="inbox" class="size-4" />{{ __('Review pending') }}</a>
        @endif
    </x-slot:actions>

    <div x-data="{ d: @js($initial) }" @live-data.window="Object.assign(d, $event.detail)">
        <div x-data="poller(@js(route('admin.live')), 6000)"></div>

        {{-- Alerts --}}
        <div class="mb-6 space-y-2" x-show="d.alerts && d.alerts.length" @if (! $alerts) x-cloak @endif>
            <template x-for="a in d.alerts" :key="a.text">
                <div class="flex items-start gap-3 rounded-xl border px-4 py-3 text-sm"
                     :class="{ 'border-info/25 bg-info/[0.07] text-blue-100': a.level === 'info', 'border-warning/25 bg-warning/[0.07] text-amber-100': a.level === 'warning', 'border-danger/25 bg-danger/[0.08] text-red-100': a.level === 'danger' }" role="status">
                    <x-icon name="alert" class="mt-0.5 size-4 shrink-0" /><span class="flex-1" x-text="a.text"></span>
                    <a x-show="a.url" :href="a.url" class="shrink-0 font-semibold underline underline-offset-2" x-text="a.action"></a>
                </div>
            </template>
        </div>

        <div class="grid gap-5 xl:grid-cols-12">
            {{-- Live round --}}
            <section class="card card-pad xl:col-span-5" aria-labelledby="round-card">
                @if ($round)
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-fg-subtle rtl:tracking-normal">{{ __('Current round') }}</p>
                            <h2 id="round-card" class="mt-1 text-lg font-bold">{{ $round->displayName() }}</h2>
                        </div>
                        <a href="{{ route('admin.competition.index') }}" class="btn btn-outline btn-sm">{{ __('Controls') }}<x-icon name="arrow-right" class="size-4" /></a>
                    </div>
                    <div class="mt-5">@include('competition.partials.timer', ['round' => $round])</div>
                    <div class="mt-5 flex items-center gap-2 border-t border-line pt-4 text-sm" x-data="{ open: @js($round->acceptsRegistrations()) }" @round:update.window="open = $event.detail.registration_open">
                        <span class="size-2 rounded-full" :class="open ? 'bg-success' : 'bg-fg-subtle'"></span>
                        <span class="text-fg-muted" x-text="open ? @js(__('Registration is open')) : @js(__('Registration is closed'))"></span>
                        @if ($pastRounds)<span class="ms-auto text-xs text-fg-subtle">{{ trans_choice(':count past round saved|:count past rounds saved', $pastRounds) }}</span>@endif
                    </div>
                @else
                    <x-empty icon="timer" :title="__('No round yet')" :text="__('Create a competition and its first round to start accepting registrations.')">
                        @can('manage-competitions')<a href="{{ route('admin.competition.index') }}" class="btn btn-primary">{{ __('Set up competition') }}</a>@endcan
                    </x-empty>
                @endif
            </section>

            {{-- KPIs: the numbers admins act on --}}
            <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:col-span-7" aria-label="{{ __('Key numbers') }}">
                @php
                    $kpis = [
                        ['key' => 'pending', 'label' => __('Pending review'), 'icon' => 'clock', 'tone' => 'text-warning', 'href' => $u->hasPermission(Permission::RegistrationsView) ? route('admin.registrations.index', ['status' => 'pending']) : null, 'wide' => true],
                        ['key' => 'accepted', 'label' => __('Accepted'), 'icon' => 'check-circle', 'tone' => 'text-success', 'href' => $u->hasPermission(Permission::RegistrationsView) ? route('admin.registrations.index', ['status' => 'accepted']) : null],
                        ['key' => 'rejected', 'label' => __('Rejected'), 'icon' => 'x-circle', 'tone' => 'text-danger', 'href' => $u->hasPermission(Permission::RegistrationsView) ? route('admin.registrations.index', ['status' => 'rejected']) : null],
                        ['key' => 'participants', 'label' => __('Participants this round'), 'icon' => 'users', 'tone' => 'text-fg-muted', 'href' => null],
                        ['key' => 'leaders', 'label' => __('Total leaders'), 'icon' => 'trophy', 'tone' => 'text-fg-muted', 'href' => $u->hasPermission(Permission::LeadersView) ? route('admin.leaders.index') : null],
                    ];
                @endphp
                @foreach ($kpis as $k)
                    <{!! $k['href'] ? 'a href="'.e($k['href']).'"' : 'div' !!} @class(['card flex flex-col justify-between gap-4 p-4 sm:p-5', 'card-hover' => $k['href'], 'col-span-2 sm:col-span-1 sm:row-span-2 border-warning/20' => $k['wide'] ?? false])>
                        <span class="flex items-center gap-2 text-sm text-fg-muted"><x-icon :name="$k['icon']" @class(['size-4', $k['tone']]) />{{ $k['label'] }}</span>
                        <span @class(['num font-extrabold tracking-tight', 'text-5xl' => $k['wide'] ?? false, 'text-3xl' => ! ($k['wide'] ?? false)]) x-text="d.counts.{{ $k['key'] }}">{{ $counts[$k['key']] }}</span>
                    </{{ $k['href'] ? 'a' : 'div' }}>
                @endforeach
            </section>
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-12">
            {{-- Leaderboard --}}
            @if ($leaderboard !== null)
                <section class="card xl:col-span-5" aria-labelledby="lb-title">
                    <div class="flex items-center justify-between border-b border-line px-5 py-4">
                        <h2 id="lb-title" class="font-bold">{{ __('Current leaderboard') }}</h2>
                        <a href="{{ route('admin.leaderboard') }}" class="text-sm font-medium text-brand-300 hover:text-brand-200">{{ __('Full board') }}</a>
                    </div>
                    <template x-if="!d.leaderboard || d.leaderboard.length === 0"><x-empty icon="trophy" :title="__('No leaders yet')" /></template>
                    <ol>
                        <template x-for="row in d.leaderboard" :key="row.code">
                            <li class="flex items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                                <span class="num flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold" :class="row.rank === 1 ? 'bg-brand-500 text-ink-950' : 'bg-white/[0.06] text-fg-muted'" x-text="row.rank"></span>
                                <x-avatar-live class="size-10 text-sm" />
                                <span class="min-w-0 flex-1"><span class="block truncate font-semibold" x-text="row.name"></span><span class="num block text-xs text-fg-subtle" dir="ltr" x-text="row.code"></span></span>
                                <span class="text-end"><span class="num block text-lg font-extrabold" x-text="row.accepted"></span><span class="block text-xs text-warning" x-show="row.pending > 0" x-text="row.pending + ' ' + @js(__('pending'))"></span></span>
                            </li>
                        </template>
                    </ol>
                </section>
            @endif

            {{-- Recent registrations with inline review --}}
            @if ($recent !== null)
                <section class="card {{ $leaderboard !== null ? 'xl:col-span-7' : 'xl:col-span-12' }}" aria-labelledby="recent-title">
                    <div class="flex items-center justify-between border-b border-line px-5 py-4">
                        <h2 id="recent-title" class="font-bold">{{ __('Recent registrations') }}</h2>
                        <a href="{{ route('admin.registrations.index', ['status' => 'all']) }}" class="text-sm font-medium text-brand-300 hover:text-brand-200">{{ __('View all') }}</a>
                    </div>
                    <template x-if="!d.recent || d.recent.length === 0"><x-empty icon="inbox" :title="__('No registrations yet')" :text="__('New registrations appear here in real time.')" /></template>
                    <ul>
                        <template x-for="r in d.recent" :key="r.id">
                            <li class="flex items-center gap-3 border-b border-line px-5 py-3 last:border-0">
                                <a :href="r.url" class="min-w-0 flex-1 hover:text-brand-200">
                                    <span class="block truncate font-medium" x-text="r.name"></span>
                                    <span class="block truncate text-xs text-fg-subtle"><span x-text="'#' + r.id"></span> · <span x-text="r.leader"></span> · <span x-text="r.ago"></span></span>
                                </a>
                                <span class="badge" :class="'badge-' + r.status" x-text="r.status_label"></span>
                            </li>
                        </template>
                    </ul>
                </section>
            @endif
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-12">
            @if ($hourly)
                {{-- Submissions per hour: one series, one hue, hover shows exact value, table for screen readers --}}
                <section class="card card-pad xl:col-span-7" aria-labelledby="hourly-title">
                    <div class="flex items-baseline justify-between gap-4">
                        <h2 id="hourly-title" class="font-bold">{{ __('Registrations per hour') }}</h2>
                        <span class="text-xs text-fg-subtle">{{ __('Last 12 hours') }}</span>
                    </div>
                    <div class="mt-6 flex h-40 items-end gap-[2px]" aria-hidden="true">
                        @foreach ($hourly as $h)
                            <div class="group relative flex h-full flex-1 items-end">
                                <div class="w-full rounded-t-[4px] bg-brand-500/85 transition-colors group-hover:bg-brand-400" style="height: {{ $h['count'] ? max(4, round($h['count'] / $maxHour * 100)) : 0 }}%"></div>
                                @if (! $h['count'])<div class="absolute inset-x-0 bottom-0 h-px bg-line-strong"></div>@endif
                                <div class="pointer-events-none absolute bottom-full start-1/2 z-10 mb-2 -translate-x-1/2 rounded-lg border border-line bg-ink-800 px-2.5 py-1.5 text-xs whitespace-nowrap opacity-0 shadow-xl transition-opacity group-hover:opacity-100 rtl:translate-x-1/2">
                                    <span class="num font-semibold text-fg">{{ $h['count'] }}</span> <span class="text-fg-muted">· {{ $h['label'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-2 flex justify-between text-[0.7rem] text-fg-subtle num" aria-hidden="true">
                        <span>{{ $hourly[0]['label'] }}</span><span>{{ $hourly[5]['label'] }}</span><span>{{ end($hourly)['label'] }}</span>
                    </div>
                    <table class="sr-only"><caption>{{ __('Registrations per hour') }}</caption><thead><tr><th>{{ __('Hour') }}</th><th>{{ __('Registrations') }}</th></tr></thead>
                        <tbody>@foreach ($hourly as $h)<tr><td>{{ $h['label'] }}</td><td>{{ $h['count'] }}</td></tr>@endforeach</tbody></table>
                </section>
            @endif

            @can('view-audit-logs')
                <section class="card {{ $hourly ? 'xl:col-span-5' : 'xl:col-span-12' }}" aria-labelledby="activity-title">
                    <div class="flex items-center justify-between border-b border-line px-5 py-4">
                        <h2 id="activity-title" class="font-bold">{{ __('Recent activity') }}</h2>
                        <a href="{{ route('admin.audit.index') }}" class="text-sm font-medium text-brand-300 hover:text-brand-200">{{ __('Audit log') }}</a>
                    </div>
                    @forelse ($activity as $log)
                        <div class="flex gap-3 border-b border-line px-5 py-3 last:border-0">
                            <span class="mt-2 size-1.5 shrink-0 rounded-full bg-fg-subtle"></span>
                            <div class="min-w-0"><p class="text-sm leading-snug">{{ $log->sentence() }}</p><p class="mt-0.5 text-xs text-fg-subtle">{{ $log->created_at->diffForHumans() }}</p></div>
                        </div>
                    @empty
                        <x-empty icon="history" :title="__('No activity yet')" />
                    @endforelse
                </section>
            @endcan
        </div>
    </div>
</x-layouts.admin>
