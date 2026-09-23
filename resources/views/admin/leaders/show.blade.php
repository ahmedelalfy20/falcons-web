<x-layouts.admin :heading="$leader->name" :subheading="__('Joined :date', ['date' => $leader->created_at->translatedFormat('j M Y')])">
    <x-slot:breadcrumb><a href="{{ route('admin.leaders.index') }}" class="hover:text-fg">{{ __('Leaders') }}</a> / {{ $leader->name }}</x-slot:breadcrumb>
    <x-slot:actions>
        <span class="badge {{ $leader->statusBadge() }} !px-3 !py-1.5">{{ $leader->statusLabel() }}</span>
        @can('update', $leader)<a href="{{ route('admin.leaders.edit', $leader) }}" class="btn btn-outline"><x-icon name="edit" class="size-4" />{{ __('Edit') }}</a>@endcan
    </x-slot:actions>

    @if (in_array($leader->status, ['pending', 'rejected'], true))
        <div class="card mb-5 flex flex-col gap-4 border-warning/30 bg-warning/[0.05] p-5 sm:flex-row sm:items-center">
            <x-icon name="clock" class="size-6 shrink-0 text-warning" />
            <div class="flex-1">
                <p class="font-semibold">{{ $leader->isPending() ? __('This leader is waiting for approval') : __('This leader request was rejected') }}</p>
                <p class="mt-1 text-sm text-fg-muted">{{ __('Their QR code does not accept registrations and they are hidden from the live leaderboard until approved.') }}</p>
            </div>
            @can('review', $leader)
                <div class="flex gap-2" x-data="{ busy: false }">
                    @if ($leader->isPending())
                        <form method="POST" action="{{ route('admin.leaders.reject', $leader) }}" @submit="busy = true">@csrf<button class="btn btn-outline" :disabled="busy" onclick="return confirm(@js(__('Reject the request from :name?', ['name' => $leader->name])))"><x-icon name="x" class="size-4" />{{ __('Reject') }}</button></form>
                    @endif
                    <form method="POST" action="{{ route('admin.leaders.approve', $leader) }}" @submit="busy = true">@csrf<button class="btn btn-success" :disabled="busy"><x-icon name="check" class="size-4" />{{ __('Approve') }}</button></form>
                </div>
            @endcan
        </div>
    @endif

    <div class="grid gap-5 lg:grid-cols-12">
        <section class="card card-pad lg:col-span-4" x-data="copyable(@js($leader->referralUrl()))" aria-labelledby="qr-title">
            <div class="mb-5 flex items-center gap-4 border-b border-line pb-5">
                @if ($leader->photo)
                    <a href="{{ $leader->photoUrl(false) }}" target="_blank" rel="noopener"><x-leader-avatar :leader="$leader" full class="size-20 text-xl" /></a>
                @else
                    <x-leader-avatar :leader="$leader" class="size-20 text-xl" />
                @endif
                <div class="min-w-0">
                    <p class="truncate text-lg font-bold">{{ $leader->name }}</p>
                    @if ($leader->approved_at)
                        <p class="text-xs text-fg-subtle">{{ __('Approved :date', ['date' => $leader->approved_at->translatedFormat('j M Y')]) }}@if ($leader->approver) · {{ $leader->approver->name }}@endif</p>
                    @endif
                </div>
            </div>
            <h2 id="qr-title" class="font-bold">{{ __('Referral QR') }}</h2>
            <div class="mx-auto mt-4 w-fit rounded-2xl bg-white p-3"><img src="{{ route('admin.leaders.qr', $leader) }}" alt="{{ __('QR code for :name', ['name' => $leader->name]) }}" width="200" height="200" class="size-48"></div>
            <p class="num mt-4 text-center text-xl font-extrabold tracking-[0.08em]" dir="ltr">{{ $leader->unique_code }}</p>
            <button type="button" class="btn btn-outline btn-sm mt-3 w-full" @click="copy()"><x-icon name="copy" class="size-4" /><span x-text="copied ? @js(__('Copied!')) : @js(__('Copy referral link'))"></span></button>
            <dl class="mt-5 space-y-3 border-t border-line pt-4 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-fg-subtle">{{ __('Phone') }}</dt><dd dir="ltr">{{ $leader->phone }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-fg-subtle">{{ __('Email') }}</dt><dd class="truncate" dir="ltr">{{ $leader->email ?: '—' }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-fg-subtle">{{ __('Login account') }}</dt><dd>{{ $leader->user ? __('Yes') : __('No') }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-fg-subtle">{{ __('Leader ID') }}</dt><dd class="num">{{ $leader->id }}</dd></div>
            </dl>
        </section>

        <div class="space-y-5 lg:col-span-8">
            <section class="card" aria-labelledby="rounds-title">
                <h2 id="rounds-title" class="border-b border-line px-5 py-4 font-bold">{{ __('Results by round') }}</h2>
                <div class="overflow-x-auto">
                    <table class="table !min-w-[520px]">
                        <thead><tr><th>{{ __('Round') }}</th><th class="!text-end">{{ __('Rank') }}</th><th class="!text-end">{{ __('Accepted') }}</th><th class="!text-end">{{ __('Pending') }}</th><th class="!text-end">{{ __('Rejected') }}</th></tr></thead>
                        <tbody>
                            @forelse ($perRound->reverse() as $p)
                                <tr>
                                    <td><span class="font-medium">{{ $p['round']->displayName() }}</span> <span class="badge badge-{{ $p['round']->status->value }} ms-1">{{ $p['round']->status->label() }}</span></td>
                                    <td class="num text-end">#{{ $p['row']?->rank ?? '—' }}</td>
                                    <td class="num text-end font-bold">{{ (int) ($p['row']?->accepted ?? 0) }}</td>
                                    <td class="num text-end text-warning">{{ (int) ($p['row']?->pending ?? 0) }}</td>
                                    <td class="num text-end text-fg-muted">{{ (int) ($p['row']?->rejected ?? 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5"><x-empty icon="timer" :title="__('No rounds yet')" /></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card" aria-labelledby="leader-recent">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 id="leader-recent" class="font-bold">{{ __('Latest registrations') }}</h2>
                    @can('viewAny', App\Models\Registration::class)
                        <a href="{{ route('admin.registrations.index', ['leader' => $leader->id, 'status' => 'all', 'round' => 'all']) }}" class="text-sm font-medium text-brand-300 hover:text-brand-200">{{ __('View all') }}</a>
                    @endcan
                </div>
                @forelse ($recent as $r)
                    <a href="{{ route('admin.registrations.show', $r) }}" class="flex items-center justify-between gap-3 border-b border-line px-5 py-3 last:border-0 hover:bg-white/[0.02]">
                        <span class="min-w-0"><span class="block truncate font-medium">{{ $r->full_name }}</span><span class="block text-xs text-fg-subtle">#{{ $r->id }} · {{ $r->round?->displayName() }} · {{ $r->created_at->diffForHumans() }}</span></span>
                        <span class="badge badge-{{ $r->status->value }}">{{ $r->status->label() }}</span>
                    </a>
                @empty
                    <x-empty icon="inbox" :title="__('No registrations yet')" />
                @endforelse
            </section>

            @can('delete', $leader)
                <section class="rounded-[var(--radius-card)] border border-danger/25 p-5" aria-labelledby="danger">
                    <h2 id="danger" class="font-bold text-danger">{{ __('Delete leader') }}</h2>
                    <p class="mt-1 text-sm text-fg-muted">{{ __('Only leaders without registrations can be deleted. Suspend the leader instead to keep results intact.') }}</p>
                    <form method="POST" action="{{ route('admin.leaders.destroy', $leader) }}" class="mt-4" onsubmit="return confirm(@js(__('Delete this leader permanently?')))">
                        @csrf @method('DELETE')
                        <button class="btn btn-danger btn-sm" @disabled($recent->isNotEmpty())><x-icon name="trash" class="size-4" />{{ __('Delete') }}</button>
                    </form>
                </section>
            @endcan
        </div>
    </div>
</x-layouts.admin>
