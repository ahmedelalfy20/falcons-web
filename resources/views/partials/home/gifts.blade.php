@if ($gifts->isNotEmpty())
<section id="gifts" aria-labelledby="gifts-title" class="border-t border-line bg-ink-900/40 py-20 sm:py-28">
    <div class="container-x grid gap-10 lg:grid-cols-12 lg:gap-12">
        <div class="lg:col-span-5">
            <x-section-heading id="gifts-title" :eyebrow="__('Exclusive Gifts')" :title="__('Gifts for our community')"
                :lead="__('Discover our special gifts and exclusive content prepared for our valued community members.')" />
            @if (file_exists(public_path('media/gifts/gift-video.mp4')))
                <div class="mt-8 overflow-hidden rounded-2xl border border-line bg-black" data-reveal>
                    {{-- preload="none": the video costs nothing until the visitor presses play --}}
                    <video controls preload="none" playsinline poster="{{ media($gifts->first()->thumb_path ?: $gifts->first()->path) }}" class="aspect-square w-full object-cover">
                        <source src="{{ asset('media/gifts/gift-video.mp4') }}" type="video/mp4">
                    </video>
                </div>
            @endif
        </div>
        <div class="lg:col-span-7">
            <x-lightbox-gallery :images="$gifts" :label="__('Gift gallery')" variant="squares" data-reveal />
        </div>
    </div>
</section>
@endif
