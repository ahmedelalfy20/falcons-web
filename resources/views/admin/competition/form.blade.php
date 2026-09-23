@php $editing = $competition->exists; $cfg = $competition->fullConfig(); @endphp
<x-layouts.admin :heading="$editing ? __('Competition settings') : __('New competition')" :subheading="__('Only one competition can be active at a time.')">
    <x-slot:breadcrumb><a href="{{ route('admin.competition.index') }}" class="hover:text-fg">{{ __('Rounds & timer') }}</a> / {{ $editing ? $competition->name : __('New') }}</x-slot:breadcrumb>

    <form method="POST" action="{{ $editing ? route('admin.competitions.update', $competition) : route('admin.competitions.store') }}" class="max-w-3xl space-y-5">
        @csrf
        @if ($editing) @method('PUT') @endif
        <section class="card card-pad space-y-5">
            <h2 class="font-bold">{{ __('Basics') }}</h2>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="name" :label="__('Name (English)')" :value="$competition->name" required maxlength="150" />
                <x-field name="name_ar" :label="__('Name (Arabic)')" :value="$competition->name_ar" dir="rtl" maxlength="150" />
            </div>
            <div>
                <label for="status" class="label">{{ __('Status') }}</label>
                <select id="status" name="status" class="input">
                    @foreach (['draft' => __('Draft — not visible'), 'active' => __('Active — accepting leaders and participants'), 'archived' => __('Archived — read-only history')] as $v => $l)
                        <option value="{{ $v }}" @selected(old('status', $competition->status->value) === $v)>{{ $l }}</option>
                    @endforeach
                </select>
                @error('status')<p class="field-error">{{ $message }}</p>@enderror
            </div>
        </section>

        <section class="card card-pad space-y-5">
            <h2 class="font-bold">{{ __('Registration rules') }}</h2>
            <div>
                <span class="label">{{ __('Duplicate prevention') }}</span>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach (['competition' => [__('Once per competition'), __('A phone or email can register only once in the whole competition.')], 'round' => [__('Once per round'), __('The same person may register again in a later round.')]] as $v => [$t, $h])
                        <label class="flex cursor-pointer gap-3 rounded-xl border border-line bg-ink-900 p-4 has-[:checked]:border-brand-500/60 has-[:checked]:bg-brand-500/[0.06]">
                            <input type="radio" name="duplicate_scope" value="{{ $v }}" @checked(old('duplicate_scope', $cfg['duplicate_scope']) === $v) class="mt-1 accent-brand-500">
                            <span><span class="block text-sm font-medium">{{ $t }}</span><span class="mt-0.5 block text-xs text-fg-subtle">{{ $h }}</span></span>
                        </label>
                    @endforeach
                </div>
            </div>
            <x-field name="default_round_minutes" type="number" :label="__('Default round length (minutes)')" :value="$cfg['default_round_minutes']" min="1" max="43200" required class="max-w-xs" />
            <div class="grid gap-3 sm:grid-cols-2">
                <x-toggle name="require_email" :label="__('Require participant email')" :checked="$cfg['require_email']" />
                <x-toggle name="require_transfer_proof" :label="__('Require transfer screenshot')" :checked="$cfg['require_transfer_proof'] ?? true" :hint="__('Participants must upload a screenshot of their payment transfer.')" />
                <x-toggle name="collect_city" :label="__('Ask participants for their city')" :checked="$cfg['collect_city']" />
                <x-toggle name="leader_self_registration" :label="__('Allow leaders to sign up themselves')" :checked="$cfg['leader_self_registration']" />
                <x-toggle name="leader_auto_approve" :label="__('Activate new leaders automatically')" :checked="$cfg['leader_auto_approve']" :hint="__('Off: new leaders send a request and join the live leaderboard only after an admin approves them.')" />
                <x-toggle name="public_leaderboard" :label="__('Show the public leaderboard')" :checked="$cfg['public_leaderboard']" />
            </div>
        </section>

        <div class="flex items-center justify-between gap-3">
            <a href="{{ route('admin.competition.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
            <button class="btn btn-primary">{{ $editing ? __('Save settings') : __('Create competition') }}</button>
        </div>
    </form>
</x-layouts.admin>
