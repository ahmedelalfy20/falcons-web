@php
    $s = (array) site('stats', [], false);
    $items = array_filter([
        ['value' => $s['active_members'] ?? null, 'suffix' => '+', 'label' => __('Active members')],
        ['value' => $s['students_trained'] ?? null, 'suffix' => '+', 'label' => __('Students trained')],
        ['value' => $s['countries'] ?? null, 'suffix' => '', 'label' => __('Countries')],
        ['value' => $s['satisfaction_rate'] ?? null, 'suffix' => '%', 'label' => __('Satisfaction rate')],
        ['value' => $s['years_experience'] ?? null, 'suffix' => '+', 'label' => __('Years of experience')],
    ], fn ($i) => filled($i['value']));
@endphp
@if ($items)
    <section aria-label="{{ __('Falcons in numbers') }}" class="border-y border-line bg-ink-900/60">
        <dl class="container-x grid grid-cols-2 sm:grid-cols-3 {{ [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4', 5 => 'lg:grid-cols-5'][count($items)] ?? 'lg:grid-cols-5' }}">
            @foreach ($items as $i => $item)
                <div @class([
                        'flex flex-col gap-1 px-2 py-7 sm:px-6 lg:py-9',
                        'lg:border-s lg:border-line' => $i > 0,
                        'col-span-2 sm:col-span-1' => $loop->last && count($items) % 2 === 1,
                    ]) data-reveal style="--reveal-delay: {{ $i * 60 }}ms">
                    <dt class="order-2 text-sm text-fg-muted">{{ $item['label'] }}</dt>
                    <dd class="order-1 num text-3xl font-extrabold tracking-tight sm:text-4xl" x-data="countUp({{ (int) $item['value'] }}, {{ 1600 + $i * 150 }})"><span dir="ltr" class="inline-block"><span x-text="display">{{ number_format((int) $item['value']) }}</span><span class="text-brand-400">{{ $item['suffix'] }}</span></span></dd>
                </div>
            @endforeach
        </dl>
    </section>
@endif
