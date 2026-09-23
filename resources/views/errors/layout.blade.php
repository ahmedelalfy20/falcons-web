{{-- Standalone error layout: must not depend on session/auth (404s can render outside the web middleware). --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $code }} · {{ __('Falcons Academy') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-dvh">
    <main class="relative flex min-h-dvh items-center justify-center px-4 py-16">
        <div class="bg-glow pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="relative max-w-md text-center">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2.5"><img src="{{ asset('media/brand/logo.webp') }}" alt="" width="40" height="39" class="size-10 rounded-full"><span class="font-extrabold">Falcons</span></a>
            <p class="num mt-10 text-7xl font-extrabold tracking-tight text-fg-subtle/60 sm:text-8xl">{{ $code }}</p>
            <h1 class="mt-4 text-2xl font-bold sm:text-3xl">{{ $title }}</h1>
            <p class="mt-3 leading-relaxed text-fg-muted">{{ $message }}</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ url('/') }}" class="btn btn-primary">{{ __('Back to home') }}</a>
                @isset($retry)<a href="{{ url()->current() }}" class="btn btn-outline">{{ __('Try again') }}</a>@endisset
            </div>
        </div>
    </main>
</body>
</html>
