@php
    $meta = [
        'invalid_code' => ['icon' => 'qr', 'title' => __('Invalid referral code'), 'retry' => true],
        'leader_inactive' => ['icon' => 'user', 'title' => __('This leader is not active'), 'retry' => true],
        'competition_inactive' => ['icon' => 'trophy', 'title' => __('No active competition'), 'retry' => false],
        'no_round' => ['icon' => 'clock', 'title' => __('Not started yet'), 'retry' => false],
        'registration_closed' => ['icon' => 'lock', 'title' => __('Registration is currently closed.'), 'retry' => false],
        'competition_finished' => ['icon' => 'timer', 'title' => __('This round has finished'), 'retry' => false],
    ][$reason] ?? ['icon' => 'alert', 'title' => __('Registration unavailable'), 'retry' => true];
@endphp
<x-layouts.site :title="$meta['title']">
    <section class="relative flex min-h-dvh items-center pt-24 pb-16">
        <div class="container-x">
            <div class="mx-auto max-w-md text-center">
                <span class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-white/[0.05] text-fg-muted"><x-icon :name="$meta['icon']" class="size-7" /></span>
                <h1 class="mt-6 text-3xl font-bold tracking-tight">{{ $meta['title'] }}</h1>
                <p class="mt-3 leading-relaxed text-fg-muted">{{ $message }}</p>
                @if ($code)
                    <p class="mt-4"><span class="chip num" dir="ltr">{{ $code }}</span></p>
                @endif
                <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                    @if ($meta['retry'])
                        <a href="{{ route('register') }}" class="btn btn-primary">{{ __('Try another code') }}</a>
                    @endif
                    <a href="{{ route('competition') }}" class="btn btn-outline">{{ __('View the competition') }}</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
