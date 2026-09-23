@if ($competition && $round && $round->status->value !== 'draft')
    @php $live = $round->status->isLive(); @endphp
    <section aria-labelledby="comp-teaser" class="py-6">
        <div class="container-x">
            <div class="card relative overflow-hidden p-6 sm:p-8 lg:p-10" data-reveal>
                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(50%_80%_at_100%_0%,rgba(34,197,132,0.14),transparent_70%)]" aria-hidden="true"></div>
                <div class="relative grid items-center gap-8 lg:grid-cols-12">
                    <div class="lg:col-span-7">
                        <p class="eyebrow">{{ __('Leader Referral Competition') }}</p>
                        <h2 id="comp-teaser" class="mt-3 text-2xl font-bold sm:text-3xl">
                            @if ($live) {{ __(':round is live', ['round' => $round->displayName()]) }}
                            @elseif ($round->status->value === 'finished') {{ __(':round has finished', ['round' => $round->displayName()]) }}
                            @else {{ __(':round starts soon', ['round' => $round->displayName()]) }} @endif
                        </h2>
                        <p class="mt-3 max-w-xl text-fg-muted">{{ __('Leaders invite participants with their personal QR code. Every approved registration counts toward the live leaderboard.') }}</p>
                        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                            <a href="{{ route('competition') }}" class="btn btn-primary">{{ __('View live leaderboard') }} <x-icon name="arrow-right" class="size-4" /></a>
                            <a href="{{ route('leader.signup') }}" class="btn btn-outline">{{ __('Become a leader') }}</a>
                        </div>
                    </div>
                    <div class="lg:col-span-5" x-data="countdown(@js(\App\Support\RoundPayload::make($round)))">
                        <p class="text-sm text-fg-subtle">{{ $round->status->value === 'paused' ? __('Timer paused') : __('Time remaining') }}</p>
                        <p class="num mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl" x-text="clock.text">{{ format_duration($round->remainingSeconds()) }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif
