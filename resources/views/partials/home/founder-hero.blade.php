{{--
    Founder-led opening hero. Editorial split screen: the headline on the dark side,
    the founder's studio portrait full-height and bleeding to the viewport edge.
    Mobile: portrait first (person-first), headline directly below.
--}}
<section id="founder" aria-labelledby="hero-title" class="relative isolate overflow-hidden lg:grid lg:min-h-[max(40rem,100dvh)] lg:grid-cols-12">
    {{-- One continuous background for the whole hero (no seams between columns) --}}
    <div class="bg-glow pointer-events-none absolute inset-0 -z-10" aria-hidden="true"></div>

    {{-- Portrait: transparent cut-out placed directly on the site background --}}
    <div class="relative mt-16 h-[58svh] min-h-[22rem] sm:h-[68svh] lg:order-2 lg:mt-[4.5rem] lg:col-span-5 lg:h-auto xl:col-span-6">
        {{-- Soft brand glow behind the founder (static, no animation cost) --}}
        <div class="pointer-events-none absolute inset-0" aria-hidden="true"
             style="background: radial-gradient(55% 50% at 50% 45%, rgba(34,197,132,0.16), transparent 70%), radial-gradient(40% 35% at 50% 40%, rgba(227,214,193,0.07), transparent 70%)"></div>
        <img src="{{ media(site('founder.image', 'media/founder.webp', false)) }}"
             alt="{{ site('founder.name') }} — {{ site('founder.title') }}"
             width="754" height="754" fetchpriority="high" decoding="async"
             class="hero-portrait absolute inset-x-0 bottom-0 mx-auto h-full w-full object-contain object-bottom [mask-image:linear-gradient(to_bottom,#000_72%,transparent_100%)]">
        {{-- Desktop caption --}}
        <div class="absolute inset-x-6 bottom-6 hidden items-end justify-between gap-4 lg:flex xl:inset-x-10 xl:bottom-10">
            <div class="rounded-2xl border border-white/10 bg-ink-950/75 px-5 py-4 backdrop-blur-md">
                <p class="text-lg font-bold text-white">{{ site('founder.name') }}</p>
                <p class="mt-0.5 text-sm text-white/70">{{ site('founder.title') }}</p>
            </div>
        </div>
    </div>

    {{-- Copy --}}
    <div class="relative flex items-center lg:order-1 lg:col-span-7 xl:col-span-6">
        <div class="hero-copy w-full px-4 pb-14 sm:px-6 lg:pt-28 lg:pb-20 lg:ps-[max(2rem,calc((100vw-1200px)/2+2rem))] lg:pe-12 xl:pe-16">
            <p class="-mt-10 flex items-center gap-3 text-sm lg:mt-0">
                <span class="chip border-brand-500/25 bg-brand-500/[0.08] text-brand-200"><x-icon name="trophy" class="size-3.5" />{{ site('hero.badge') }}</span>
            </p>
            <p class="eyebrow mt-7">{{ __('Meet the Founder') }}</p>
            <h1 id="hero-title" class="mt-4 max-w-[17ch] text-[2.6rem] leading-[1.03] font-extrabold tracking-[-0.035em] text-balance sm:text-6xl lg:text-[3.6rem] xl:text-[4.1rem] rtl:leading-[1.25] rtl:tracking-normal">{{ site('hero.title') }}</h1>
            <p class="mt-5 text-lg text-fg-muted sm:text-xl lg:hidden"><span class="font-semibold text-fg">{{ site('founder.name') }}</span> — {{ site('founder.title') }}</p>

            <figure class="mt-8 max-w-xl">
                <blockquote class="font-serif text-[1.35rem] leading-snug text-sand-200 italic sm:text-2xl">“{{ site('founder.quote') }}”</blockquote>
            </figure>

            <div class="mt-10 flex flex-col gap-3 sm:flex-row sm:items-center">
                <a href="{{ whatsapp_url(site('contact.whatsapp_message')) }}" target="_blank" rel="noopener" class="btn btn-primary btn-lg">
                    {{ site('hero.primary_cta') }} <x-icon name="arrow-right" class="size-4" />
                </a>
                <a href="{{ route('app.download') }}" class="btn btn-outline btn-lg">{{ site('hero.secondary_cta') }}</a>
            </div>
            <p class="mt-8 max-w-md text-sm leading-relaxed text-fg-subtle">{{ site('hero.subtitle') }}</p>
        </div>
    </div>
</section>
