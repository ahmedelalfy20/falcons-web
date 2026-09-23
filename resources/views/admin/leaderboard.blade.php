<x-layouts.admin :heading="__('Leaderboard')" :subheading="__('Ranked by accepted registrations; ties go to the leader who reached the score first.')">
    <x-slot:actions>
        @if ($rounds->count() > 1)
            <form method="GET">
                <label for="round" class="sr-only">{{ __('Round') }}</label>
                <select id="round" name="round" class="input !min-h-11 w-48" onchange="this.form.submit()">
                    @foreach ($rounds->reverse() as $r)<option value="{{ $r->id }}" @selected($round?->id === $r->id)>{{ $r->displayName() }} — {{ $r->status->label() }}</option>@endforeach
                </select>
            </form>
        @endif
    </x-slot:actions>

    @if (! $round)
        <div class="card"><x-empty icon="trophy" :title="__('No rounds yet')" /></div>
    @else
        @php $top = $rows->take(3); @endphp
        @if ($top->where('score', '>', 0)->isNotEmpty())
            <div class="mb-5 grid gap-3 sm:grid-cols-3">
                @foreach ($top as $row)
                    <div @class(['card card-pad', 'border-brand-500/30 bg-brand-500/[0.05]' => $row->rank === 1])>
                        <div class="flex items-center gap-3">
                            <x-leader-avatar :leader="$row" class="size-12 text-base" />
                            <div class="min-w-0">
                                <p class="text-sm text-fg-muted">{{ [1 => __('1st place'), 2 => __('2nd place'), 3 => __('3rd place')][$row->rank] }}</p>
                                <p class="truncate text-lg font-bold">{{ $row->name }}</p>
                            </div>
                        </div>
                        <p class="num mt-3 text-4xl font-extrabold">{{ $row->score }}</p>
                    </div>
                @endforeach
            </div>
        @endif
        @if ($rows->isEmpty())
            <div class="card"><x-empty icon="users" :title="__('No leaders yet')" /></div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr>
                        <th scope="col" class="w-16">{{ __('Rank') }}</th>
                        <th scope="col">{{ __('Leader') }}</th>
                        <th scope="col">{{ __('Referral code') }}</th>
                        <th scope="col" class="!text-end">{{ __('Accepted') }}</th>
                        <th scope="col" class="!text-end">{{ __('Pending') }}</th>
                        <th scope="col" class="!text-end">{{ __('Rejected') }}</th>
                        <th scope="col" class="!text-end">{{ __('Score') }}</th>
                        <th scope="col" class="!text-end">{{ __('Score reached') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td class="num font-bold {{ $row->rank <= 3 ? 'text-brand-300' : 'text-fg-subtle' }}">#{{ $row->rank }}</td>
                                <td><div class="flex items-center gap-3"><x-leader-avatar :leader="$row" class="size-9 text-xs" /><span><a href="{{ route('admin.leaders.show', $row->id) }}" class="font-semibold hover:text-brand-200">{{ $row->name }}</a>@if ($row->status !== 'active') <span class="badge badge-rejected ms-1">{{ __('Suspended') }}</span>@endif</span></div></td>
                                <td class="num text-fg-muted" dir="ltr">{{ $row->unique_code }}</td>
                                <td class="num text-end">{{ $row->accepted }}</td>
                                <td class="num text-end text-warning">{{ $row->pending }}</td>
                                <td class="num text-end text-fg-muted">{{ $row->rejected }}</td>
                                <td class="num text-end text-lg font-extrabold">{{ $row->score }}</td>
                                <td class="num text-end text-xs text-fg-subtle">{{ $row->reached_at ? \Illuminate\Support\Carbon::parse($row->reached_at)->format('j M H:i:s') : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endif
</x-layouts.admin>
