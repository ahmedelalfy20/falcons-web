@php
    $tabs = ['pending' => __('Requests'), 'active' => __('Active'), 'suspended' => __('Suspended'), 'rejected' => __('Rejected'), 'all' => __('All')];
    $current = in_array($status, App\Models\Leader::STATUSES, true) ? $status : 'all';
@endphp
<x-layouts.admin :heading="__('Leaders')" :subheading="$competition ? __('New leaders join the live leaderboard once you approve their request.') : __('No active competition')">
    <x-slot:actions>
        @can('create', App\Models\Leader::class)
            <a href="{{ route('admin.leaders.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" />{{ __('Add leader') }}</a>
        @endcan
    </x-slot:actions>

    {{-- Status tabs --}}
    <nav class="-mx-4 flex gap-1 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0" aria-label="{{ __('Filter by status') }}">
        @foreach ($tabs as $key => $label)
            @php $n = $key === 'all' ? array_sum($statusCounts->all()) : (int) ($statusCounts[$key] ?? 0); $active = $current === $key; @endphp
            <a href="{{ route('admin.leaders.index', array_filter(['status' => $key, 'q' => $q])) }}"
               @class(['flex min-h-11 shrink-0 items-center gap-2 rounded-full px-4 text-sm font-medium transition-colors', 'bg-fg text-ink-950' => $active, 'text-fg-muted hover:bg-white/[0.05] hover:text-fg' => ! $active])
               @if ($active) aria-current="page" @endif>
                {{ $label }}
                <span @class(['num rounded-full px-1.5 text-xs', 'bg-ink-950/10' => $active, 'bg-warning text-ink-950' => ! $active && $key === 'pending' && $n > 0, 'bg-white/[0.07]' => ! $active && ! ($key === 'pending' && $n > 0)])>{{ $n }}</span>
            </a>
        @endforeach
    </nav>

    <form method="GET" class="card mt-4 flex flex-col gap-3 p-4 sm:flex-row">
        <input type="hidden" name="status" value="{{ $current }}">
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute start-4 top-1/2 size-4 -translate-y-1/2 text-fg-subtle" />
            <label for="q" class="sr-only">{{ __('Search') }}</label>
            <input id="q" type="search" name="q" value="{{ $q }}" placeholder="{{ __('Search name, code, phone or email') }}" class="input ps-11">
        </div>
        <button class="btn btn-primary">{{ __('Search') }}</button>
    </form>

    <div class="mt-4">
        @if ($leaders->isEmpty())
            <div class="card">
                @if ($current === 'pending' && ! $q)
                    <x-empty icon="check-circle" :title="__('No leader requests')" :text="__('New leader sign-ups appear here for approval.')" />
                @else
                    <x-empty icon="users" :title="__('No leaders found')" :text="$q ? __('Try a different search.') : __('Leaders appear here after they register or are added by an admin.')" />
                @endif
            </div>
        @elseif ($current === 'pending')
            {{-- Approval queue --}}
            <ul class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                @foreach ($leaders as $leader)
                    <li class="card flex flex-col p-5">
                        <div class="flex items-start gap-4">
                            <a href="{{ $leader->photoUrl(false) ?? '#' }}" @if ($leader->photo) target="_blank" rel="noopener" @endif class="shrink-0">
                                <x-leader-avatar :leader="$leader" class="size-20 text-xl" />
                            </a>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.leaders.show', $leader) }}" class="block truncate text-lg font-bold hover:text-brand-200">{{ $leader->name }}</a>
                                <p class="num mt-1 text-sm text-fg-muted" dir="ltr">{{ $leader->phone }}</p>
                                @if ($leader->email)<p class="truncate text-sm text-fg-muted" dir="ltr">{{ $leader->email }}</p>@endif
                                <p class="mt-1 text-xs text-fg-subtle">{{ __('Requested :when', ['when' => $leader->created_at->diffForHumans()]) }} · <span class="num" dir="ltr">{{ $leader->unique_code }}</span></p>
                            </div>
                        </div>
                        @can('review', $leader)
                            <div class="mt-5 grid grid-cols-2 gap-2 border-t border-line pt-4" x-data="{ busy: false }">
                                <form method="POST" action="{{ route('admin.leaders.reject', $leader) }}" @submit="busy = true">
                                    @csrf
                                    <button class="btn btn-outline w-full" :disabled="busy" onclick="return confirm(@js(__('Reject the request from :name?', ['name' => $leader->name])))"><x-icon name="x" class="size-4" />{{ __('Reject') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.leaders.approve', $leader) }}" @submit="busy = true">
                                    @csrf
                                    <button class="btn btn-success w-full" :disabled="busy"><x-icon name="check" class="size-4" />{{ __('Approve') }}</button>
                                </form>
                            </div>
                        @endcan
                    </li>
                @endforeach
            </ul>
            <div class="mt-5">{{ $leaders->links() }}</div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr>
                        <th scope="col">{{ __('Leader') }}</th>
                        <th scope="col">{{ __('Referral code') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col" class="!text-end">{{ $round ? __('Rank · score') : __('Score') }}</th>
                        <th scope="col" class="!text-end">{{ __('All-time registrations') }}</th>
                        <th scope="col"><span class="sr-only">{{ __('Actions') }}</span></th>
                    </tr></thead>
                    <tbody>
                        @foreach ($leaders as $leader)
                            @php $row = $board[$leader->id] ?? null; @endphp
                            <tr>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-leader-avatar :leader="$leader" class="size-10 text-sm" />
                                        <div class="min-w-0">
                                            <a href="{{ route('admin.leaders.show', $leader) }}" class="font-semibold hover:text-brand-200">{{ $leader->name }}</a>
                                            <div class="num text-xs text-fg-subtle" dir="ltr">{{ $leader->phone }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="num font-medium tracking-wide" dir="ltr">{{ $leader->unique_code }}</span></td>
                                <td><span class="badge {{ $leader->statusBadge() }}">{{ $leader->statusLabel() }}</span></td>
                                <td class="num text-end">@if ($row)<span class="text-fg-subtle">#{{ $row->rank }}</span> · <span class="font-bold">{{ $row->score }}</span>@if ($row->pending) <span class="text-xs text-warning">(+{{ $row->pending }})</span>@endif @else — @endif</td>
                                <td class="num text-end text-fg-muted">{{ $leader->registrations_count }}</td>
                                <td class="text-end">
                                    @if ($leader->isPending() || $leader->status === 'rejected')
                                        @can('review', $leader)
                                            <form method="POST" action="{{ route('admin.leaders.approve', $leader) }}" class="inline">@csrf<button class="btn btn-success btn-sm"><x-icon name="check" class="size-4" />{{ __('Approve') }}</button></form>
                                        @endcan
                                    @endif
                                    <a href="{{ route('admin.leaders.show', $leader) }}" class="btn btn-ghost btn-sm">{{ __('Open') }}<x-icon name="chevron-right" class="size-4" /></a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-5">{{ $leaders->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
