@if ($team->isNotEmpty())
<section id="leaders" aria-labelledby="leaders-title" class="border-t border-line bg-ink-900/40 py-20 sm:py-28">
    <div class="container-x">
        <x-section-heading id="leaders-title" :eyebrow="__('Our Excellence')" :title="__('Meet Our Top Leaders')"
            :lead="__('Exceptional minds driving success in the trading world. Our leaders combine years of experience with proven results, mentoring the next generation of elite traders.')" />
        <ul @class(['mt-12 grid grid-cols-2 gap-3 sm:gap-5', 'sm:grid-cols-3 lg:grid-cols-5' => $team->count() === 5, 'lg:grid-cols-4' => $team->count() !== 5]) role="list">
            @foreach ($team as $member)
                <li data-reveal style="--reveal-delay: {{ $loop->index * 70 }}ms">
                    <a href="{{ route('team.show', $member) }}" class="group block focus-visible:outline-offset-4">
                        <div class="relative aspect-[4/5] overflow-hidden rounded-2xl bg-[#ecebe8]">
                            @if ($member->image)
                                <img src="{{ media($member->image) }}" alt="{{ $member->tr('name') }}" loading="lazy" decoding="async" width="600" height="750"
                                     class="h-full w-full object-cover object-top transition-transform duration-500 ease-out group-hover:scale-[1.04]">
                            @endif
                            <span class="absolute end-3 top-3 flex size-9 items-center justify-center rounded-full bg-ink-950/70 text-white opacity-0 backdrop-blur transition-opacity duration-300 group-hover:opacity-100 group-focus-visible:opacity-100" aria-hidden="true">
                                <x-icon name="arrow-up-right" class="size-4" />
                            </span>
                        </div>
                        <h3 class="mt-4 text-base font-bold leading-tight sm:text-lg">{{ $member->tr('name') }}</h3>
                        <p class="mt-1 text-sm text-fg-muted">{{ $member->tr('role') }}@if ($member->tr('city')) <span class="text-fg-subtle">· {{ $member->tr('city') }}</span>@endif</p>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endif
