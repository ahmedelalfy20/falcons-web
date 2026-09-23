<x-layouts.site :title="$member->tr('name')" :description="$member->tr('role')">
    <article class="relative overflow-hidden pt-24 sm:pt-28">
        <div class="bg-glow pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="container-x relative">
            <a href="{{ route('home') }}#leaders" class="inline-flex min-h-11 items-center gap-2 text-sm text-fg-muted hover:text-fg"><x-icon name="arrow-left" class="size-4" />{{ __('All leaders') }}</a>

            <div class="mt-6 grid gap-10 pb-16 lg:grid-cols-12 lg:gap-14 lg:pb-24">
                {{-- Large portrait --}}
                <div class="lg:col-span-5">
                    <div class="relative mx-auto aspect-[4/5] max-w-md overflow-hidden rounded-[2rem] border border-white/10 bg-[#ecebe8] lg:sticky lg:top-28 lg:max-w-none">
                        @if ($member->image)
                            <img src="{{ media($member->image) }}" alt="{{ $member->tr('name') }}" width="1000" height="1250" fetchpriority="high" decoding="async" class="h-full w-full object-cover object-top">
                        @else
                            <div class="flex h-full items-center justify-center bg-ink-800"><x-icon name="user" class="size-16 text-fg-subtle" /></div>
                        @endif
                    </div>
                </div>

                <div class="lg:col-span-7 lg:pt-6">
                    <p class="eyebrow">{{ __('Falcons Leader') }}</p>
                    <h1 class="h-display mt-5 text-balance">{{ $member->tr('name') }}</h1>
                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        @if ($member->tr('role'))<span class="chip border-brand-500/30 bg-brand-500/[0.07] text-brand-200"><x-icon name="trophy" class="size-3.5" />{{ $member->tr('role') }}</span>@endif
                        @if ($member->tr('city'))<span class="chip"><x-icon name="map-pin" class="size-3.5" />{{ $member->tr('city') }}</span>@endif
                    </div>

                    @if ($member->experience_years || $member->company)
                        <div class="mt-8 grid max-w-xl gap-3 sm:grid-cols-2">
                            @if ($member->experience_years)
                                <div class="card flex items-center gap-4 p-4">
                                    <div class="num text-4xl font-extrabold text-brand-300">{{ $member->experience_years }}<span class="text-brand-400">+</span></div>
                                    <p class="text-sm leading-snug text-fg-muted">{{ trans_choice('{1} year of experience|[2,*] years of experience', $member->experience_years) }}</p>
                                </div>
                            @endif
                            @if ($member->company)
                                <div class="card flex items-center gap-4 p-4">
                                    <div @class(['flex h-12 w-24 shrink-0 items-center justify-center rounded-lg', 'bg-white px-2' => $member->company->on_light])>
                                        <img src="{{ media($member->company->logo) }}" alt="{{ $member->company->tr('name') }}" class="max-h-full max-w-full object-contain">
                                    </div>
                                    <p class="text-sm leading-snug text-fg-muted">{{ __('Company') }}<span class="block font-semibold text-fg">{{ $member->company->tr('name') }}</span></p>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="mt-10 border-t border-line pt-8">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-fg-subtle rtl:tracking-normal">{{ __('Biography') }}</h2>
                        @if ($member->hasBio())
                            <div class="prose-copy mt-5 text-[1.08rem]">{{ rich_text($member->tr('bio')) }}</div>
                        @else
                            <div class="mt-5 rounded-2xl border border-dashed border-line-strong px-6 py-8 text-center">
                                <x-icon name="book" class="mx-auto size-6 text-fg-subtle" />
                                <p class="mt-3 font-medium">{{ __('Biography coming soon') }}</p>
                                <p class="mt-1 text-sm text-fg-muted">{{ __(':name’s full profile will be published here shortly.', ['name' => $member->tr('name')]) }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ whatsapp_url(__('Hello, I would like to join Falcons through :name', ['name' => $member->name])) }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg"><x-icon name="whatsapp" class="size-4" />{{ __('Join through :name', ['name' => $member->plainName()]) }}</a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Events gallery --}}
        @if ($items = $member->galleryItems())
            <section class="border-t border-line py-16 sm:py-20" aria-labelledby="events-title">
                <div class="container-x">
                    <div class="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p class="eyebrow">{{ __('Events & workshops') }}</p>
                            <h2 id="events-title" class="h-section mt-3">{{ __('On stage with :name', ['name' => $member->plainName()]) }}</h2>
                        </div>
                        <p class="text-sm text-fg-subtle">{{ trans_choice('{1} 1 photo|[2,*] :count photos', count($items)) }} · {{ __('Tap a photo to enlarge') }}</p>
                    </div>
                    <x-lightbox-gallery class="mt-8" :items="$items" :label="__('Events with :name', ['name' => $member->plainName()])" />
                </div>
            </section>
        @endif

        {{-- Previous / next leader + the rest of the team --}}
        @if ($previous && $next)
            <nav class="border-t border-line" aria-label="{{ __('Other leaders') }}">
                <div class="container-x grid grid-cols-2">
                    <a href="{{ route('team.show', $previous) }}" class="group flex min-h-24 items-center gap-4 border-e border-line py-6 pe-4">
                        <x-icon name="arrow-left" class="size-5 shrink-0 text-fg-subtle transition-transform group-hover:-translate-x-1 rtl:group-hover:translate-x-1" />
                        <span class="min-w-0"><span class="block text-xs text-fg-subtle">{{ __('Previous') }}</span><span class="block truncate font-semibold">{{ $previous->tr('name') }}</span></span>
                    </a>
                    <a href="{{ route('team.show', $next) }}" class="group flex min-h-24 items-center justify-end gap-4 py-6 ps-4 text-end">
                        <span class="min-w-0"><span class="block text-xs text-fg-subtle">{{ __('Next') }}</span><span class="block truncate font-semibold">{{ $next->tr('name') }}</span></span>
                        <x-icon name="arrow-right" class="size-5 shrink-0 text-fg-subtle transition-transform group-hover:translate-x-1 rtl:group-hover:-translate-x-1" />
                    </a>
                </div>
            </nav>
        @endif
        <section class="border-t border-line bg-ink-900/40 py-12" aria-labelledby="team-strip">
            <div class="container-x">
                <h2 id="team-strip" class="text-sm font-semibold text-fg-muted">{{ __('Meet the team') }}</h2>
                <ul class="mt-5 flex gap-3 overflow-x-auto pb-2 [scrollbar-width:thin]" role="list">
                    @foreach ($team as $m)
                        <li class="shrink-0">
                            <a href="{{ route('team.show', $m) }}" @class(['flex items-center gap-3 rounded-full border py-1.5 ps-1.5 pe-4 transition-colors', 'border-brand-500/50 bg-brand-500/[0.07]' => $m->is($member), 'border-line hover:border-line-strong' => ! $m->is($member)]) @if ($m->is($member)) aria-current="page" @endif>
                                <img src="{{ media($m->image) }}" alt="" width="40" height="40" loading="lazy" class="size-10 rounded-full bg-[#ecebe8] object-cover object-top">
                                <span class="text-sm font-medium whitespace-nowrap">{{ $m->tr('name') }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    </article>
</x-layouts.site>
