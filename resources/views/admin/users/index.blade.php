<x-layouts.admin :heading="__('Admins')" :subheading="__('Super admins have full access. Reviewers only get the permissions you grant.')">
    <x-slot:actions><a href="{{ route('admin.users.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" />{{ __('Add admin') }}</a></x-slot:actions>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Role') }}</th><th>{{ __('Permissions') }}</th><th>{{ __('Status') }}</th><th>{{ __('Last login') }}</th><th></th></tr></thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td><div class="font-semibold">{{ $user->name }} @if ($user->is(auth()->user()))<span class="text-xs font-normal text-fg-subtle">({{ __('you') }})</span>@endif</div><div class="text-xs text-fg-subtle" dir="ltr">{{ $user->email }}</div></td>
                        <td><span class="badge {{ $user->isSuperAdmin() ? 'badge-running' : 'badge-neutral' }}">{{ $user->role->label() }}</span></td>
                        <td class="max-w-xs text-xs text-fg-muted">
                            @if ($user->isSuperAdmin()) {{ __('Everything') }}
                            @else {{ collect($user->permissions ?? [])->map(fn ($p) => \App\Enums\Permission::tryFrom($p)?->label())->filter()->join(', ') ?: __('None') }} @endif
                        </td>
                        <td><span class="badge {{ $user->is_active ? 'badge-accepted' : 'badge-rejected' }}">{{ $user->is_active ? __('Active') : __('Disabled') }}</span></td>
                        <td class="text-fg-muted">{{ $user->last_login_at?->diffForHumans() ?? __('Never') }}</td>
                        <td class="text-end"><a href="{{ route('admin.users.edit', $user) }}" class="btn btn-ghost btn-sm"><x-icon name="edit" class="size-4" />{{ __('Edit') }}</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.admin>
