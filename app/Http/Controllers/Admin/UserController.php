<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display the user management page.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $role = trim((string) $request->query('role', ''));
        $status = trim((string) $request->query('status', ''));

        $roles = Role::query()->orderBy('name')->get();

        if ($role !== '' && ! $roles->contains(fn (Role $candidate) => $candidate->name === $role)) {
            $role = '';
        }

        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = '';
        }

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($role !== '', fn ($query) => $query->whereHas(
                'roles',
                fn ($roles) => $roles->where('name', $role),
            ))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        $editingUser = $request->filled('edit')
            ? User::findOrFail($request->integer('edit'))
            : null;

        $deletingUser = $request->filled('delete')
            ? User::findOrFail($request->integer('delete'))
            : null;

        return view('admin.user-management', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
            'roles' => $roles,
            'editingUser' => $editingUser,
            'deletingUser' => $deletingUser,
            'showCreateModal' => $request->boolean('create'),
            'currentUserId' => $request->user()->getKey(),
            'activeAdminCount' => User::query()
                ->where('is_active', true)
                ->whereHas('roles', fn ($query) => $query->where('name', Role::ADMIN))
                ->count(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request, UserManagementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $service->create($validated, $request->user(), $request->ip());

        return redirect()
            ->route('admin.users')
            ->with('success', 'User berhasil dibuat.');
    }

    /**
     * Update the specified user.
     */
    public function update(
        Request $request,
        User $user,
        UserManagementService $service,
    ): RedirectResponse {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['confirmed', Password::defaults()];
        }

        $validated = $request->validate($rules);

        $service->update(
            $user,
            $validated,
            $validated['password'] ?? null,
            $request->user(),
            $request->ip(),
        );

        return redirect()
            ->route('admin.users')
            ->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Activate or deactivate the specified user.
     */
    public function updateStatus(
        Request $request,
        User $user,
        UserManagementService $service,
    ): RedirectResponse {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $active = $request->boolean('is_active');

        $service->setActive($user, $active, $request->user(), $request->ip());

        return back()->with(
            'success',
            $active
                ? 'User '.$user->name.' berhasil diaktifkan.'
                : 'User '.$user->name.' berhasil dinonaktifkan.',
        );
    }

    /**
     * Delete the specified user.
     */
    public function destroy(
        Request $request,
        User $user,
        UserManagementService $service,
    ): RedirectResponse {
        $service->delete($user, $request->user(), $request->ip());

        return redirect()
            ->route('admin.users')
            ->with('success', 'User berhasil dihapus.');
    }
}
