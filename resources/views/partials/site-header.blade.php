@php
    $home = request()->routeIs('home');
    $links = [
        ['label' => __('About'), 'href' => ($home ? '' : route('home')).'#about'],
        ['label' => __('Scanners'), 'href' => ($home ? '' : route('home')).'#scanners'],
        ['label' => __('Leaders'), 'href' => ($home ? '' : route('home')).'#leaders'],
        ['label' => __('Courses'), 'href' => ($home ? '' : route('home')).'#courses'],
        ['label' => __('Competition'), 'href' => route('competition'), 'active' => request()->routeIs('competition*', 'register*', 'registration.*')],
        ['label' => __('Free Courses'), 'href' => route('free-courses'), 'active' => request()->routeIs('free-courses*')],
    ];
    $otherLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
@endphp
<header x-data="{ open: false, scrolled: false }"
        x-init="scrolled = window.scrollY > 8; window.addEventListener('scroll', () => scrolled = window.scrollY > 8, { passive: true })"
        @keydown.escape.window="open = false"
        class="fixed inset-x-0 top-0 z-50 transition-[background-color,border-color] duration-300"
        :class="scrolled || open ? 'bg-ink-950/90 backdrop-blur-xl border-b border-line' : 'bg-ink-950 border-b border-white/[0.04]'">
    <div class="container-x flex h-16 items-center justify-between gap-4 lg:h-[4.5rem]">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5" aria-label="{{ __('Falcons Academy — Home') }}">
            <img src="{{ asset('media/brand/logo.webp') }}" alt="" width="36" height="35" class="size-9 rounded-full">
            <span class="flex flex-col leading-none">
                <span class="text-[0.95rem] font-extrabold tracking-tight">Falcons</span>
                <span class="mt-0.5 text-[0.6rem] font-semibold uppercase tracking-[0.22em] text-fg-subtle">{{ __('Organization') }}</span>
            </span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('Main') }}">
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}"
                   @class(['rounded-full px-3.5 py-2 text-sm font-medium transition-colors', 'text-fg bg-white/[0.06]' => $link['active'] ?? false, 'text-fg-muted hover:text-fg' => ! ($link['active'] ?? false)])
                   @if ($link['active'] ?? false) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ route('locale', $otherLocale) }}" class="btn btn-ghost btn-sm !px-3" hreflang="{{ $otherLocale }}" lang="{{ $otherLocale }}">
                <x-icon name="globe" class="size-4" /> {{ $otherLocale === 'ar' ? 'العربية' : 'English' }}
            </a>
            <a href="{{ join_url() }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm hidden sm:inline-flex"><x-icon name="telegram" class="size-4" />{{ __('Join us') }}</a>
            <button type="button" class="btn btn-ghost !min-h-11 !px-2.5 lg:hidden" @click="open = !open" :aria-expanded="open" aria-controls="mobile-menu" aria-label="{{ __('Menu') }}">
                <x-icon name="menu" class="size-6" x-show="!open" />
                <x-icon name="x" class="size-6" x-show="open" x-cloak />
            </button>
        </div>
    </div>

    {{-- Mobile menu: full-height sheet with large touch targets --}}
    <div id="mobile-menu" x-show="open" x-cloak
         x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="opacity-0"
         class="border-t border-line bg-ink-950 lg:hidden" style="height: calc(100dvh - 4rem)">
        <nav class="container-x flex h-full flex-col overflow-y-auto py-6" aria-label="{{ __('Mobile') }}">
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}" @click="open = false" class="flex min-h-14 items-center justify-between border-b border-line text-lg font-semibold">
                    {{ $link['label'] }} <x-icon name="arrow-right" class="size-5 text-fg-subtle" />
                </a>
            @endforeach
            <div class="mt-auto grid gap-3 pt-8">
                @guest
                    <a href="{{ route('login') }}" class="btn btn-outline btn-lg">{{ __('Log in') }}</a>
                @endguest
                <a href="{{ join_url() }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg"><x-icon name="telegram" class="size-5" />{{ __('Join us') }}</a>
            </div>
        </nav>
    </div>
</header>
