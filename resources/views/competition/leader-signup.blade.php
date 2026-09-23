<x-layouts.site :title="__('Become a leader')">
    <section class="relative pt-24 pb-16 sm:pt-28">
        <div class="bg-glow pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="container-x relative">
            <div class="mx-auto grid max-w-5xl gap-10 lg:grid-cols-12 lg:gap-14">
                <div class="lg:col-span-5">
                    <p class="eyebrow">{{ __('Leader Referral Competition') }}</p>
                    <h1 class="h-section mt-4">{{ __('Become a Falcons leader') }}</h1>
                    <p class="mt-4 leading-relaxed text-fg-muted">{{ __('Register once and get your personal referral code, QR code and link. Share it — every approved participant you bring counts toward your score.') }}</p>
                    <ul class="mt-8 space-y-4">
                        @foreach ([
                            ['icon' => 'qr', 'text' => __('A unique referral code and QR, ready to download and share')],
                            ['icon' => 'chart', 'text' => __('A live dashboard with your score, rank and registrations')],
                            ['icon' => 'history', 'text' => __('Results from every round are kept')],
                            ['icon' => 'shield-check', 'text' => __('Your request is reviewed by our team — once approved, your photo appears on the live leaderboard')],
                        ] as $item)
                            <li class="flex gap-3"><span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-500/10 text-brand-300"><x-icon :name="$item['icon']" class="size-4" /></span><span class="pt-1.5 text-sm text-fg-muted">{{ $item['text'] }}</span></li>
                        @endforeach
                    </ul>
                </div>

                <div class="lg:col-span-7">
                    @if (! $open)
                        <div class="card">
                            <x-empty icon="lock" :title="__('Leader registration is currently closed.')" :text="__('Please check back later or contact us on WhatsApp.')">
                                <a href="{{ whatsapp_url() }}" target="_blank" rel="noopener" class="btn btn-outline">{{ __('Contact us') }}</a>
                            </x-empty>
                        </div>
                    @else
                        @if (session('error'))<x-alert level="danger" class="mb-5">{{ session('error') }}</x-alert>@endif
                        <form method="POST" action="{{ route('leader.signup.store') }}" enctype="multipart/form-data" class="card card-pad space-y-5" novalidate x-data="{ busy: false }" @submit="busy = true">
                            @csrf
                            <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                            {{-- Personal photo (appears on the live leaderboard) --}}
                            <div x-data="{ preview: null, pick(f) { if (!f) return; if (this.preview) URL.revokeObjectURL(this.preview); this.preview = URL.createObjectURL(f); } }"
                                 class="flex flex-col items-center gap-4 rounded-2xl border border-line bg-ink-900/60 p-5 text-center sm:flex-row sm:text-start">
                                <label for="photo" class="group relative shrink-0 cursor-pointer" :aria-label="@js(__('Choose a personal photo'))">
                                    <span class="flex size-24 items-center justify-center overflow-hidden rounded-full border-2 border-dashed transition-colors @error('photo') border-danger/70 @else border-line-strong group-hover:border-brand-400 @enderror" :class="preview && '!border-solid !border-brand-500'">
                                        <template x-if="preview"><img :src="preview" alt="" class="size-full object-cover"></template>
                                        <template x-if="!preview"><x-icon name="user" class="size-9 text-fg-subtle" /></template>
                                    </span>
                                    <span class="absolute -bottom-1 -end-1 flex size-8 items-center justify-center rounded-full bg-brand-500 text-ink-950 ring-4 ring-ink-850"><x-icon name="plus" class="size-4" /></span>
                                </label>
                                <div class="min-w-0 flex-1">
                                    <p class="font-semibold">{{ __('Personal photo') }} <span class="text-danger" aria-hidden="true">*</span></p>
                                    <p class="mt-1 text-sm text-fg-muted">{{ __('A clear photo of your face. It appears next to your name on the live leaderboard.') }}</p>
                                    <label for="photo" class="btn btn-outline btn-sm mt-3 cursor-pointer"><x-icon name="image" class="size-4" /><span x-text="preview ? @js(__('Change photo')) : @js(__('Choose photo'))">{{ __('Choose photo') }}</span></label>
                                    <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" required class="sr-only" @change="pick($event.target.files[0])" @error('photo') aria-invalid="true" aria-describedby="photo-error" @enderror>
                                    @error('photo')
                                        <p id="photo-error" class="field-error sm:justify-start">{{ $message }}</p>
                                    @else
                                        @if ($errors->any())<p class="mt-1.5 text-sm text-warning">{{ __('Please choose your photo again after fixing the errors below.') }}</p>@endif
                                    @enderror
                                </div>
                            </div>
                            <x-field name="name" :label="__('Full name')" autocomplete="name" required maxlength="120" />
                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-field name="phone" type="tel" :label="__('Phone number')" autocomplete="tel" inputmode="tel" dir="ltr" required maxlength="25" placeholder="01XXXXXXXXX" />
                                <x-field name="email" type="email" :label="__('Email')" autocomplete="email" inputmode="email" dir="ltr" required maxlength="190" :hint="__('You will use it to log in.')" />
                            </div>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-field name="password" type="password" :label="__('Password')" autocomplete="new-password" required :hint="__('At least 8 characters, with letters and numbers.')" />
                                <x-field name="password_confirmation" type="password" :label="__('Confirm password')" autocomplete="new-password" required />
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="busy">
                                <span x-show="!busy">{{ __('Send my leader request') }}</span>
                                <span x-show="busy" x-cloak>{{ __('Creating…') }}</span>
                            </button>
                            <p class="text-center text-xs text-fg-subtle">{{ __('You get your referral code and QR right away. It starts accepting registrations once an admin approves your request.') }}</p>
                            <p class="text-center text-sm text-fg-muted">{{ __('Already a leader?') }} <a href="{{ route('login') }}" class="font-semibold text-brand-300 hover:text-brand-200">{{ __('Log in') }}</a></p>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
