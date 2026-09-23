@php
    $u = auth()->user();
    $status = $round?->status->value;
@endphp
<x-layouts.admin :heading="__('Rounds & timer')" :subheading="$competition ? $competition->tr('name') : __('No active competition')">
    <x-slot:actions>
        @can('manage-competitions')
            @if ($competition)<a href="{{ route('admin.competitions.edit', $competition) }}" class="btn btn-outline"><x-icon name="settings" class="size-4" />{{ __('Competition settings') }}</a>@endif
            <a href="{{ route('admin.competitions.create') }}" class="btn btn-ghost"><x-icon name="plus" class="size-4" />{{ __('New competition') }}</a>
        @endcan
    </x-slot:actions>

    @if (! $competition)
        <div class="card"><x-empty icon="trophy" :title="__('No active competition')" :text="__('Create a competition (or activate an existing one) to start rounds.')">
            @can('manage-competitions')<a href="{{ route('admin.competitions.create') }}" class="btn btn-primary">{{ __('Create competition') }}</a>@endcan
        </x-empty></div>
    @else
        <div class="grid gap-5 xl:grid-cols-12">
            {{-- Timer control panel --}}
            <section class="card xl:col-span-7" aria-labelledby="timer-title">
                @if ($round)
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4 sm:px-6">
                        <h2 id="timer-title" class="font-bold">{{ $round->displayName() }}</h2>
                        <a href="{{ route('admin.rounds.show', $round) }}" class="text-sm font-medium text-brand-300 hover:text-brand-200">{{ __('Round details') }}</a>
                    </div>
                    <div class="px-5 py-6 sm:px-6">
                        @include('competition.partials.timer', ['round' => $round, 'big' => true])
                        <div x-data="poller(@js(route('live.competition')), 10000)"></div>

                        {{-- Primary state actions --}}
                        <div class="mt-6 flex flex-wrap gap-2">
                            @if (in_array($status, ['ready', 'draft']) && $u->can('start', $round))
                                <form method="POST" action="{{ route('admin.rounds.start', $round) }}">@csrf<button class="btn btn-primary"><x-icon name="play" class="size-4" />{{ __('Start round') }}</button></form>
                            @endif
                            @if ($status === 'running' && $u->can('adjustTimer', $round))
                                <form method="POST" action="{{ route('admin.rounds.pause', $round) }}">@csrf<button class="btn btn-outline"><x-icon name="pause" class="size-4" />{{ __('Pause') }}</button></form>
                            @endif
                            @if ($status === 'paused' && $u->can('adjustTimer', $round))
                                <form method="POST" action="{{ route('admin.rounds.resume', $round) }}">@csrf<button class="btn btn-primary"><x-icon name="play" class="size-4" />{{ __('Resume') }}</button></form>
                            @endif
                            @if (in_array($status, ['running', 'paused']) && $u->can('finish', $round))
                                <form method="POST" action="{{ route('admin.rounds.finish', $round) }}" onsubmit="return confirm(@js(__('Finish this round now? Registration closes and results are frozen.')))">@csrf<button class="btn btn-danger"><x-icon name="stop" class="size-4" />{{ __('Finish round') }}</button></form>
                            @endif
                        </div>

                        {{-- Time adjustment --}}
                        @if ($status !== 'finished' && $u->can('adjustTimer', $round))
                            <form method="POST" action="{{ route('admin.rounds.adjust', $round) }}" class="mt-6 rounded-2xl border border-line bg-ink-900 p-4" x-data="{ minutes: 30 }">
                                @csrf
                                <p class="text-sm font-semibold">{{ __('Adjust time') }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach ([5, 15, 30, 60] as $m)
                                        <button type="button" @click="minutes = {{ $m }}" class="min-h-10 rounded-full border px-3.5 text-sm transition-colors" :class="minutes === {{ $m }} ? 'border-brand-500/60 bg-brand-500/10 text-brand-200' : 'border-line text-fg-muted hover:text-fg'">{{ $m < 60 ? __(':m min', ['m' => $m]) : __('1 hour') }}</button>
                                    @endforeach
                                    <label class="flex items-center gap-2 text-sm text-fg-muted"><span class="sr-only">{{ __('Custom minutes') }}</span>
                                        <input type="number" name="minutes" x-model.number="minutes" min="1" max="10080" class="input !min-h-10 w-24 text-center">{{ __('min') }}</label>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-2 sm:flex">
                                    <button name="direction" value="remove" class="btn btn-outline btn-sm" onclick="return confirm(@js(__('Remove time from the timer?')))"><x-icon name="minus" class="size-4" />{{ __('Remove time') }}</button>
                                    <button name="direction" value="add" class="btn btn-outline btn-sm"><x-icon name="plus" class="size-4" />{{ __('Add time') }}</button>
                                </div>
                                @error('minutes')<p class="field-error">{{ $message }}</p>@enderror
                            </form>
                        @endif

                        {{-- Registration switch --}}
                        @if ($status !== 'finished' && $u->can('toggleRegistration', $round))
                            <form method="POST" action="{{ route('admin.rounds.registration', $round) }}" class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-ink-900 p-4">
                                @csrf
                                <input type="hidden" name="open" value="{{ $round->registration_enabled ? 0 : 1 }}">
                                <div>
                                    <p class="text-sm font-semibold">{{ __('Participant registration') }}</p>
                                    <p class="mt-0.5 text-xs text-fg-subtle">{{ $round->registration_enabled ? __('Open while the timer is running.') : __('Closed — the form shows “Registration is currently closed.”') }}</p>
                                </div>
                                <button @class(['btn btn-sm', 'btn-danger' => $round->registration_enabled, 'btn-success' => ! $round->registration_enabled])>
                                    <x-icon :name="$round->registration_enabled ? 'lock' : 'check'" class="size-4" />{{ $round->registration_enabled ? __('Close registration') : __('Open registration') }}
                                </button>
                            </form>
                        @endif
                    </div>
                @else
                    <x-empty icon="timer" :title="__('No rounds yet')" :text="__('Create the first round below.')" />
                @endif
            </section>

            {{-- New round --}}
            <aside class="space-y-5 xl:col-span-5">
                @can('create', App\Models\Round::class)
                    <section class="card card-pad" aria-labelledby="new-round">
                        <h2 id="new-round" class="font-bold">{{ __('Start a new round') }}</h2>
                        <p class="mt-1 text-sm leading-relaxed text-fg-muted">{{ __('The current round is finished and its results are saved. Every leader starts the new round at 0.') }}</p>
                        @php $def = (int) $competition->config('default_round_minutes'); @endphp
                        <form method="POST" action="{{ route('admin.rounds.store', $competition) }}" class="mt-5 space-y-4" onsubmit="return confirm(@js(__('Start a new round? The current round will be finished.')))">
                            @csrf
                            <div class="grid grid-cols-2 gap-3">
                                <x-field name="duration_hours" type="number" :label="__('Hours')" :value="intdiv($def, 60)" min="0" max="720" required />
                                <x-field name="duration_minutes" type="number" :label="__('Minutes')" :value="$def % 60" min="0" max="59" required />
                            </div>
                            <x-toggle name="start_now" :label="__('Start the timer immediately')" :checked="true" />
                            <button class="btn btn-primary w-full"><x-icon name="plus" class="size-4" />{{ $round ? __('Start new round') : __('Create first round') }}</button>
                        </form>
                    </section>
                @endcan
                <section class="card card-pad text-sm leading-relaxed text-fg-muted">
                    <p class="flex items-center gap-2 font-semibold text-fg"><x-icon name="shield-check" class="size-4 text-brand-400" />{{ __('Server-controlled timer') }}</p>
                    <p class="mt-2">{{ __('The countdown runs on the server. Refreshing pages or changing a device clock never affects it. When it reaches zero the round finishes and registration closes automatically; pending registrations stay reviewable.') }}</p>
                </section>
            </aside>
        </div>

        {{-- Round history --}}
        <section class="card mt-5" aria-labelledby="history-title">
            <h2 id="history-title" class="border-b border-line px-5 py-4 font-bold sm:px-6">{{ __('All rounds') }}</h2>
            @if ($history->isEmpty())
                <x-empty icon="history" :title="__('No rounds yet')" />
            @else
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>{{ __('Round') }}</th><th>{{ __('Status') }}</th><th>{{ __('Started') }}</th><th>{{ __('Finished') }}</th><th class="!text-end">{{ __('Registrations') }}</th><th class="!text-end">{{ __('Accepted') }}</th><th>{{ __('Winner') }}</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($history as $r)
                                @php $w = $r->accepted_count ? $winnerFor($r) : null; @endphp
                                <tr>
                                    <td class="font-semibold">{{ $r->displayName() }}</td>
                                    <td><span class="badge badge-{{ $r->status->value }}">{{ $r->status->label() }}</span></td>
                                    <td class="text-fg-muted">{{ $r->started_at?->translatedFormat('j M, H:i') ?? '—' }}</td>
                                    <td class="text-fg-muted">{{ $r->finished_at?->translatedFormat('j M, H:i') ?? '—' }}</td>
                                    <td class="num text-end">{{ $r->registrations_count }}</td>
                                    <td class="num text-end font-bold">{{ $r->accepted_count }}</td>
                                    <td>@if ($w && $w->score > 0)<span class="inline-flex items-center gap-1.5"><x-icon name="trophy" class="size-4 text-brand-400" />{{ $w->name }} <span class="num text-fg-subtle">({{ $w->score }})</span></span>@else — @endif</td>
                                    <td class="text-end"><a href="{{ route('admin.rounds.show', $r) }}" class="btn btn-ghost btn-sm">{{ __('Open') }}<x-icon name="chevron-right" class="size-4" /></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        @if ($competitions->count() > 1)
            <section class="card mt-5" aria-labelledby="comps">
                <h2 id="comps" class="border-b border-line px-5 py-4 font-bold sm:px-6">{{ __('All competitions') }}</h2>
                @foreach ($competitions as $c)
                    <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 last:border-0 sm:px-6">
                        <span class="font-medium">{{ $c->name }}</span>
                        <span class="flex items-center gap-3"><span class="badge {{ $c->isActive() ? 'badge-accepted' : 'badge-neutral' }}">{{ $c->status->label() }}</span><a href="{{ route('admin.competitions.edit', $c) }}" class="btn btn-ghost btn-sm">{{ __('Edit') }}</a></span>
                    </div>
                @endforeach
            </section>
        @endif
    @endif
</x-layouts.admin>
