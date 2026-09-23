@php
    $difficulty = ['beginner' => __('Beginner'), 'intermediate' => __('Intermediate'), 'advanced' => __('Advanced')];
    $list = $videos->map(fn ($v) => ['id' => $v->id, 'title' => $v->tr('title'), 'url' => route('free-courses.video', $v)])->values();
@endphp
<x-layouts.site :title="__('Free Courses')" :description="__('Free trading lessons from Falcons Academy — trading basics, TradingView, candlesticks, risk management and MT5.')">
    <section class="relative overflow-hidden pt-28 pb-12 sm:pt-32">
        <div class="bg-glow pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="container-x relative grid items-end gap-8 lg:grid-cols-12">
            <div class="lg:col-span-8">
                <p class="eyebrow">{{ __('Free Education') }}</p>
                <h1 class="h-display mt-5 text-balance">{{ __('Free trading courses') }}</h1>
                <p class="lead mt-5 max-w-2xl">{{ __('Start your professional trading journey with a curated selection of free lessons from the Falcons Academy trainers.') }}</p>
            </div>
            <dl class="flex gap-8 lg:col-span-4 lg:justify-end">
                <div><dt class="text-sm text-fg-muted">{{ __('Videos') }}</dt><dd class="num text-3xl font-extrabold">{{ $count }}</dd></div>
                <div><dt class="text-sm text-fg-muted">{{ __('Price') }}</dt><dd class="text-3xl font-extrabold text-brand-400">{{ __('Free') }}</dd></div>
            </dl>
        </div>
    </section>

    @if (! $unlocked)
        <section class="pb-24">
            <div class="container-x">
                <div class="card mx-auto grid max-w-4xl overflow-hidden md:grid-cols-2">
                    <div class="relative hidden md:block">
                        <img src="{{ asset('media/free-courses/cover.webp') }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-ink-950/80 to-transparent"></div>
                    </div>
                    <div class="p-6 sm:p-10">
                        <span class="flex size-12 items-center justify-center rounded-2xl bg-brand-500/10 text-brand-300"><x-icon name="lock" class="size-6" /></span>
                        <h2 class="mt-5 text-2xl font-bold">{{ __('Enter your invite code') }}</h2>
                        <p class="mt-2 text-sm leading-relaxed text-fg-muted">{{ __('The free courses are unlocked with the invite code shared by the person who invited you.') }}</p>
                        <form method="POST" action="{{ route('free-courses.unlock') }}" class="mt-6 space-y-4" novalidate>
                            @csrf
                            <x-field name="code" :label="__('Invite code')" autocomplete="off" inputmode="numeric" dir="ltr" required maxlength="64" />
                            <button type="submit" class="btn btn-primary btn-lg w-full">{{ __('Unlock courses') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    @else
        <section class="pb-24" x-data="{ current: null, list: @js($list), play(i) { this.current = i; document.documentElement.style.overflow = 'hidden'; }, stop() { this.$refs.video?.pause(); this.current = null; document.documentElement.style.overflow = ''; } }">
            <div class="container-x">
                <ol class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3" role="list">
                    @foreach ($videos as $i => $video)
                        <li>
                            <button type="button" @click="play({{ $i }})" class="card card-hover group flex h-full w-full flex-col overflow-hidden text-start">
                                <span class="relative block aspect-video overflow-hidden bg-ink-800">
                                    <img src="{{ asset('media/free-courses/cover.webp') }}" alt="" loading="lazy" decoding="async" width="640" height="360" class="h-full w-full object-cover opacity-80 transition-transform duration-500 group-hover:scale-[1.03]">
                                    <span class="absolute inset-0 flex items-center justify-center">
                                        <span class="flex size-14 items-center justify-center rounded-full bg-white/90 text-ink-950 transition-transform duration-300 group-hover:scale-105"><x-icon name="play" class="ms-0.5 size-5" /></span>
                                    </span>
                                    <span class="num absolute start-3 top-3 rounded-full bg-ink-950/75 px-2.5 py-1 text-xs font-semibold backdrop-blur">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    @if ($video->duration)<span class="num absolute end-3 bottom-3 rounded-md bg-ink-950/75 px-2 py-0.5 text-xs backdrop-blur">{{ $video->duration }}</span>@endif
                                </span>
                                <span class="flex flex-1 flex-col p-5">
                                    @if ($video->difficulty)<span class="text-xs font-semibold text-brand-300">{{ $difficulty[$video->difficulty] ?? $video->difficulty }}</span>@endif
                                    <span class="mt-1.5 font-bold leading-snug">{{ $video->tr('title') }}</span>
                                    <span class="mt-2 text-sm leading-relaxed text-fg-muted">{{ $video->tr('description') }}</span>
                                </span>
                            </button>
                        </li>
                    @endforeach
                </ol>

                <div class="card card-pad mt-12 flex flex-col items-start justify-between gap-5 sm:flex-row sm:items-center">
                    <div>
                        <p class="text-lg font-bold">{{ __('Ready for more?') }}</p>
                        <p class="mt-1 text-sm text-fg-muted">{{ __('Join Falcons Academy for advanced courses, personal mentorship and continuous support.') }}</p>
                    </div>
                    <a href="{{ whatsapp_url() }}" target="_blank" rel="noopener" class="btn btn-primary shrink-0">{{ __('Join Now') }}</a>
                </div>
            </div>

            {{-- Player: video streams only after opening (preload="metadata") --}}
            <template x-teleport="body">
                <div x-show="current !== null" x-cloak role="dialog" aria-modal="true" :aria-label="current !== null ? list[current].title : ''"
                     x-transition.opacity.duration.200ms @keydown.escape.window="current !== null && stop()" @click.self="stop()"
                     class="fixed inset-0 z-[150] flex items-center justify-center bg-ink-950/95 p-3 sm:p-8">
                    <div class="w-full max-w-5xl">
                        <div class="mb-3 flex items-center justify-between gap-4">
                            <p class="truncate font-semibold" x-text="current !== null ? list[current].title : ''"></p>
                            <button type="button" @click="stop()" class="flex size-11 shrink-0 items-center justify-center rounded-full bg-white/10 hover:bg-white/20" aria-label="{{ __('Close') }}"><x-icon name="x" class="size-5" /></button>
                        </div>
                        <template x-if="current !== null">
                            <video x-ref="video" :src="list[current].url" controls autoplay playsinline preload="metadata" controlslist="nodownload" class="aspect-video w-full rounded-xl bg-black"></video>
                        </template>
                        <div class="mt-3 flex justify-between">
                            <button type="button" class="btn btn-ghost btn-sm" :disabled="current === 0" @click="current--"><x-icon name="arrow-left" class="size-4" />{{ __('Previous') }}</button>
                            <button type="button" class="btn btn-ghost btn-sm" :disabled="current === list.length - 1" @click="current++">{{ __('Next') }}<x-icon name="arrow-right" class="size-4" /></button>
                        </div>
                    </div>
                </div>
            </template>
        </section>

        @if ($justUnlocked)
            <div x-data="{ open: true }" x-show="open" x-cloak x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="welcome-title" class="fixed inset-0 z-[140] flex items-center justify-center bg-ink-950/85 p-4" @keydown.escape.window="open = false">
                <div class="card w-full max-w-md p-6 sm:p-8" @click.outside="open = false">
                    <h2 id="welcome-title" class="text-2xl font-bold">{{ __('Welcome to Your Journey!') }}</h2>
                    <p class="mt-2 text-fg-muted">{{ __('Welcome to your journey in the world of trading and online business!') }}</p>
                    <p class="mt-5 text-sm font-semibold">{{ __('Before you start:') }}</p>
                    <ul class="rich-list !mt-3 text-sm">
                        @foreach ([__('Take a deep breath'), __('Watch the videos carefully'), __('Prepare a notebook and pen'), __('Choose a quiet place'), __('Apply every tip in your notes')] as $tip)<li>{{ $tip }}</li>@endforeach
                    </ul>
                    <p class="mt-4 rounded-xl bg-ink-900 p-4 text-sm leading-relaxed text-fg-muted"><strong class="text-fg">{{ __('Focus:') }}</strong> {{ __('This field has real opportunities, but success requires commitment to the rules.') }} {{ __('After you finish, contact the person who invited you immediately and send them your application.') }}</p>
                    <button type="button" class="btn btn-primary btn-lg mt-6 w-full" @click="open = false">{{ __('Start Now') }}</button>
                </div>
            </div>
        @endif
    @endif
</x-layouts.site>
