<x-layouts.admin :heading="$round->displayName()" :subheading="$round->competition->tr('name')">
    <x-slot:breadcrumb><a href="{{ route('admin.competition.index') }}" class="hover:text-fg">{{ __('Rounds & timer') }}</a> / {{ $round->displayName() }}</x-slot:breadcrumb>
    <x-slot:actions>
        <span class="badge badge-{{ $round->status->value }} !px-3 !py-1.5">{{ $round->status->label() }}</span>
        @can('super-admin')
            <form method="POST" action="{{ route('admin.rounds.recalculate', $round) }}">@csrf<button class="btn btn-outline btn-sm" title="{{ __('Rebuild cached scores from accepted registrations') }}"><x-icon name="refresh" class="size-4" />{{ __('Recalculate scores') }}</button></form>
        @endcan
    </x-slot:actions>

    <dl class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach ([
            [__('Started'), $round->started_at?->translatedFormat('j M Y, H:i') ?? '—'],
            [__('Finished'), $round->finished_at?->translatedFormat('j M Y, H:i') ?? '—'],
            [__('Accepted'), (int) ($byStatus['accepted'] ?? 0)],
            [__('Pending'), (int) ($byStatus['pending'] ?? 0)],
            [__('Rejected'), (int) ($byStatus['rejected'] ?? 0)],
        ] as [$label, $value])
            <div class="card p-4"><dt class="text-xs text-fg-subtle">{{ $label }}</dt><dd class="num mt-1 text-lg font-bold">{{ $value }}</dd></div>
        @endforeach
    </dl>

    @if ($rows->isNotEmpty())
        <div class="table-wrap mt-5">
            <table class="table">
                <thead><tr><th class="w-16">{{ __('Rank') }}</th><th>{{ __('Leader') }}</th><th class="!text-end">{{ __('Accepted') }}</th><th class="!text-end">{{ __('Pending') }}</th><th class="!text-end">{{ __('Rejected') }}</th></tr></thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <td class="num font-bold {{ $row->rank <= 3 ? 'text-brand-300' : 'text-fg-subtle' }}">#{{ $row->rank }}</td>
                            <td class="font-semibold">{{ $row->name }} <span class="num ms-1 text-xs font-normal text-fg-subtle" dir="ltr">{{ $row->unique_code }}</span></td>
                            <td class="num text-end text-lg font-extrabold">{{ $row->accepted }}</td>
                            <td class="num text-end text-warning">{{ $row->pending }}</td>
                            <td class="num text-end text-fg-muted">{{ $row->rejected }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @can('viewAny', App\Models\Registration::class)
        <a href="{{ route('admin.registrations.index', ['round' => $round->id, 'status' => 'all']) }}" class="btn btn-outline mt-5">{{ __('View registrations in this round') }}</a>
    @endcan
</x-layouts.admin>
