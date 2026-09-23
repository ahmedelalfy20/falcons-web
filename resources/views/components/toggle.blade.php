@props(['name', 'label', 'checked' => false, 'hint' => null])
@php $id = 't-'.str_replace(['.', '[', ']'], '-', $name); $on = (bool) old($name, $checked); @endphp
<label for="{{ $id }}" {{ $attributes->class(['flex min-h-12 cursor-pointer items-start gap-3 rounded-xl border border-line bg-ink-900 px-4 py-3 transition-colors hover:border-line-strong']) }}>
    <input type="hidden" name="{{ $name }}" value="0">
    <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" @checked($on) class="peer sr-only">
    <span class="relative mt-0.5 h-6 w-10 shrink-0 rounded-full bg-ink-700 transition-colors peer-checked:bg-brand-500 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400 peer-focus-visible:ring-offset-2 peer-focus-visible:ring-offset-ink-900
                 after:absolute after:start-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:transition-transform peer-checked:after:translate-x-4 rtl:peer-checked:after:-translate-x-4" aria-hidden="true"></span>
    <span class="min-w-0">
        <span class="block text-sm font-medium text-fg">{{ $label }}</span>
        @if ($hint)<span class="mt-0.5 block text-xs leading-relaxed text-fg-subtle">{{ $hint }}</span>@endif
    </span>
</label>
