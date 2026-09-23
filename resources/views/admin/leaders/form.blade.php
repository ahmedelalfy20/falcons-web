@php $editing = $leader->exists; @endphp
<x-layouts.admin :heading="$editing ? __('Edit :name', ['name' => $leader->name]) : __('Add leader')" :subheading="$editing ? __('The referral code never changes, so printed QR codes keep working.') : __('A unique referral code and QR are generated automatically.')">
    <x-slot:breadcrumb><a href="{{ route('admin.leaders.index') }}" class="hover:text-fg">{{ __('Leaders') }}</a> / {{ $editing ? $leader->name : __('New') }}</x-slot:breadcrumb>

    <form method="POST" action="{{ $editing ? route('admin.leaders.update', $leader) : route('admin.leaders.store') }}" enctype="multipart/form-data" class="card card-pad max-w-2xl space-y-5" x-data="{ account: @js((bool) old('create_account', false)) }">
        @csrf
        @if ($editing) @method('PUT') @endif
        {{-- Profile photo (shown on the live leaderboard) --}}
        <div class="flex items-center gap-4" x-data="{ preview: null, remove: false }">
            <span class="relative">
                <template x-if="preview"><img :src="preview" alt="" class="size-20 rounded-full object-cover ring-1 ring-white/10"></template>
                <template x-if="!preview"><span :class="remove && 'opacity-30'"><x-leader-avatar :leader="$leader->exists ? $leader : new App\Models\Leader(['name' => '?'])" class="size-20 text-xl" /></span></template>
            </span>
            <div class="min-w-0 flex-1">
                <label for="photo" class="label">{{ __('Profile photo') }}</label>
                <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-fg-muted file:me-3 file:rounded-full file:border-0 file:bg-white/[0.08] file:px-4 file:py-2 file:text-sm file:font-medium file:text-fg hover:file:bg-white/[0.12]"
                       @change="const f = $event.target.files[0]; preview = f ? URL.createObjectURL(f) : null; remove = false">
                <p class="hint">{{ __('Square photos look best. Shown on the live leaderboard.') }}</p>
                @error('photo')<p class="field-error">{{ $message }}</p>@enderror
                @if ($editing && $leader->photo)
                    <label class="mt-2 flex items-center gap-2 text-sm text-fg-muted"><input type="checkbox" name="remove_photo" value="1" class="checkbox" x-model="remove"> {{ __('Remove current photo') }}</label>
                @endif
            </div>
        </div>
        <x-field name="name" :label="__('Full name')" :value="$leader->name" required maxlength="120" />
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="phone" type="tel" :label="__('Phone number')" :value="$leader->phone" dir="ltr" required maxlength="25" />
            <x-field name="email" type="email" :label="__('Email')" :value="$leader->email" dir="ltr" maxlength="190" />
        </div>
        <div>
            <span class="label">{{ __('Status') }}</span>
            <div class="grid gap-2 sm:grid-cols-2">
                @php
                    $options = ['active' => __('Active — accepts registrations'), 'suspended' => __('Suspended — QR is disabled')];
                    if (in_array($leader->status, ['pending', 'rejected'], true)) {
                        $options = [$leader->status => $leader->statusLabel()] + $options;
                    }
                @endphp
                @foreach ($options as $val => $label)
                    <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-line bg-ink-900 px-4 text-sm has-[:checked]:border-brand-500/60 has-[:checked]:bg-brand-500/[0.06]">
                        <input type="radio" name="status" value="{{ $val }}" @checked(old('status', $leader->status) === $val) class="accent-brand-500"> {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>
        @unless ($editing)
            <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-line bg-ink-900 px-4 text-sm">
                <input type="checkbox" name="create_account" value="1" x-model="account" class="checkbox"> {{ __('Create a login so the leader can see their dashboard') }}
            </label>
            <div x-show="account" x-collapse x-cloak>
                <x-field name="password" type="password" :label="__('Temporary password')" autocomplete="new-password" :hint="__('Share it privately; the leader logs in with their email.')" />
            </div>
        @endunless
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-line pt-5">
            <a href="{{ $editing ? route('admin.leaders.show', $leader) : route('admin.leaders.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
            <button class="btn btn-primary">{{ $editing ? __('Save changes') : __('Create leader') }}</button>
        </div>
    </form>
</x-layouts.admin>
