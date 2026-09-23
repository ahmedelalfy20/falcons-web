<x-layouts.site :title="$scanner->tr('name')" :description="$scanner->tr('summary')">
    <article style="--accent: {{ $scanner->accent }}">
        <header class="relative overflow-hidden pt-28 pb-12 sm:pt-32 lg:pb-16">
            <div class="pointer-events-none absolute inset-0 opacity-60" style="background: radial-gradient(50% 60% at 85% 0%, color-mix(in srgb, var(--accent) 22%, transparent), transparent 70%)" aria-hidden="true"></div>
            <div class="container-x relative">
                <nav aria-label="{{ __('Breadcrumb') }}">
                    <a href="{{ route('home') }}#scanners" class="inline-flex min-h-11 items-center gap-2 text-sm text-fg-muted hover:text-fg"><x-icon name="arrow-left" class="size-4" />{{ __('All scanners') }}</a>
                </nav>
                <div class="mt-6 grid items-end gap-10 lg:grid-cols-12">
                    <div class="lg:col-span-7">
                        <p class="inline-flex items-center gap-2 text-sm font-semibold" style="color: var(--accent)"><span class="size-2 rounded-full" style="background: var(--accent)"></span>{{ __('Scanner') }}</p>
                        <h1 class="h-display mt-4">{{ $scanner->tr('name') }}</h1>
                        <p class="mt-4 text-xl font-medium text-fg-muted sm:text-2xl"><bdi>{{ $scanner->tr('tagline') }}</bdi></p>
                    </div>
                    <dl class="grid grid-cols-3 divide-x divide-line rounded-2xl border border-line bg-ink-900/70 rtl:divide-x-reverse lg:col-span-5">
                        <div class="px-4 py-5"><dt class="text-xs text-fg-subtle">{{ __('Timeframe') }}</dt><dd class="mt-1 font-bold" dir="ltr">{{ $scanner->timeframe ?: '—' }}</dd></div>
                        <div class="px-4 py-5"><dt class="text-xs text-fg-subtle">{{ __('Targets') }}</dt><dd class="num mt-1 font-bold">{{ $scanner->targets ? 'TP1–TP'.$scanner->targets : '—' }}</dd></div>
                        <div class="px-4 py-5"><dt class="text-xs text-fg-subtle">{{ __('Method') }}</dt><dd class="mt-1 font-bold leading-snug">{{ $scanner->methodology ?: '—' }}</dd></div>
                    </dl>
                </div>
            </div>
        </header>

        @if ($scanner->coverImage())
            <div class="container-x">
                <div class="overflow-hidden rounded-[1.5rem] border border-line bg-white">
                    <img src="{{ media($scanner->coverImage()) }}" alt="{{ __(':name scanner signal on a chart', ['name' => $scanner->name]) }}" width="1600" height="900" fetchpriority="high" decoding="async" class="aspect-[16/9] w-full object-cover object-top">
                </div>
            </div>
        @endif

        <section class="py-16 sm:py-20">
            <div class="container-x grid gap-12 lg:grid-cols-12">
                <div class="lg:col-span-7">
                    <h2 class="text-2xl font-bold">{{ __('How it works') }}</h2>
                    <div class="prose-copy mt-5 text-[1.05rem]">{{ rich_text($scanner->tr('description')) }}</div>
                </div>
                <div class="lg:col-span-5">
                    @if ($features = $scanner->localizedFeatures())
                        <h2 class="text-2xl font-bold">{{ __('Key Features') }}</h2>
                        <ul class="mt-5 divide-y divide-line rounded-2xl border border-line">
                            @foreach ($features as $f)
                                <li class="flex gap-4 px-5 py-4">
                                    <span class="mt-1.5 size-2 shrink-0 rounded-full" style="background: var(--accent)"></span>
                                    <div><p class="font-semibold">{{ $f['title'] }}</p><p class="mt-0.5 text-sm text-fg-muted">{{ $f['text'] }}</p></div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="card card-pad mt-6">
                        <p class="font-semibold">{{ __('Get started with :name', ['name' => $scanner->tr('name')]) }}</p>
                        <p class="mt-1.5 text-sm text-fg-muted">{{ __('Talk to our team about access, setup and training.') }}</p>
                        <a href="{{ whatsapp_url(__('Hello, I want to know more about the :name scanner', ['name' => $scanner->name])) }}" target="_blank" rel="noopener" class="btn btn-primary mt-5 w-full"><x-icon name="whatsapp" class="size-4" />{{ __('Contact Us') }}</a>
                    </div>
                </div>
            </div>
        </section>

        @if (count($scanner->images ?? []) > 1)
            <section class="border-t border-line py-16 sm:py-20" aria-labelledby="scanner-gallery">
                <div class="container-x">
                    <h2 id="scanner-gallery" class="text-2xl font-bold">{{ __('Scanner Gallery') }}</h2>
                    <x-lightbox-gallery class="mt-6" :items="$scanner->galleryItems()" :label="__(':name gallery', ['name' => $scanner->name])" variant="wide" />
                </div>
            </section>
        @endif

        @if ($others->isNotEmpty())
            <section class="border-t border-line bg-ink-900/40 py-16" aria-labelledby="more-scanners">
                <div class="container-x">
                    <h2 id="more-scanners" class="text-xl font-bold">{{ __('More scanners') }}</h2>
                    <ul class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3" role="list">
                        @foreach ($others as $o)
                            <li>
                                <a href="{{ route('scanners.show', $o) }}" class="card card-hover group flex items-center gap-4 p-4" style="--accent: {{ $o->accent }}">
                                    <span class="h-10 w-1 rounded-full" style="background: var(--accent)"></span>
                                    <span class="min-w-0 flex-1"><span class="block font-bold">{{ $o->tr('name') }}</span><span class="block truncate text-sm text-fg-muted"><bdi>{{ $o->tr('tagline') }}</bdi></span></span>
                                    <x-icon name="arrow-right" class="size-4 text-fg-subtle transition-transform group-hover:translate-x-0.5 rtl:group-hover:-translate-x-0.5" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </section>
        @endif
    </article>
</x-layouts.site>
