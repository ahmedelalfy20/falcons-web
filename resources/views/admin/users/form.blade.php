@php
    use App\Enums\Permission;
    use App\Enums\Role;
    $editing = $user->exists;
    $selected = old('permissions', $user->permissions ?? []);
@endphp
<x-layouts.admin :heading="$editing ? __('Edit :name', ['name' => $user->name]) : __('Add admin')">
    <x-slot:breadcrumb><a href="{{ route('admin.users.index') }}" class="hover:text-fg">{{ __('Admins') }}</a> / {{ $editing ? $user->name : __('New') }}</x-slot:breadcrumb>

    <form method="POST" action="{{ $editing ? route('admin.users.update', $user) : route('admin.users.store') }}" class="max-w-3xl space-y-5" x-data="{ role: @js(old('role', $user->role?->value ?? 'admin')) }">
        @csrf
        @if ($editing) @method('PUT') @endif
        <section class="card card-pad space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="name" :label="__('Name')" :value="$user->name" required maxlength="120" />
                <x-field name="email" type="email" :label="__('Email')" :value="$user->email" dir="ltr" required maxlength="190" />
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
                <x-field name="password" type="password" :label="$editing ? __('New password (optional)') : __('Password')" autocomplete="new-password" :required="! $editing" />
                <x-field name="password_confirmation" type="password" :label="__('Confirm password')" autocomplete="new-password" :required="! $editing" />
            </div>
            <x-toggle name="is_active" :label="__('Account active')" :checked="$user->is_active ?? true" :hint="__('Disabled accounts are signed out and cannot log in.')" />
        </section>

        <section class="card card-pad">
            <h2 class="font-bold">{{ __('Role') }}</h2>
            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                @foreach ([Role::Admin->value => [__('Admin / Reviewer'), __('Only the permissions selected below.')], Role::SuperAdmin->value => [__('Super Admin'), __('Full access, including admins, settings, rounds and audit log.')]] as $v => [$t, $h])
                    <label class="flex cursor-pointer gap-3 rounded-xl border border-line bg-ink-900 p-4 has-[:checked]:border-brand-500/60 has-[:checked]:bg-brand-500/[0.06]">
                        <input type="radio" name="role" value="{{ $v }}" x-model="role" class="mt-1 accent-brand-500">
                        <span><span class="block text-sm font-medium">{{ $t }}</span><span class="mt-0.5 block text-xs text-fg-subtle">{{ $h }}</span></span>
                    </label>
                @endforeach
            </div>
            @error('role')<p class="field-error">{{ $message }}</p>@enderror

            <fieldset class="mt-6" x-show="role === 'admin'" x-collapse>
                <legend class="text-sm font-semibold">{{ __('Permissions') }}</legend>
                <p class="mt-1 text-xs text-fg-subtle">{{ __('Starting rounds, resetting, settings and admin management are never available to reviewers.') }}</p>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    @foreach (Permission::cases() as $p)
                        <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-line bg-ink-900 px-4 text-sm">
                            <input type="checkbox" name="permissions[]" value="{{ $p->value }}" @checked(in_array($p->value, $selected, true)) class="checkbox"> {{ $p->label() }}
                        </label>
                    @endforeach
                </div>
            </fieldset>
        </section>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <a href="{{ route('admin.users.index') }}" class="btn btn-ghost">{{ __('Cancel') }}</a>
            <button class="btn btn-primary">{{ $editing ? __('Save changes') : __('Create account') }}</button>
        </div>
    </form>

    @if ($editing && ! $user->is(auth()->user()))
        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="mt-8 max-w-3xl rounded-[var(--radius-card)] border border-danger/25 p-5" onsubmit="return confirm(@js(__('Delete this admin account?')))">
            @csrf @method('DELETE')
            <p class="font-bold text-danger">{{ __('Delete account') }}</p>
            <p class="mt-1 text-sm text-fg-muted">{{ __('Their past actions stay in the audit log.') }}</p>
            <button class="btn btn-danger btn-sm mt-4"><x-icon name="trash" class="size-4" />{{ __('Delete') }}</button>
        </form>
    @endif
</x-layouts.admin>
