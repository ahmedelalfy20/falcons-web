@php
    $social = array_filter((array) site('social', [], false));
    $icons = ['telegram' => 'telegram', 'facebook' => 'facebook', 'instagram' => 'instagram', 'tiktok' => 'tiktok', 'youtube' => 'youtube'];
    $home = request()->routeIs('home') ? '' : route('home');
    $stores = array_filter(['play' => site('app.play_url', null, false), 'appstore' => site('app.appstore_url', null, false)]);
@endphp
<footer class="border-t border-line bg-ink-950">
    <div class="container-x grid gap-12 py-14 sm:py-16 lg:grid-cols-12">
        <div class="lg:col-span-5">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                <img src="{{ asset('media/brand/logo.webp') }}" alt="" width="44" height="43" loading="lazy" class="size-11 rounded-full">
                <span class="text-lg font-extrabold tracking-tight">Falcons <span class="font-medium text-fg-muted">{{ __('Organization') }}</span></span>
            </a>
            <p class="mt-5 max-w-sm text-sm leading-relaxed text-fg-muted">{{ __('Building traders, building leaders. Elite trading education, mentorship and a community that grows together.') }}</p>
            <div class="mt-6 flex flex-wrap items-center gap-2">
                <a href="{{ whatsapp_url() }}" target="_blank" rel="noopener" class="flex size-11 items-center justify-center rounded-full border border-line text-fg-muted transition-colors hover:border-brand-500/60 hover:text-brand-300" aria-label="WhatsApp">
                    <x-icon name="whatsapp" class="size-5" />
                </a>
                @foreach ($social as $network => $url)
                    @if (isset($icons[$network]))
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="flex size-11 items-center justify-center rounded-full border border-line text-fg-muted transition-colors hover:border-brand-500/60 hover:text-brand-300" aria-label="{{ ucfirst($network) }}">
                            <x-icon :name="$icons[$network]" class="size-5" />
                        </a>
                    @endif
                @endforeach
            </div>
            @if ($stores)
                <p class="mt-7 text-xs font-semibold uppercase tracking-[0.16em] text-fg-subtle rtl:tracking-normal">{{ __('Download the app') }}</p>
                <div class="mt-3 flex flex-wrap gap-2.5">
                    @foreach ($stores as $key => $url)
                        <x-store-badge :store="$key" :url="$url" size="sm" />
                    @endforeach
                </div>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-8 sm:grid-cols-3 lg:col-span-7">
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-[0.16em] text-fg-subtle">{{ __('Academy') }}</h2>
                <ul class="mt-4 space-y-1">
                    <li><a class="inline-flex min-h-10 items-center text-sm text-fg-muted hover:text-fg" href="{{ $home }}#founder">{{ __('The Founder') }}</a></li>
                    <li><a class="inline-flex min-h-10 items-center text-sm text-fg-muted hover:text-fg" href="{{ $home }}#about">{{ __('About') }}</a></li>
                    <li><a class="inline-flex min-h-10 items-center text-sm text-fg-muted hover:text-fg" href="{{ $home }}#leaders">{{ __('Leaders') }}</a></li>
                </ul>
            </div>
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-[0.16em] text-fg-subtle">{{ __('Learn') }}</h2>
                <ul class="mt-4 space-y-1">
                    <li><a class="inline-flex min-h-10 items-center text-sm text-fg-muted hover:text-fg" href="{{ $home }}#courses">{{ __('Courses') }}</a></li>
                    <li><a class="inline-flex min-h-10 items-center text-sm text-fg-muted hover:text-fg" href="{{ $home }}#scanners">{{ __('Scanners') }}</a></li>
                    <li><a class="inline-flex min-h-10 items-center text-sm text-fg-muted hover:text-fg" href="{{ route('free-courses') }}">{{ __('Free Courses') }}</a></li>
                </ul>
            </div>
            <div class="col-span-2 sm:col-span-1">
                <h2 class="text-xs font-semibold uppercase tracking-[0.16em] text-fg-subtle">{{ __('Competition') }}</h2>
                <ul class="mt-4 space-y-1">
                    <li><a class="inline-flex min-h-10 items-center text-sm text-fg-muted hover:text-fg" href="{{ route('competition') }}">{{ __('Live leaderboard') }}</a></li>
                    <li><a class="inline-flex min-h-10 items-center text-sm text-fg-muted hover:text-fg" href="{{ route('leader.signup') }}">{{ __('Become a leader') }}</a></li>
                    <li><a class="inline-flex min-h-10 items-center text-sm text-fg-muted hover:text-fg" href="{{ route('login') }}">{{ __('Leader & admin login') }}</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="border-t border-line">
        <div class="container-x flex flex-col gap-3 py-6 text-xs text-fg-subtle sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} {{ __('Falcons Organization. All rights reserved.') }}</p>
            <p>{{ __('Trading involves risk. Educational content only — not financial advice.') }}</p>
        </div>
    </div>
</footer>
