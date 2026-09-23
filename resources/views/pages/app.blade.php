<x-layouts.site :title="__('Download the Falcons app')" :description="__('Register and learn with Falcons from your phone — download the app on Google Play or the App Store.')">
    <section class="relative overflow-hidden pt-28 pb-20 sm:pt-32 sm:pb-28">
        <div class="bg-glow pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="container-x relative">
            <div class="mx-auto max-w-3xl text-center">
                {{-- App icon --}}
                <div class="relative mx-auto w-fit" data-reveal>
                    <span class="absolute inset-0 -z-10 scale-150 rounded-full bg-brand-500/20 blur-3xl" aria-hidden="true"></span>
                    <img src="{{ asset('media/brand/logo.webp') }}" alt="{{ __('Falcons app icon') }}" width="160" height="160"
                         class="size-32 rounded-[2rem] bg-ink-900 object-contain p-3 shadow-2xl shadow-black/60 ring-1 ring-white/10 sm:size-40 sm:rounded-[2.4rem]">
                </div>

                <p class="eyebrow mt-10 justify-center">{{ __('Register now') }}</p>
                <h1 class="h-section mt-4">{{ __('Download the Falcons app') }}</h1>
                <p class="lead mx-auto mt-5 max-w-xl">{{ __('Register and start learning with Falcons from your phone. The app is available for Android and iPhone.') }}</p>

                @if ($stores)
                    <ul class="mx-auto mt-10 grid max-w-xl gap-4 sm:grid-cols-{{ count($stores) }}" role="list">
                        @foreach ($stores as $key => $url)
                            <li class="flex flex-col items-center gap-5">
                                <x-store-badge :store="$key" :url="$url" class="w-full" />
                                {{-- QR for desktop visitors: scan with the phone camera --}}
                                <div class="hidden flex-col items-center gap-2 sm:flex">
                                    <div class="rounded-2xl bg-white p-2.5 [&>svg]:size-36">{!! $qr[$key] !!}</div>
                                    <p class="text-xs text-fg-subtle">{{ __('Scan with your phone camera') }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="card mx-auto mt-10 max-w-md"><x-empty icon="clock" :title="__('Download links coming soon')" /></div>
                @endif

                <p class="mt-12 text-sm text-fg-muted">
                    {{ __('Need help with registration?') }}
                    <a href="{{ whatsapp_url(site('contact.whatsapp_message')) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-semibold text-brand-300 hover:text-brand-200"><x-icon name="whatsapp" class="size-4" />{{ __('Contact us on WhatsApp') }}</a>
                </p>
            </div>
        </div>
    </section>
</x-layouts.site>
