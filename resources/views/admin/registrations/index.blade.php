@php
    $tabs = ['pending' => __('Pending'), 'accepted' => __('Accepted'), 'rejected' => __('Rejected'), 'all' => __('All')];
    $total = array_sum($tabCounts->all());
    $hasFilters = filled($filters['q'] ?? null) || filled($filters['leader'] ?? null) || filled($filters['from'] ?? null) || filled($filters['to'] ?? null);
@endphp
<x-layouts.admin :heading="__('Registrations')" :subheading="__('Review participants. Accepting counts one point for the leader; decisions are final and logged.')">
    {{-- Status tabs --}}
    <nav class="-mx-4 flex gap-1 overflow-x-auto px-4 pb-1 sm:mx-0 sm:px-0" aria-label="{{ __('Filter by status') }}">
        @foreach ($tabs as $key => $label)
            @php $n = $key === 'all' ? $total : (int) ($tabCounts[$key] ?? 0); $active = $filters['status'] === $key; @endphp
            <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}"
               @class(['flex min-h-11 shrink-0 items-center gap-2 rounded-full px-4 text-sm font-medium transition-colors', 'bg-fg text-ink-950' => $active, 'text-fg-muted hover:bg-white/[0.05] hover:text-fg' => ! $active])
               @if ($active) aria-current="page" @endif>
                {{ $label }} <span @class(['num rounded-full px-1.5 text-xs', 'bg-ink-950/10' => $active, 'bg-white/[0.07]' => ! $active])>{{ $n }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Filters --}}
    <form method="GET" class="card mt-4 p-4" x-data="{ more: @js($hasFilters) }">
        <input type="hidden" name="status" value="{{ $filters['status'] }}">
        <div class="flex flex-col gap-3 lg:flex-row">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute start-4 top-1/2 size-4 -translate-y-1/2 text-fg-subtle" />
                <label for="q" class="sr-only">{{ __('Search') }}</label>
                <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Search name, phone, email or #ID') }}" class="input ps-11">
            </div>
            <div class="flex gap-2">
                <label for="round" class="sr-only">{{ __('Round') }}</label>
                <select id="round" name="round" class="input lg:w-44" onchange="this.form.submit()">
                    <option value="all" @selected($filters['round'] === 'all')>{{ __('All rounds') }}</option>
                    @foreach ($rounds as $rd)<option value="{{ $rd->id }}" @selected((string) $filters['round'] === (string) $rd->id)>{{ $rd->displayName() }}</option>@endforeach
                </select>
                <button type="button" class="btn btn-outline shrink-0" @click="more = !more" :aria-expanded="more"><x-icon name="filter" class="size-4" /><span class="hidden sm:inline">{{ __('Filters') }}</span></button>
                <button type="submit" class="btn btn-primary shrink-0">{{ __('Search') }}</button>
            </div>
        </div>
        <div x-show="more" x-collapse @if (! $hasFilters) x-cloak @endif>
            <div class="mt-3 grid gap-3 border-t border-line pt-3 sm:grid-cols-3">
                <div>
                    <label for="leader" class="label">{{ __('Leader') }}</label>
                    <select id="leader" name="leader" class="input">
                        <option value="">{{ __('All leaders') }}</option>
                        @foreach ($leaders as $l)<option value="{{ $l->id }}" @selected((int) ($filters['leader'] ?? 0) === $l->id)>{{ $l->name }} ({{ $l->unique_code }})</option>@endforeach
                    </select>
                </div>
                <div><label for="from" class="label">{{ __('From date') }}</label><input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input"></div>
                <div><label for="to" class="label">{{ __('To date') }}</label><input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input"></div>
            </div>
            @if ($hasFilters)
                <a href="{{ route('admin.registrations.index', ['status' => $filters['status']]) }}" class="mt-3 inline-flex min-h-10 items-center text-sm text-fg-muted hover:text-fg"><x-icon name="x" class="size-4" />{{ __('Clear filters') }}</a>
            @endif
        </div>
    </form>

    <div class="mt-4">
        @if ($registrations->isEmpty())
            <div class="card"><x-empty icon="inbox" :title="$filters['status'] === 'pending' && ! $hasFilters ? __('All caught up') : __('No registrations found')" :text="$filters['status'] === 'pending' && ! $hasFilters ? __('There are no registrations waiting for review.') : __('Try a different search or filter.')" /></div>
        @else
            {{-- Desktop table --}}
            <div class="table-wrap hidden md:block">
                <table class="table">
                    <thead><tr>
                        <th scope="col" class="w-20">{{ __('Proof') }}</th>
                        <th scope="col">{{ __('Participant') }}</th>
                        <th scope="col">{{ __('Leader') }}</th>
                        <th scope="col">{{ __('Round') }}</th>
                        <th scope="col">{{ __('Submitted') }}</th>
                        <th scope="col" class="!text-end">{{ __('Decision') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($registrations as $r)
                            <tr>
                                <td>@include('admin.registrations.partials.proof-thumb', ['r' => $r])</td>
                                <td>
                                    <a href="{{ route('admin.registrations.show', $r) }}" class="font-semibold hover:text-brand-200">{{ $r->full_name }}</a>
                                    <div class="num mt-0.5 text-xs text-fg-subtle" dir="ltr"><span>#{{ $r->id }}</span> · {{ $r->phone }}@if ($r->email) · {{ $r->email }}@endif</div>
                                    @if ($r->team)
                                        <div class="mt-1"><span class="chip border-brand-500/25 bg-brand-500/[0.08] text-brand-300 text-[0.7rem] px-1.5 py-0.5 inline-flex items-center gap-1"><x-icon name="users" class="size-3" />{{ $r->team }}</span></div>
                                    @endif
                                </td>
                                <td><div class="font-medium">{{ $r->leader?->name }}</div><div class="num text-xs text-fg-subtle" dir="ltr">{{ $r->leader?->unique_code }}</div></td>
                                <td class="text-fg-muted">{{ $r->round?->displayName() }}</td>
                                <td class="text-fg-muted"><span title="{{ $r->created_at->toDayDateTimeString() }}">{{ $r->created_at->diffForHumans() }}</span>
                                    @if ($r->reviewer)<div class="text-xs text-fg-subtle">{{ __('by :name', ['name' => $r->reviewer->name]) }}</div>@endif</td>
                                <td>@include('admin.registrations.partials.review-buttons', ['r' => $r])</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{-- Mobile cards --}}
            <ul class="space-y-3 md:hidden">
                @foreach ($registrations as $r)
                    <li class="card p-4">
                        <a href="{{ route('admin.registrations.show', $r) }}" class="flex gap-3">
                            @include('admin.registrations.partials.proof-thumb', ['r' => $r, 'plain' => true])
                            <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-semibold">{{ $r->full_name }}</p>
                                <span class="num text-xs text-fg-subtle">#{{ $r->id }}</span>
                            </div>
                            <p class="num mt-1 text-sm text-fg-muted" dir="ltr">{{ $r->phone }}</p>
                            @if ($r->team)
                                <div class="mt-1"><span class="chip border-brand-500/25 bg-brand-500/[0.08] text-brand-300 text-[0.7rem] px-1.5 py-0.5 inline-flex items-center gap-1"><x-icon name="users" class="size-3" />{{ $r->team }}</span></div>
                            @endif
                            <p class="mt-1 text-xs text-fg-subtle">{{ $r->leader?->name }} · {{ $r->created_at->diffForHumans() }}</p>
                            </div>
                        </a>
                        <div class="mt-3 border-t border-line pt-3">@include('admin.registrations.partials.review-buttons', ['r' => $r])</div>
                    </li>
                @endforeach
            </ul>
            <div class="mt-5">{{ $registrations->links() }}</div>
        @endif
    </div>
</x-layouts.admin>
