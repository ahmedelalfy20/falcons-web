<x-layouts.admin :title="__('Registration #:id', ['id' => $registration->id])" :heading="$registration->full_name" :subheading="__('Registration #:id · submitted :when', ['id' => $registration->id, 'when' => $registration->created_at->translatedFormat('j M Y, H:i')])">
    <x-slot:breadcrumb><a href="{{ route('admin.registrations.index') }}" class="hover:text-fg">{{ __('Registrations') }}</a> / #{{ $registration->id }}</x-slot:breadcrumb>
    <x-slot:actions><span class="badge badge-{{ $registration->status->value }} !px-3 !py-1.5 !text-sm">{{ $registration->status->label() }}</span></x-slot:actions>

    <div class="grid gap-5 lg:grid-cols-12">
        <div class="space-y-5 lg:col-span-8">
            @if ($related->isNotEmpty())
                <x-alert level="warning">
                    <p class="font-semibold">{{ __('Possible duplicate') }}</p>
                    <p class="mt-1">{{ __('The same phone or email appears in :n other registration(s) in this competition:', ['n' => $related->count()]) }}</p>
                    <ul class="mt-2 space-y-1">
                        @foreach ($related as $o)
                            <li><a href="{{ route('admin.registrations.show', $o) }}" class="underline underline-offset-2">#{{ $o->id }}</a> — {{ $o->round?->displayName() }} · {{ $o->leader?->name }} · {{ $o->status->label() }}</li>
                        @endforeach
                    </ul>
                </x-alert>
            @endif

            <section class="card" aria-labelledby="proof-title">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 id="proof-title" class="font-bold">{{ __('Transfer screenshot') }}</h2>
                    @if ($registration->transfer_path)
                        <a href="{{ route('admin.registrations.transfer', $registration) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm"><x-icon name="external" class="size-4" />{{ __('Open full size') }}</a>
                    @endif
                </div>
                @if ($registration->transfer_path)
                    <a href="{{ route('admin.registrations.transfer', $registration) }}" target="_blank" rel="noopener" class="block bg-ink-900 p-4">
                        <img src="{{ route('admin.registrations.transfer', $registration) }}" alt="{{ __('Transfer screenshot for registration #:id', ['id' => $registration->id]) }}"
                             class="mx-auto max-h-[32rem] w-auto rounded-lg object-contain" loading="lazy">
                    </a>
                @else
                    <x-empty icon="image" :title="__('No screenshot uploaded')" :text="__('This registration was submitted without a transfer screenshot.')" />
                @endif
            </section>

            <section class="card" aria-labelledby="details">
                <h2 id="details" class="border-b border-line px-5 py-4 font-bold">{{ __('Participant details') }}</h2>
                <dl class="grid sm:grid-cols-2">
                    @foreach ([
                        __('Full name') => $registration->full_name,
                        __('Phone') => $registration->phone,
                        __('Email') => $registration->email ?: '—',
                        __('City') => $registration->city ?: '—',
                        __('Normalized phone') => $registration->phone_normalized,
                        __('Round') => $registration->round->displayName(),
                    ] as $label => $value)
                        <div class="border-b border-line px-5 py-4 sm:odd:border-e">
                            <dt class="text-xs text-fg-subtle">{{ $label }}</dt>
                            <dd class="mt-1 font-medium break-words" @if (in_array($label, [__('Phone'), __('Email'), __('Normalized phone')])) dir="ltr" @endif>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="card" aria-labelledby="history-title">
                <h2 id="history-title" class="border-b border-line px-5 py-4 font-bold">{{ __('History') }}</h2>
                <ol class="px-5 py-2">
                    <li class="flex gap-3 py-3"><span class="mt-1.5 size-2 shrink-0 rounded-full bg-warning"></span><div><p class="text-sm">{{ __('Submitted via :leader’s referral code', ['leader' => $registration->leader->name]) }}</p><p class="text-xs text-fg-subtle">{{ $registration->created_at->translatedFormat('j M Y, H:i:s') }}</p></div></li>
                    @foreach ($history->reverse() as $log)
                        <li class="flex gap-3 border-t border-line py-3"><span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-success' => str_ends_with($log->action, 'accepted'), 'bg-danger' => str_ends_with($log->action, 'rejected'), 'bg-fg-subtle' => ! str_ends_with($log->action, 'accepted') && ! str_ends_with($log->action, 'rejected')])></span>
                            <div><p class="text-sm">{{ $log->sentence() }}</p>@if (! empty($log->metadata['note']))<p class="mt-1 text-sm text-fg-muted">“{{ $log->metadata['note'] }}”</p>@endif<p class="text-xs text-fg-subtle">{{ $log->created_at->translatedFormat('j M Y, H:i:s') }}</p></div></li>
                    @endforeach
                </ol>
            </section>
        </div>

        <aside class="space-y-5 lg:col-span-4">
            <section class="card card-pad" aria-labelledby="decision">
                <h2 id="decision" class="font-bold">{{ __('Decision') }}</h2>
                @if ($registration->isPending())
                    @canany(['accept', 'reject'], $registration)
                        <form method="POST" class="mt-4 space-y-3" x-data="{ busy: false }" @submit="busy = true">
                            @csrf
                            <x-field name="note" :label="__('Note (optional)')" textarea rows="3" maxlength="500" :hint="__('Saved in the audit log.')" />
                            <div class="grid grid-cols-2 gap-2">
                                @can('reject', $registration)
                                    <button formaction="{{ route('admin.registrations.reject', $registration) }}" class="btn btn-danger" :disabled="busy" onclick="return confirm(@js(__('Reject this registration? This cannot be undone.')))"><x-icon name="x" class="size-4" />{{ __('Reject') }}</button>
                                @endcan
                                @can('accept', $registration)
                                    <button formaction="{{ route('admin.registrations.accept', $registration) }}" class="btn btn-success" :disabled="busy"><x-icon name="check" class="size-4" />{{ __('Accept') }}</button>
                                @endcan
                            </div>
                        </form>
                    @else
                        <p class="mt-3 text-sm text-fg-muted">{{ __('You can view this registration but not review it.') }}</p>
                    @endcanany
                @else
                    <p class="mt-3 text-sm text-fg-muted">{{ __(':status by :name on :date.', ['status' => $registration->status->label(), 'name' => $registration->reviewer?->name ?? __('an admin'), 'date' => $registration->reviewed_at?->translatedFormat('j M Y, H:i')]) }}</p>
                    @if ($registration->review_note)<p class="mt-3 rounded-xl bg-ink-900 p-3 text-sm text-fg-muted">“{{ $registration->review_note }}”</p>@endif
                    <p class="mt-3 text-xs text-fg-subtle">{{ __('Decisions are final to keep scores trustworthy.') }}</p>
                @endif
            </section>

            <section class="card card-pad" aria-labelledby="leader-card">
                <h2 id="leader-card" class="text-xs font-semibold uppercase tracking-[0.14em] text-fg-subtle rtl:tracking-normal">{{ __('Referred by') }}</h2>
                <p class="mt-2 text-lg font-bold">{{ $registration->leader->name }}</p>
                <p class="num text-sm text-fg-muted" dir="ltr">{{ $registration->leader->unique_code }}</p>
                @can('view', $registration->leader)
                    <a href="{{ route('admin.leaders.show', $registration->leader) }}" class="btn btn-outline btn-sm mt-4">{{ __('View leader') }}</a>
                @endcan
            </section>
        </aside>
    </div>
</x-layouts.admin>
