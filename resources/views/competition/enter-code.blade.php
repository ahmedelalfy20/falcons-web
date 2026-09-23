<x-layouts.site :title="__('Enter referral code')">
    <section class="relative flex min-h-dvh items-center pt-24 pb-16">
        <div class="bg-glow pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="container-x relative">
            <div class="mx-auto max-w-md text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-brand-500/10 text-brand-300"><x-icon name="qr" class="size-7" /></span>
                <h1 class="h-section mt-6">{{ __('Enter your referral code') }}</h1>
                <p class="mt-3 text-fg-muted">{{ __('Scan your leader’s QR code, or type the code they shared with you.') }}</p>

                <form method="GET" action="{{ route('register') }}" class="mt-8 text-start" x-data="{ code: '' }">
                    <label for="ref" class="label">{{ __('Referral code') }}</label>
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <input id="ref" name="ref" x-model="code" required maxlength="20" autocomplete="off" autocapitalize="characters" spellcheck="false"
                               placeholder="LDR-X7K92M4P" dir="ltr"
                               class="input num flex-1 text-center text-lg font-semibold tracking-[0.12em] uppercase sm:text-start">
                        <button type="submit" class="btn btn-primary btn-lg" :disabled="code.replace(/[^a-z0-9]/gi, '').length < 4">{{ __('Continue') }}</button>
                    </div>
                    <p class="hint">{{ __('Codes look like LDR- followed by 8 letters and numbers.') }}</p>
                </form>
            </div>
        </div>
    </section>
</x-layouts.site>
