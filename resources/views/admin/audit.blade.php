<x-layouts.admin :heading="__('Audit log')" :subheading="__('Append-only record of every sensitive action. Entries cannot be edited or deleted.')">
    <form method="GET" class="card grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <label for="q" class="sr-only">{{ __('Search') }}</label>
            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Actor name or entity #ID') }}" class="input">
        </div>
        <div>
            <label for="action" class="sr-only">{{ __('Action') }}</label>
            <select id="action" name="action" class="input">
                <option value="">{{ __('All actions') }}</option>
                @foreach ($actions as $a)<option value="{{ $a }}" @selected(($filters['action'] ?? '') === $a)>{{ __(ucfirst($a)) }}</option>@endforeach
            </select>
        </div>
        <div class="flex gap-2 lg:col-span-2">
            <label class="sr-only" for="from">{{ __('From date') }}</label><input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="input">
            <label class="sr-only" for="to">{{ __('To date') }}</label><input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="input">
            <button class="btn btn-primary shrink-0">{{ __('Filter') }}</button>
        </div>
    </form>

    <div class="card mt-4">
        @forelse ($logs as $log)
            <details class="group border-b border-line last:border-0">
                <summary class="flex min-h-14 cursor-pointer list-none items-start gap-3 px-5 py-3.5 hover:bg-white/[0.02]">
                    @php $dot = match (true) { str_contains($log->action, 'accepted') => 'bg-success', str_contains($log->action, 'rejected') || str_contains($log->action, 'deleted') => 'bg-danger', str_starts_with($log->action, 'round') => 'bg-info', default => 'bg-fg-subtle' }; @endphp
                    <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $dot }}"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm">{{ $log->sentence() }}</span>
                        <span class="mt-0.5 block text-xs text-fg-subtle"><span class="num">{{ $log->created_at->translatedFormat('j M Y, H:i:s') }}</span> · <code class="text-fg-subtle">{{ $log->action }}</code>@if ($log->ip_address) · <span dir="ltr">{{ $log->ip_address }}</span>@endif</span>
                    </span>
                    @if ($log->metadata)<x-icon name="chevron-down" class="mt-1 size-4 shrink-0 text-fg-subtle transition-transform group-open:rotate-180" />@endif
                </summary>
                @if ($log->metadata)
                    <pre class="mx-5 mb-4 overflow-x-auto rounded-xl bg-ink-900 p-4 text-xs leading-relaxed text-fg-muted" dir="ltr">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                @endif
            </details>
        @empty
            <x-empty icon="history" :title="__('No entries found')" />
        @endforelse
    </div>
    <div class="mt-5">{{ $logs->links() }}</div>
</x-layouts.admin>
