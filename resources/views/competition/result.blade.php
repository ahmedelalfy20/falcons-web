@php $status = $registration->status->value; @endphp
<x-layouts.site :title="__('Registration status')">
    <section class="relative flex min-h-dvh items-center pt-24 pb-16">
        <div class="bg-glow pointer-events-none absolute inset-0" aria-hidden="true"></div>
        <div class="container-x relative">
            <div class="mx-auto max-w-lg" x-data="{ status: @js($status) }"
                 x-init="if (status === 'pending') {
                    const url = @js(route('live.registration', $registration->public_token));
                    const tick = async () => {
                        if (!document.hidden) {
                            try { const r = await fetch(url, { headers: { Accept: 'application/json' } }); if (r.ok) status = (await r.json()).status; } catch (_) {}
                        }
                        if (status === 'pending') setTimeout(tick, 10000);
                    };
                    setTimeout(tick, 10000);
                 }">
                <div class="card overflow-hidden text-center">
                    <div class="px-6 pt-10 pb-8 sm:px-10">
                        {{-- Pending --}}
                        <div x-show="status === 'pending'" @if ($status !== 'pending') x-cloak @endif>
                            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-warning/12 text-warning"><x-icon name="clock" class="size-8" /></span>
                            <h1 class="mt-6 text-2xl font-bold sm:text-3xl">{{ __('Registration submitted successfully.') }}</h1>
                            <p class="mt-3 leading-relaxed text-fg-muted">{{ __('Your registration is currently pending review.') }}</p>
                            <p class="mt-2 text-sm text-fg-subtle">{{ __('Keep this page — it updates automatically when our team reviews it.') }}</p>
                        </div>
                        {{-- Accepted --}}
                        <div x-show="status === 'accepted'" @if ($status !== 'accepted') x-cloak @endif x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="opacity-0 scale-95">
                            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-success/12 text-success"><x-icon name="check-circle" class="size-8" /></span>
                            <h1 class="mt-6 text-2xl font-bold sm:text-3xl">{{ __('Registration Approved.') }}</h1>
                            <p class="mt-3 leading-relaxed text-fg-muted">{{ __('Welcome to Falcons! Your leader will contact you with the next steps.') }}</p>
                        </div>
                        {{-- Rejected --}}
                        <div x-show="status === 'rejected'" @if ($status !== 'rejected') x-cloak @endif x-transition:enter="transition duration-500 ease-out" x-transition:enter-start="opacity-0 scale-95">
                            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-danger/12 text-danger"><x-icon name="x-circle" class="size-8" /></span>
                            <h1 class="mt-6 text-2xl font-bold sm:text-3xl">{{ __('Registration Rejected.') }}</h1>
                            <p class="mt-3 leading-relaxed text-fg-muted">{{ __('Your registration could not be approved. Please contact the leader who invited you for more information.') }}</p>
                        </div>
                    </div>
                    <dl class="grid grid-cols-2 border-t border-line text-start text-sm">
                        <div class="border-e border-line px-5 py-4">
                            <dt class="text-fg-subtle">{{ __('Reference') }}</dt>
                            <dd class="num mt-1 font-semibold">#{{ $registration->id }}</dd>
                        </div>
                        <div class="px-5 py-4">
                            <dt class="text-fg-subtle">{{ __('Invited by') }}</dt>
                            <dd class="mt-1 truncate font-semibold">{{ $registration->leader->name }}</dd>
                        </div>
                        <div class="border-e border-t border-line px-5 py-4">
                            <dt class="text-fg-subtle">{{ __('Round') }}</dt>
                            <dd class="mt-1 font-semibold">{{ $registration->round->displayName() }}</dd>
                        </div>
                        <div class="border-t border-line px-5 py-4">
                            <dt class="text-fg-subtle">{{ __('Submitted') }}</dt>
                            <dd class="mt-1 font-semibold">{{ $registration->created_at->translatedFormat('j M Y, H:i') }}</dd>
                        </div>
                    </dl>
                </div>
                <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ route('home') }}" class="btn btn-outline">{{ __('Explore Falcons Academy') }}</a>
                    <a href="{{ route('competition') }}" class="btn btn-ghost">{{ __('View live leaderboard') }}</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
