@props(['title' => null, 'heading' => null, 'subheading' => null])
@php
    use App\Enums\Permission;
    $u = auth()->user();
    $pendingCount = cache()->remember('admin.pending_badge.'.\App\Support\LiveVersion::get('admin'), 30, function () {
        $c = \App\Models\Competition::active();
        return $c ? \App\Models\Registration::where('competition_id', $c->id)->where('status', 'pending')->count() : 0;
    });
    $pendingLeaders = cache()->remember('admin.pending_leaders.'.\App\Support\LiveVersion::get('admin'), 30, function () {
        $c = \App\Models\Competition::active();
        return $c ? \App\Models\Leader::where('competition_id', $c->id)->where('status', 'pending')->count() : 0;
    });
    $groups = [
        __('Competition') => [
            ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'icon' => 'dashboard', 'label' => __('Overview'), 'show' => true],
            ['route' => 'admin.registrations.index', 'match' => 'admin.registrations.*', 'icon' => 'inbox', 'label' => __('Registrations'), 'show' => $u->hasPermission(Permission::RegistrationsView), 'badge' => $pendingCount],
            ['route' => 'admin.leaders.index', 'match' => 'admin.leaders.*', 'icon' => 'users', 'label' => __('Leaders'), 'show' => $u->hasPermission(Permission::LeadersView), 'badge' => $pendingLeaders],
            ['route' => 'admin.leaderboard', 'match' => 'admin.leaderboard', 'icon' => 'trophy', 'label' => __('Leaderboard'), 'show' => $u->hasPermission(Permission::LeaderboardView)],
            ['route' => 'admin.competition.index', 'match' => ['admin.competition*', 'admin.rounds.*'], 'icon' => 'timer', 'label' => __('Rounds & timer'), 'show' => true],
        ],
        __('Website') => [
            ['route' => 'admin.scanners.index', 'match' => 'admin.scanners.*', 'icon' => 'scan', 'label' => __('Scanners'), 'show' => $u->hasPermission(Permission::ContentManage)],
            ['route' => 'admin.team.index', 'match' => 'admin.team.*', 'icon' => 'user', 'label' => __('Team profiles'), 'show' => $u->hasPermission(Permission::ContentManage)],
            ['route' => 'admin.companies.index', 'match' => 'admin.companies.*', 'icon' => 'building', 'label' => __('Companies'), 'show' => $u->hasPermission(Permission::ContentManage)],
            ['route' => 'admin.courses.index', 'match' => 'admin.courses.*', 'icon' => 'graduation', 'label' => __('Courses'), 'show' => $u->hasPermission(Permission::ContentManage)],
            ['route' => 'admin.gallery.index', 'match' => 'admin.gallery.*', 'icon' => 'image', 'label' => __('Gallery'), 'show' => $u->hasPermission(Permission::ContentManage)],
        ],
        __('System') => [
            ['route' => 'admin.audit.index', 'match' => 'admin.audit.*', 'icon' => 'history', 'label' => __('Audit log'), 'show' => $u->can('view-audit-logs')],
            ['route' => 'admin.users.index', 'match' => 'admin.users.*', 'icon' => 'shield', 'label' => __('Admins'), 'show' => $u->can('manage-admins')],
            ['route' => 'admin.settings.edit', 'match' => 'admin.settings.*', 'icon' => 'settings', 'label' => __('Settings'), 'show' => $u->can('manage-settings')],
        ],
    ];
    $otherLocale = app()->getLocale() === 'ar' ? 'en' : 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    @include('partials.head', ['title' => ($title ?? $heading) ? ($title ?? $heading).' · '.__('Admin') : __('Admin'), 'description' => null])
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="min-h-dvh bg-ink-950" x-data="{ nav: false }" @keydown.escape.window="nav = false">
    <a href="#admin-main" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-[200] focus:rounded-full focus:bg-fg focus:px-4 focus:py-2 focus:text-ink-950">{{ __('Skip to content') }}</a>

    {{-- Sidebar (drawer on mobile) --}}
    <div x-show="nav" x-cloak x-transition.opacity class="fixed inset-0 z-40 bg-ink-950/70 backdrop-blur-sm lg:hidden" @click="nav = false"></div>
    <aside class="fixed inset-y-0 start-0 z-50 flex w-72 -translate-x-full flex-col border-e border-line bg-ink-900 transition-transform duration-300 ease-[var(--ease-out-soft)] rtl:translate-x-full lg:translate-x-0 rtl:lg:translate-x-0"
           :class="nav && '!translate-x-0'" aria-label="{{ __('Admin navigation') }}">
        <div class="flex h-16 items-center justify-between gap-3 border-b border-line px-5">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('media/brand/logo.webp') }}" alt="" width="32" height="31" class="size-8 rounded-full">
                <span class="leading-none"><span class="block text-sm font-extrabold">Falcons</span><span class="mt-0.5 block text-[0.65rem] font-semibold uppercase tracking-[0.18em] text-fg-subtle">{{ __('Control room') }}</span></span>
            </a>
            <button type="button" class="btn btn-ghost !min-h-10 !px-2 lg:hidden" @click="nav = false" aria-label="{{ __('Close menu') }}"><x-icon name="x" /></button>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 py-5">
            @foreach ($groups as $group => $items)
                @php $visible = array_filter($items, fn ($i) => $i['show']); @endphp
                @continue(! $visible)
                <p class="mb-2 px-3 text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-fg-subtle rtl:tracking-normal @if (! $loop->first) mt-6 @endif">{{ $group }}</p>
                <ul class="space-y-0.5">
                    @foreach ($visible as $item)
                        @php $active = request()->routeIs(...(array) $item['match']); @endphp
                        <li>
                            <a href="{{ route($item['route']) }}" @class(['flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium transition-colors', 'bg-white/[0.07] text-fg' => $active, 'text-fg-muted hover:bg-white/[0.04] hover:text-fg' => ! $active]) @if ($active) aria-current="page" @endif>
                                <x-icon :name="$item['icon']" @class(['size-[1.15rem]', 'text-brand-400' => $active]) />
                                <span class="flex-1">{{ $item['label'] }}</span>
                                @if (! empty($item['badge']))<span class="num rounded-full bg-warning/15 px-2 py-0.5 text-xs font-semibold text-warning">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>@endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>
        <div class="border-t border-line p-3">
            <div class="flex items-center gap-3 rounded-xl px-3 py-2">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-500/15 text-sm font-bold text-brand-300">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                <span class="min-w-0 flex-1 leading-tight"><span class="block truncate text-sm font-semibold">{{ $u->name }}</span><span class="block truncate text-xs text-fg-subtle">{{ $u->role->label() }}</span></span>
            </div>
            <div class="mt-1 grid grid-cols-2 gap-1">
                <a href="{{ route('home') }}" class="btn btn-ghost btn-sm"><x-icon name="home" class="size-4" />{{ __('Website') }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-sm w-full"><x-icon name="logout" class="size-4" />{{ __('Log out') }}</button></form>
            </div>
        </div>
    </aside>

    <div class="lg:ps-72">
        <header class="sticky top-0 z-30 border-b border-line bg-ink-950/85 backdrop-blur-xl">
            <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                <button type="button" class="btn btn-ghost !min-h-11 !px-2.5 lg:hidden" @click="nav = true" aria-label="{{ __('Open menu') }}"><x-icon name="menu" class="size-6" /></button>
                <div class="min-w-0 flex-1">
                    @isset($breadcrumb)<div class="truncate text-xs text-fg-subtle">{{ $breadcrumb }}</div>@endisset
                    <p class="truncate text-sm font-semibold lg:hidden">{{ $heading ?? $title }}</p>
                </div>
                <a href="{{ route('locale', $otherLocale) }}" class="btn btn-ghost btn-sm" lang="{{ $otherLocale }}"><x-icon name="globe" class="size-4" />{{ $otherLocale === 'ar' ? 'العربية' : 'English' }}</a>
            </div>
        </header>

        <main id="admin-main" tabindex="-1" class="px-4 py-6 outline-none sm:px-6 lg:px-8 lg:py-8">
            @if ($heading)
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between lg:mb-8">
                    <div class="min-w-0">
                        <h1 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ $heading }}</h1>
                        @if ($subheading)<p class="mt-1.5 text-sm text-fg-muted">{{ $subheading }}</p>@endif
                    </div>
                    @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
                </div>
            @endif
            @if ($errors->any() && ! ($hideErrorSummary ?? false))
                <x-alert level="danger" class="mb-6">{{ __('Please fix the highlighted fields.') }}</x-alert>
            @endif
            {{ $slot }}
        </main>
    </div>

    @include('partials.flash')
    @stack('scripts')
</body>
</html>
