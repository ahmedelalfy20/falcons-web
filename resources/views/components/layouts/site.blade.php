@props(['title' => null, 'description' => null])
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @include('partials.head', ['title' => $title ?? null, 'description' => $description ?? null])
    @stack('head')
</head>
<body class="min-h-dvh">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[200] focus:rounded-full focus:bg-fg focus:px-4 focus:py-2 focus:text-ink-950">{{ __('Skip to content') }}</a>
    @include('partials.site-header')
    <main id="main" tabindex="-1" class="outline-none">
        {{ $slot }}
    </main>
    @include('partials.site-footer')
    @include('partials.back-button')
    @include('partials.flash')
    @stack('scripts')
</body>
</html>
