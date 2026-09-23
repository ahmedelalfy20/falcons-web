@php
    $tracks = [
        'trading' => ['label' => __('Trading'), 'title' => __('Trading track'), 'text' => __('Four progressive levels — from reading your first chart to applying professional strategies with confidence.'), 'icon' => 'chart'],
        'marketing' => ['label' => __('Marketing'), 'title' => __('Marketing track'), 'text' => __('Four levels on building a real marketing business and a team that keeps growing.'), 'icon' => 'users'],
    ];
    $difficulty = ['beginner' => __('Beginner'), 'intermediate' => __('Intermediate'), 'advanced' => __('Advanced'), 'professional' => __('Professional')];
@endphp
<section id="courses" aria-labelledby="courses-title" class="py-20 sm:py-28" x-data="{ track: 'trading' }">
    <div class="container-x">
        <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
            <x-section-heading id="courses-title" :eyebrow="__('Our Courses')" :title="__('Choose the right path for you')" />
            <div class="inline-flex self-start rounded-full border border-line bg-ink-900 p-1 lg:self-auto" role="tablist" aria-label="{{ __('Course tracks') }}" data-reveal>
                @foreach ($tracks as $key => $t)
                    @if ($courses->has($key))
                        <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                                :aria-selected="track === '{{ $key }}'" :tabindex="track === '{{ $key }}' ? 0 : -1"
                                @click="track = '{{ $key }}'" @keydown.arrow-right.prevent="track = track === 'trading' ? 'marketing' : 'trading'; $nextTick(() => document.getElementById('tab-' + track).focus())" @keydown.arrow-left.prevent="track = track === 'trading' ? 'marketing' : 'trading'; $nextTick(() => document.getElementById('tab-' + track).focus())"
                                class="min-h-11 rounded-full px-5 text-sm font-semibold transition-colors duration-200"
                                :class="track === '{{ $key }}' ? 'bg-fg text-ink-950' : 'text-fg-muted hover:text-fg'">
                            {{ $t['label'] }}
                        </button>
                    @endif
                @endforeach
            </div>
        </div>

        @foreach ($tracks as $key => $t)
            @continue(! $courses->has($key))
            <div id="panel-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}" x-show="track === '{{ $key }}'" @if (! $loop->first) x-cloak @endif
                 x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="opacity-0 translate-y-1"
                 class="mt-12 grid gap-8 lg:grid-cols-12 lg:gap-12">
                <div class="lg:col-span-4">
                    <div class="lg:sticky lg:top-28">
                        <span class="flex size-12 items-center justify-center rounded-2xl bg-brand-500/10 text-brand-300"><x-icon :name="$t['icon']" class="size-6" /></span>
                        <h3 class="mt-5 text-2xl font-bold">{{ $t['title'] }}</h3>
                        <p class="mt-3 leading-relaxed text-fg-muted">{{ $t['text'] }}</p>
                        <a href="{{ whatsapp_url(__('Hello, I want to enroll in the :track courses', ['track' => $t['label']])) }}" target="_blank" rel="noopener" class="btn btn-primary mt-6">
                            {{ __('Enroll via WhatsApp') }} <x-icon name="arrow-right" class="size-4" />
                        </a>
                    </div>
                </div>

                {{-- Level ladder: one row per level, expandable details --}}
                <ol class="divide-y divide-line overflow-hidden rounded-[var(--radius-card)] border border-line lg:col-span-8" x-data="{ openLevel: null }">
                    @foreach ($courses[$key] as $course)
                        @php $has = $course->hasDetails(); $highlights = array_filter((array) $course->tr('highlights')); @endphp
                        <li class="bg-ink-850">
                            <button type="button" class="flex w-full items-start gap-4 p-5 text-start transition-colors hover:bg-ink-800 sm:gap-6 sm:p-6 disabled:cursor-default disabled:hover:bg-ink-850"
                                    @click="openLevel = openLevel === {{ $course->id }} ? null : {{ $course->id }}" :aria-expanded="openLevel === {{ $course->id }}"
                                    aria-controls="course-{{ $course->id }}" @disabled(! $has)>
                                <span class="num shrink-0 text-3xl font-extrabold leading-none tracking-tight text-fg-subtle sm:text-4xl" aria-hidden="true">{{ str_pad($course->level, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex flex-wrap items-center gap-2">
                                        <span class="text-lg font-bold">{{ $course->tr('title') }}</span>
                                        @if ($course->difficulty)
                                            <span class="chip !py-0.5">{{ $difficulty[$course->difficulty] ?? $course->difficulty }}</span>
                                        @endif
                                    </span>
                                    @if ($course->tr('subtitle'))
                                        <span class="mt-1.5 block text-sm leading-relaxed text-fg-muted">{{ $course->tr('subtitle') }}</span>
                                    @elseif (! $has)
                                        <span class="mt-1.5 block text-sm text-fg-subtle">{{ __('Curriculum details coming soon.') }}</span>
                                    @endif
                                </span>
                                @if ($has)
                                    <x-icon name="chevron-down" class="mt-1 size-5 shrink-0 text-fg-subtle transition-transform duration-300" x-bind:class="openLevel === {{ $course->id }} && 'rotate-180'" />
                                @endif
                            </button>
                            @if ($has)
                                <div id="course-{{ $course->id }}" x-show="openLevel === {{ $course->id }}" x-collapse x-cloak>
                                    <div class="px-5 pb-6 sm:ps-[5.5rem] sm:pe-8">
                                        @if ($course->image)
                                            <img src="{{ media($course->image) }}" alt="" loading="lazy" class="mb-5 aspect-[16/9] w-full rounded-xl object-cover">
                                        @endif
                                        @if ($highlights)
                                            <ul class="rich-list !mt-0">
                                                @foreach ($highlights as $h)<li>{{ $h }}</li>@endforeach
                                            </ul>
                                        @endif
                                        @if ($course->tr('description'))
                                            <details class="group mt-4 rounded-xl border border-line bg-ink-900">
                                                <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between px-4 text-sm font-semibold">
                                                    {{ __('Full curriculum') }} <x-icon name="chevron-down" class="size-4 transition-transform group-open:rotate-180" />
                                                </summary>
                                                <div class="prose-copy max-h-[28rem] overflow-y-auto border-t border-line px-4 py-4 text-sm">{{ rich_text($course->tr('description')) }}</div>
                                            </details>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        @endforeach
    </div>
</section>
