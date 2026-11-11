<x-layouts.app title="User Management - LinyTix">
    @php
        $indexQuery = array_filter([
            'q' => $search,
            'page' => request('page'),
        ], fn ($value) => $value !== null && $value !== '');

        $closeUrl = route('admin.users', $indexQuery);
    @endphp

    {{-- Page Header --}}
    <x-layout.page-header title="User Management" subtitle="Manage all users in the system">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="fas fa-plus" :href="route('admin.users', $indexQuery + ['create' => 1])">
                Add User
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    {{-- Users Table Card --}}
    <x-layout.modern-card>
        {{-- Search and Filters --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0 fw-semibold text-body">All Users</h5>
            <form method="GET" action="{{ route('admin.users') }}" style="max-width: 300px; width: 100%;">
                <x-form.input
                    name="q"
                    :value="$search"
                    placeholder="Search users..."
                    icon="fas fa-search"
                    class="mb-0"
                    onchange="this.form.submit()"
                />
            </form>
        </div>

        {{-- Users Table --}}
        <div class="table-responsive">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="user-avatar">{{ $user->initials() }}</div>
                                    <div>
                                        <div class="fw-semibold text-body">{{ $user->name }}</div>
                                        <small class="text-muted">ID: {{ $user->id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-secondary">{{ $user->email }}</td>
                            <td class="text-muted">{{ $user->created_at->format('M d, Y') }}</td>
                            <td>
                                @if($user->email_verified_at)
                                    <x-ui.badge variant="success" icon="fas fa-check-circle">Verified</x-ui.badge>
                                @else
                                    <x-ui.badge variant="warning" icon="fas fa-clock">Pending</x-ui.badge>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <x-ui.btn-edit
                                        :href="route('admin.users', $indexQuery + ['edit' => $user->id])"
                                        tooltip="Edit user"
                                    />
                                    <x-ui.btn-delete
                                        :href="route('admin.users', $indexQuery + ['delete' => $user->id])"
                                        tooltip="Delete user"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                <x-ui.empty-state
                                    icon="fas fa-users"
                                    title="No users found"
                                    description="Try adjusting your search query or add a new user."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($users->hasPages())
            <div class="d-flex justify-content-end mt-4">
                {{ $users->links() }}
            </div>
        @endif
    </x-layout.modern-card>

    {{-- Create User Modal --}}
    @if ($showCreateModal && ! $editingUser && ! $deletingUser)
        <x-layout.modal-form
            title="Create New User"
            :action="route('admin.users.store')"
            :close-url="$closeUrl"
            submit-label="Create User"
        >
            <x-form.input
                id="create-name"
                name="name"
                label="Name"
                :value="old('name')"
                placeholder="Enter full name"
                required
                :error="$errors->first('name')"
            />

            <x-form.input
                type="email"
                id="create-email"
                name="email"
                label="Email"
                :value="old('email')"
                placeholder="Enter email address"
                required
                :error="$errors->first('email')"
            />

            <x-form.input
                type="password"
                id="create-password"
                name="password"
                label="Password"
                placeholder="Enter password"
                required
                :error="$errors->first('password')"
            />

            <x-form.input
                type="password"
                id="create-password-confirmation"
                name="password_confirmation"
                label="Confirm Password"
                placeholder="Confirm password"
                required
            />
        </x-layout.modal-form>
    @endif

    {{-- Edit User Modal --}}
    @if ($editingUser && ! $deletingUser)
        <x-layout.modal-form
            title="Edit User"
            :action="route('admin.users.update', $editingUser)"
            method="PUT"
            :close-url="$closeUrl"
            submit-label="Update User"
        >
            <x-form.input
                id="edit-name"
                name="name"
                label="Name"
                :value="old('name', $editingUser->name)"
                placeholder="Enter full name"
                required
                :error="$errors->first('name')"
            />

            <x-form.input
                type="email"
                id="edit-email"
                name="email"
                label="Email"
                :value="old('email', $editingUser->email)"
                placeholder="Enter email address"
                required
                :error="$errors->first('email')"
            />

            <x-form.input
                type="password"
                id="edit-password"
                name="password"
                label="Password"
                placeholder="Enter new password"
                hint="(leave blank to keep current)"
                :error="$errors->first('password')"
            />

            <x-form.input
                type="password"
                id="edit-password-confirmation"
                name="password_confirmation"
                label="Confirm Password"
                placeholder="Confirm password"
            />
        </x-layout.modal-form>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if ($deletingUser)
        <x-ui.confirm-modal
            :show="true"
            title="Confirm Delete"
            :message="'Are you sure you want to delete ' . $deletingUser->name . '? This action cannot be undone.'"
            confirm-text="Delete User"
            :action="route('admin.users.destroy', $deletingUser)"
            method="DELETE"
            :cancel-url="$closeUrl"
            variant="danger"
            icon="fas fa-exclamation-triangle"
        >
            <x-slot:confirmButton>
                <i class="fas fa-trash-alt me-2"></i>Delete User
            </x-slot:confirmButton>
        </x-ui.confirm-modal>
    @endif
</x-layouts.app>
