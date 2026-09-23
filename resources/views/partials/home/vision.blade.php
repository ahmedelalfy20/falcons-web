@php
    $vision = (array) site('vision.vision', []);
    $mission = (array) site('vision.mission', []);
@endphp
<section aria-labelledby="vision-title" class="py-20 sm:py-28">
    <div class="container-x grid gap-14 lg:grid-cols-12 lg:gap-12">
        <div class="lg:col-span-5">
            <p class="eyebrow" data-reveal>{{ __('Meet the Founder') }}</p>
            <h2 id="vision-title" class="h-section mt-4" data-reveal>{{ __('Vision & Mission') }}</h2>
            <div class="prose-copy mt-6" data-reveal>
                <p>{{ site('founder.bio_1') }}</p>
                <p>{{ site('founder.bio_2') }}</p>
            </div>
            @if (site('founder.mission_quote'))
                <figure class="mt-8 rounded-2xl border border-line bg-ink-900 p-6" data-reveal>
                    <x-icon name="quote" class="size-6 text-brand-400" />
                    <blockquote class="mt-3 font-serif text-lg leading-snug text-sand-200 italic">{{ site('founder.mission_quote') }}</blockquote>
                    <figcaption class="mt-3 text-sm text-fg-muted">— {{ site('founder.name') }}</figcaption>
                </figure>
            @endif
        </div>

        <div class="grid gap-5 sm:grid-cols-2 lg:col-span-7 lg:gap-6">
            @foreach ([['label' => __('Our Vision'), 'icon' => 'compass', 'items' => $vision], ['label' => __('Our Mission'), 'icon' => 'target', 'items' => $mission]] as $block)
                <div class="card card-pad flex flex-col" data-reveal style="--reveal-delay: {{ $loop->index * 90 }}ms">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 items-center justify-center rounded-xl bg-brand-500/10 text-brand-300"><x-icon :name="$block['icon']" /></span>
                        <h3 class="text-lg font-bold">{{ $block['label'] }}</h3>
                    </div>
                    <ol class="mt-6 space-y-5">
                        @foreach ($block['items'] as $n => $point)
                            <li class="flex gap-4">
                                <span class="num mt-0.5 text-sm font-semibold text-fg-subtle">{{ str_pad($n + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="leading-relaxed text-fg-muted">{{ $point }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endforeach
        </div>
    </div>
</section>
