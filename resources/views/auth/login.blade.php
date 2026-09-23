<x-layouts.site :title="__('Log in')">
    <section class="relative flex min-h-dvh items-center pt-24 pb-16">
        <div class="bg-glow pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="container-x relative">
            <div class="mx-auto w-full max-w-md">
                <p class="eyebrow">{{ __('Leaders & staff') }}</p>
                <h1 class="h-section mt-4">{{ __('Welcome back') }}</h1>
                <p class="mt-3 text-fg-muted">{{ __('Log in to see your referral QR, live score and competition status.') }}</p>

                <form method="POST" action="{{ route('login.attempt') }}" class="card card-pad mt-8 space-y-5" x-data="{ busy: false }" @submit="busy = true" novalidate>
                    @csrf
                    <x-field name="email" type="email" :label="__('Email')" autocomplete="email" inputmode="email" required autofocus />
                    <div x-data="{ show: false }">
                        <label for="f-password" class="label">{{ __('Password') }}<span class="text-danger" aria-hidden="true"> *</span></label>
                        <div class="relative">
                            <input id="f-password" name="password" :type="show ? 'text' : 'password'" type="password" required autocomplete="current-password" class="input pe-14">
                            <button type="button" @click="show = !show" class="absolute inset-y-0 end-0 flex w-12 items-center justify-center text-fg-subtle hover:text-fg" :aria-label="show ? @js(__('Hide password')) : @js(__('Show password'))">
                                <x-icon name="eye" class="size-5" />
                            </button>
                        </div>
                    </div>
                    <label class="flex min-h-11 items-center gap-3 text-sm text-fg-muted">
                        <input type="checkbox" name="remember" value="1" class="checkbox"> {{ __('Keep me signed in on this device') }}
                    </label>
                    <button type="submit" class="btn btn-primary btn-lg w-full" :disabled="busy">
                        <span x-show="!busy">{{ __('Log in') }}</span>
                        <span x-show="busy" x-cloak class="inline-flex items-center gap-2"><span class="size-4 animate-spin rounded-full border-2 border-ink-950/30 border-t-ink-950"></span>{{ __('Signing in…') }}</span>
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-fg-muted">
                    {{ __('Want to compete as a leader?') }}
                    <a href="{{ route('leader.signup') }}" class="font-semibold text-brand-300 hover:text-brand-200">{{ __('Create your leader account') }}</a>
                </p>
            </div>
        </div>
    </section>
</x-layouts.site>
