<x-layouts.app title="User Management - LinyTix">
    @php
        $indexQuery = array_filter([
            'q' => $search,
            'role' => $role,
            'status' => $status,
            'page' => request('page'),
        ], fn ($value) => $value !== null && $value !== '');

        $closeUrl = route('admin.users', $indexQuery);
        $hasFilter = $search !== '' || $role !== '' || $status !== '';
    @endphp

    {{-- Page Header --}}
    <x-layout.page-header title="User Management" subtitle="Cari pengguna, kelola status akun, dan atur akses pengguna.">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="fas fa-plus" :href="route('admin.users', $indexQuery + ['create' => 1])">
                Add User
            </x-ui.button>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Users Table Card --}}
    <x-layout.modern-card>
        <form method="GET" action="{{ route('admin.users') }}" class="row g-2 align-items-end mb-4">
            <div class="col-md-4">
                <label for="q" class="form-label small fw-semibold">Cari pengguna</label>
                <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Nama atau email">
            </div>

            <div class="col-md-3">
                <label for="role" class="form-label small fw-semibold">Role</label>
                <select id="role" name="role" class="form-select">
                    <option value="">Semua role</option>
                    @foreach ($roles as $roleOption)
                        <option value="{{ $roleOption->name }}" @selected($role === $roleOption->name)>
                            {{ $roleOption->label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label for="status" class="form-label small fw-semibold">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Semua status</option>
                    <option value="active" @selected($status === 'active')>Aktif</option>
                    <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
                @if ($hasFilter)
                    <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-semibold text-body">All Users</h5>
            <span class="small text-muted">{{ $users->total() }} pengguna</span>
        </div>

        {{-- Users Table --}}
        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="user-avatar">{{ $user->initials() }}</div>
                                    <div>
                                        <div class="fw-semibold text-body">
                                            {{ $user->name }}
                                            @if ($user->id === $currentUserId)
                                                <span class="badge text-bg-secondary ms-1">Anda</span>
                                            @endif
                                        </div>
                                        <small class="text-muted">ID: {{ $user->id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-secondary">{{ $user->email }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @forelse ($user->roles as $userRole)
                                        <x-ui.badge :variant="$userRole->name === 'admin' ? 'danger' : 'info'">{{ $userRole->label }}</x-ui.badge>
                                    @empty
                                        <span class="small text-muted">Tanpa role</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                @if ($user->is_active)
                                    <x-ui.badge variant="success" icon="fas fa-check-circle">Aktif</x-ui.badge>
                                @else
                                    <x-ui.badge variant="danger" icon="fas fa-ban">Nonaktif</x-ui.badge>
                                @endif
                                <div class="small text-muted mt-1">
                                    {{ $user->email_verified_at ? 'Email terverifikasi' : 'Email belum diverifikasi' }}
                                </div>
                            </td>
                            <td class="text-muted">{{ $user->created_at->format('d M Y') }}</td>
                            <td>
                                @php
                                    $isSelf = $user->id === $currentUserId;
                                    $isLastAdmin = $user->is_active
                                        && $user->hasRole(\App\Models\Role::ADMIN)
                                        && $activeAdminCount <= 1;
                                    $blocked = $isSelf || $isLastAdmin;
                                @endphp

                                <div class="d-flex flex-wrap gap-1">
                                    <x-ui.btn-edit
                                        :href="route('admin.users', $indexQuery + ['edit' => $user->id])"
                                        tooltip="Edit user"
                                    />

                                    @if ($user->is_active)
                                        <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="0">
                                            <button type="submit" class="btn btn-sm btn-outline-warning" @disabled($blocked) title="{{ $isSelf ? 'Tidak dapat menonaktifkan akun sendiri' : ($isLastAdmin ? 'Admin terakhir tidak dapat dinonaktifkan' : 'Nonaktifkan user') }}">
                                                <i class="fas fa-user-slash"></i>
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="is_active" value="1">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Aktifkan user">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                        </form>
                                    @endif

                                    @if (! $isSelf)
                                        <x-ui.btn-delete
                                            :href="route('admin.users', $indexQuery + ['delete' => $user->id])"
                                            tooltip="Delete user"
                                        />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <x-ui.empty-state
                                    icon="fas fa-users"
                                    title="No users found"
                                    description="Try adjusting your search or filters."
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

            <p class="small text-muted mb-0">User baru otomatis mendapat role customer.</p>
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
