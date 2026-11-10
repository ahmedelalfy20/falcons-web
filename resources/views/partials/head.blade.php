<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#080a09">
<script>
    // Apply the saved theme before first paint (no flash). Default: dark.
    (function () {
        try {
            var t = localStorage.getItem('theme');
            if (t === 'light') {
                document.documentElement.setAttribute('data-theme', 'light');
                document.querySelector('meta[name="theme-color"]').setAttribute('content', '#f7f6f2');
            }
        } catch (e) {}
    })();
</script>
<title>{{ isset($title) && $title ? $title.' · ' : '' }}{{ __('Falcons Academy') }}</title>
<meta name="description" content="{{ $description ?? __('Falcons Academy — elite trading education, mentorship and a community of professional traders across the MENA region.') }}">
<meta property="og:title" content="{{ $title ?? __('Falcons Academy') }}">
<meta property="og:description" content="{{ $description ?? __('Elite trading education & mentorship') }}">
<meta property="og:type" content="website">
<meta property="og:image" content="{{ asset('media/founder-original.webp') }}">
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<link rel="preload" href="{{ asset('fonts/manrope-var.woff2') }}" as="font" type="font/woff2" crossorigin>
@if (app()->getLocale() === 'ar')
    <link rel="preload" href="{{ asset('fonts/plex-arabic-700.woff2') }}" as="font" type="font/woff2" crossorigin>
@endif
@vite(['resources/css/app.css', 'resources/js/app.js'])
