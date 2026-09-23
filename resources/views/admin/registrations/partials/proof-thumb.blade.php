{{-- Transfer screenshot thumbnail (private file served through an authorised route). --}}
@if ($r->transfer_path)
    @if ($plain ?? false)
        <img src="{{ route('admin.registrations.transfer', ['registration' => $r, 'thumb' => 1]) }}" alt="{{ __('Transfer screenshot') }}" loading="lazy" class="h-16 w-12 shrink-0 rounded-lg bg-ink-800 object-cover">
    @else
        <a href="{{ route('admin.registrations.transfer', $r) }}" target="_blank" rel="noopener" class="block w-fit" title="{{ __('Open full size') }}">
            <img src="{{ route('admin.registrations.transfer', ['registration' => $r, 'thumb' => 1]) }}" alt="{{ __('Transfer screenshot') }}" loading="lazy" class="h-14 w-11 rounded-lg bg-ink-800 object-cover ring-1 ring-line transition hover:ring-brand-400">
        </a>
    @endif
@else
    <span class="flex h-14 w-11 items-center justify-center rounded-lg border border-dashed border-line-strong text-fg-subtle" title="{{ __('No screenshot uploaded') }}"><x-icon name="image" class="size-4" /></span>
@endif
