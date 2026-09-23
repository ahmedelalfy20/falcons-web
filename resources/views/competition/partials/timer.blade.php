{{-- Server-authoritative countdown. Pass $round. Big variant for hero areas. --}}
@php $payload = \App\Support\RoundPayload::make($round); $big = $big ?? false; @endphp
<div x-data="countdown(@js($payload))">
    <div class="flex items-center gap-2 text-sm text-fg-muted">
        <span class="badge" :class="'badge-' + status" x-text="{running: @js(__('Running')), paused: @js(__('Paused')), finished: @js(__('Finished')), ready: @js(__('Ready')), draft: @js(__('Draft'))}[status]">{{ $round->status->label() }}</span>
        <span x-show="status === 'running'">{{ __('Time remaining') }}</span>
        <span x-show="status === 'paused'" x-cloak>{{ __('Timer paused') }}</span>
        <span x-show="status === 'ready' || status === 'draft'" x-cloak>{{ __('Planned duration') }}</span>
    </div>
    <p @class(['num mt-3 font-extrabold tracking-tight', 'text-5xl sm:text-6xl lg:text-7xl' => $big, 'text-4xl sm:text-5xl' => ! $big])
       :class="status === 'paused' && 'text-warning'" aria-live="off">
        <span x-text="clock.text">{{ format_duration($round->remainingSeconds()) }}</span>
    </p>
</div>
