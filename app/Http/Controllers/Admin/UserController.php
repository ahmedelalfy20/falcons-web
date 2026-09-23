<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index()
    {
        $this->authorize('manage-admins');

        return view('admin.users.index', [
            'users' => User::whereIn('role', [Role::SuperAdmin->value, Role::Admin->value])->orderBy('role')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->authorize('manage-admins');

        return view('admin.users.form', ['user' => new User(['role' => Role::Admin, 'permissions' => Permission::reviewerDefaults(), 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $this->authorize('manage-admins');
        $data = $this->validated($request);
        $user = User::create($data);
        $this->audit->log('admin.created', $user, ['role' => $user->role->value, 'permissions' => $user->permissions]);

        return redirect()->route('admin.users.index')->with('success', __('Account created for :name.', ['name' => $user->name]));
    }

    public function edit(User $user)
    {
        $this->authorize('manage-admins');
        abort_unless($user->isStaff(), 404);

        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('manage-admins');
        abort_unless($user->isStaff(), 404);
        $data = $this->validated($request, $user);

        if ($user->is($request->user()) && ($data['role'] !== Role::SuperAdmin || ! $data['is_active'])) {
            throw ValidationException::withMessages(['role' => __('You cannot demote or deactivate your own account.')]);
        }
        if ($user->isSuperAdmin() && ($data['role'] !== Role::SuperAdmin || ! $data['is_active']) && $this->superAdminCount() <= 1) {
            throw ValidationException::withMessages(['role' => __('At least one active super admin is required.')]);
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $before = ['role' => $user->role->value, 'permissions' => $user->permissions, 'is_active' => $user->is_active];
        $user->update($data);
        $this->audit->log('admin.updated', $user, ['before' => $before, 'after' => ['role' => $user->role->value, 'permissions' => $user->permissions, 'is_active' => $user->is_active], 'password_changed' => isset($data['password'])]);

        return redirect()->route('admin.users.index')->with('success', __('Account updated.'));
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('manage-admins');
        abort_unless($user->isStaff(), 404);
        if ($user->is($request->user())) {
            return back()->with('error', __('You cannot delete your own account.'));
        }
        if ($user->isSuperAdmin() && $this->superAdminCount() <= 1) {
            return back()->with('error', __('At least one active super admin is required.'));
        }
        $this->audit->log('admin.deleted', $user, ['email' => $user->email, 'role' => $user->role->value]);
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', __('Account deleted.'));
    }

    private function superAdminCount(): int
    {
        return User::where('role', Role::SuperAdmin->value)->where('is_active', true)->count();
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $v = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in([Role::SuperAdmin->value, Role::Admin->value])],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(array_column(Permission::cases(), 'value'))],
            'is_active' => ['sometimes', 'boolean'],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ]);
        $role = Role::from($v['role']);

        return [
            'name' => $v['name'],
            'email' => strtolower($v['email']),
            'role' => $role,
            'permissions' => $role === Role::Admin ? array_values($v['permissions'] ?? []) : null,
            'is_active' => $request->boolean('is_active'),
            'password' => $v['password'] ?? null,
        ];
    }
}
