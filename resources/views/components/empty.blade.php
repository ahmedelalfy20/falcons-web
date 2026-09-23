@props(['icon' => 'inbox', 'title', 'text' => null])
<div {{ $attributes->class(['flex flex-col items-center justify-center px-6 py-14 text-center']) }}>
    <span class="flex size-12 items-center justify-center rounded-2xl bg-white/[0.04] text-fg-subtle"><x-icon :name="$icon" class="size-6" /></span>
    <p class="mt-4 font-semibold">{{ $title }}</p>
    @if ($text)<p class="mt-1.5 max-w-sm text-sm leading-relaxed text-fg-muted">{{ $text }}</p>@endif
    @if (trim($slot))<div class="mt-5">{{ $slot }}</div>@endif
</div>
