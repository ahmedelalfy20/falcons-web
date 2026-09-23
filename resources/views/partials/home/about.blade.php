<section id="about" aria-labelledby="about-title" class="overflow-hidden border-t border-line bg-ink-900/40 py-20 sm:py-28">
    <div class="container-x">
        <div class="grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <p class="eyebrow" data-reveal>{{ __('Our Story') }}</p>
                <h2 id="about-title" class="h-section mt-4 text-balance" data-reveal>{!! __('About :brand Organization', ['brand' => '<span class="text-brand-400">Falcons</span>']) !!}</h2>
                <figure class="mt-8 hidden lg:block" data-reveal>
                    <blockquote class="font-serif text-2xl leading-snug text-sand-200 italic">“{{ site('about.quote') }}”</blockquote>
                    <figcaption class="mt-4 text-sm text-fg-muted"><span class="font-semibold text-fg">{{ site('founder.name') }}</span> — {{ site('founder.title') }}</figcaption>
                </figure>
            </div>
            <div class="lg:col-span-7">
                <div class="prose-copy text-[1.05rem]" data-reveal>
                    <p class="!text-fg">{{ site('about.story_1') }}</p>
                    <p>{{ site('about.story_2') }}</p>
                    <p>{{ site('about.story_3') }}</p>
                </div>
                <figure class="mt-8 lg:hidden" data-reveal>
                    <blockquote class="border-s-2 border-brand-500/70 ps-5 font-serif text-xl leading-snug text-sand-200 italic">“{{ site('about.quote') }}”</blockquote>
                </figure>

                <div class="mt-10 grid gap-6 border-t border-line pt-8 sm:grid-cols-3" data-reveal>
                    @foreach ([
                        ['icon' => 'shield', 'title' => __('Discipline'), 'text' => __('Building mental resilience for market volatility')],
                        ['icon' => 'target', 'title' => __('Precision'), 'text' => __('Every trade backed by thorough analysis')],
                        ['icon' => 'users', 'title' => __('Community'), 'text' => __('Continuous support and shared learning')],
                    ] as $value)
                        <div>
                            <x-icon :name="$value['icon']" class="size-6 text-brand-400" />
                            <h3 class="mt-3 font-bold">{{ $value['title'] }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-fg-muted">{{ $value['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

    @if ($gallery->isNotEmpty())
        <div class="mt-16 sm:mt-24" x-data>
            <div class="container-x mb-8 flex flex-wrap items-end justify-between gap-4" data-reveal>
                <div>
                    <p class="eyebrow">{{ __('Moments') }}</p>
                    <h3 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">{{ __('Inside the Academy') }}</h3>
                </div>
                <button type="button" class="btn btn-outline btn-sm" @click="$dispatch('gallery-open')">
                    <x-icon name="image" class="size-4" />{{ trans_choice(':count photo|:count photos', $gallery->count()) }}
                </button>
            </div>
            <x-gallery-marquee :images="$gallery" :label="__('Academy gallery')" @gallery-open.window="show(0)" />
        </div>
    @endif
</section>
