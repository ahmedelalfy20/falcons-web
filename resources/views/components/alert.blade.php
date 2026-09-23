@props(['level' => 'info'])
@php
    $styles = [
        'info' => 'border-info/25 bg-info/[0.07] text-blue-100',
        'warning' => 'border-warning/25 bg-warning/[0.07] text-amber-100',
        'danger' => 'border-danger/25 bg-danger/[0.08] text-red-100',
        'success' => 'border-success/25 bg-success/[0.07] text-emerald-100',
    ];
    $icons = ['info' => 'info', 'warning' => 'alert', 'danger' => 'alert', 'success' => 'check-circle'];
@endphp
<div role="{{ $level === 'danger' ? 'alert' : 'status' }}" {{ $attributes->class(['flex items-start gap-3 rounded-xl border px-4 py-3 text-sm leading-relaxed', $styles[$level] ?? $styles['info']]) }}>
    <x-icon :name="$icons[$level] ?? 'info'" class="mt-0.5 size-4 shrink-0" />
    <div class="min-w-0 flex-1">{{ $slot }}</div>
</div>
