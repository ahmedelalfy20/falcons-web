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
@php
    $seoBrand = \App\Support\Seo::brand();
    $seoTitle = isset($title) && $title ? $title.' · '.$seoBrand : $seoBrand.' — '.__('Professional Trading Education & Mentorship');
    $seoDesc = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) ($description ?? '')))) ?: __('Falcons Academy — elite trading education, mentorship and a community of professional traders across the MENA region.'), 158, '…');
    $seoImage = \App\Support\Seo::image($image ?? null);
    $seoLocale = app()->getLocale();
    $seoNoindex = $noindex ?? false;
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDesc }}">
@if ($seoNoindex)
    <meta name="robots" content="noindex, follow">
@else
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">
    <link rel="canonical" href="{{ \App\Support\Seo::url($seoLocale) }}">
    @foreach (array_keys(\App\Support\Seo::LOCALES) as $alt)
        <link rel="alternate" hreflang="{{ $alt }}" href="{{ \App\Support\Seo::url($alt) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ \App\Support\Seo::url() }}">
@endif
<meta property="og:site_name" content="{{ $seoBrand }}">
<meta property="og:type" content="{{ $type ?? 'website' }}">
<meta property="og:title" content="{{ $title ?? $seoTitle }}">
<meta property="og:description" content="{{ $seoDesc }}">
<meta property="og:url" content="{{ \App\Support\Seo::url($seoLocale) }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:image:alt" content="{{ $title ?? $seoBrand }}">
<meta property="og:locale" content="{{ \App\Support\Seo::LOCALES[$seoLocale] ?? 'en_US' }}">
@foreach (\App\Support\Seo::LOCALES as $code => $ogLocale)
    @if ($code !== $seoLocale)<meta property="og:locale:alternate" content="{{ $ogLocale }}">@endif
@endforeach
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title ?? $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDesc }}">
<meta name="twitter:image" content="{{ $seoImage }}">
<meta name="application-name" content="{{ $seoBrand }}">
<meta name="format-detection" content="telephone=no">
@unless ($seoNoindex)
    <script type="application/ld+json">{!! \App\Support\Seo::jsonLd($schema ?? []) !!}</script>
@endunless
<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<link rel="preload" href="{{ asset('fonts/manrope-var.woff2') }}" as="font" type="font/woff2" crossorigin>
@if (app()->getLocale() === 'ar')
    <link rel="preload" href="{{ asset('fonts/plex-arabic-700.woff2') }}" as="font" type="font/woff2" crossorigin>
@endif
@vite(['resources/css/app.css', 'resources/js/app.js'])
