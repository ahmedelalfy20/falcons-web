{{-- Light / dark switch. The choice is saved in this browser; see the inline script in partials/head. --}}
<button type="button" x-data="themeToggle" @click="toggle()"
        {{ $attributes->class(['btn btn-ghost btn-sm !px-2.5']) }}
        :aria-pressed="light.toString()"
        :aria-label="light ? @js(__('Switch to dark mode')) : @js(__('Switch to light mode'))"
        :title="light ? @js(__('Dark mode')) : @js(__('Light mode'))"
        aria-label="{{ __('Switch to light mode') }}">
    <x-icon name="sun" class="size-[1.15rem]" x-show="!light" />
    <x-icon name="moon" class="size-[1.15rem]" x-show="light" x-cloak />
    @if ($slot->isNotEmpty())<span>{{ $slot }}</span>@endif
</button>
