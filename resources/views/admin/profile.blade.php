<x-layouts.app title="Profile - LinyTix">
    @php
        $user = Auth::user();
        $showPasswordSection = $errors->has('current_password') || $errors->has('password');
    @endphp

    {{-- Page Header --}}
    <x-layout.page-header title="Profile" subtitle="Kelola informasi akun Anda">
        <x-slot:actions>
            <x-ui.badge variant="{{ $user->email_verified_at ? 'success' : 'warning' }}" icon="{{ $user->email_verified_at ? 'fas fa-user-check' : 'fas fa-clock' }}">
                {{ $user->email_verified_at ? 'Terverifikasi' : 'Belum Terverifikasi' }}
            </x-ui.badge>
        </x-slot:actions>
    </x-layout.page-header>

    <div class="row g-4">
        {{-- Profile Info Card --}}
        <div class="col-lg-4">
            <x-layout.modern-card class="text-center">
                {{-- Avatar Section --}}
                <div class="position-relative d-inline-block mb-3">
                    @if($user->hasAvatar())
                        <img src="{{ $user->avatarUrl() }}" alt="Avatar" class="rounded-circle border border-4 border-primary"
                            style="width: 120px; height: 120px; object-fit: cover;">
                    @else
                        <div class="user-avatar mx-auto" style="width: 120px; height: 120px; font-size: 3rem;">
                            {{ $user->initials() }}
                        </div>
                    @endif
                </div>

                <h4 class="text-body fw-semibold">{{ $user->name }}</h4>
                <p class="text-muted mb-3">{{ $user->email }}</p>
                <x-ui.badge variant="primary" icon="fas fa-user-shield">Administrator</x-ui.badge>

                <hr class="my-4">

                <div class="text-start">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Bergabung</span>
                        <span class="text-body">{{ $user->created_at->format('d M Y') }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Terakhir diperbarui</span>
                        <span class="text-body">{{ $user->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </x-layout.modern-card>
        </div>

        {{-- Edit Profile Card --}}
        <div class="col-lg-8">
            {{-- Avatar Upload --}}
            <x-layout.modern-card class="mb-4">
                <div class="preview-title d-flex align-items-center gap-2">
                    <i class="fas fa-camera text-secondary"></i>
                    Foto Profil
                </div>

                <div class="d-flex align-items-center gap-4">
                    {{-- Preview --}}
                    <div class="position-relative">
                        <img id="avatar-preview"
                            src="{{ $user->avatarUrl() ?? '' }}"
                            data-original-src="{{ $user->avatarUrl() ?? '' }}"
                            alt="Avatar"
                            class="rounded-circle border border-3 border-primary"
                            style="width: 80px; height: 80px; object-fit: cover; {{ $user->hasAvatar() ? '' : 'display: none;' }}">

                        <div id="avatar-initials" class="user-avatar"
                            style="width: 80px; height: 80px; font-size: 2rem; {{ $user->hasAvatar() ? 'display: none;' : '' }}">
                            {{ $user->initials() }}
                        </div>
                    </div>

                    <div class="flex-grow-1">
                        <form method="POST" action="{{ route('admin.profile.avatar') }}" enctype="multipart/form-data">
                            @csrf

                            <input type="file" name="avatar" id="avatar-upload" class="d-none" accept="image/*">

                            <div class="d-flex gap-2 flex-wrap">
                                <label for="avatar-upload" class="btn btn-modern btn-primary-modern"
                                    style="cursor: pointer;">
                                    <i class="fas fa-upload me-2"></i> Pilih Foto
                                </label>

                                <x-ui.button type="submit" variant="success" icon="fas fa-check">
                                    Simpan
                                </x-ui.button>

                                <x-ui.button type="button" variant="secondary" icon="fas fa-times"
                                    onclick="resetAvatarSelection()">
                                    Batal
                                </x-ui.button>
                            </div>

                            @error('avatar')
                                <div class="text-danger mt-2 small">{{ $message }}</div>
                            @enderror
                        </form>

                        @if($user->hasAvatar())
                            <form method="POST" action="{{ route('admin.profile.avatar.destroy') }}" class="mt-2"
                                onsubmit="return confirm('Hapus foto profil?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-modern btn-danger btn-sm">
                                    <i class="fas fa-trash me-2"></i> Hapus Foto
                                </button>
                            </form>
                        @endif

                        <p class="text-muted mb-0 mt-2 small">
                            <i class="fas fa-info-circle me-1"></i>
                            Format: JPG, PNG, GIF. Maksimal 2MB.
                        </p>
                    </div>
                </div>
            </x-layout.modern-card>

            {{-- Profile Information --}}
            <x-layout.modern-card class="mb-4">
                <div class="preview-title d-flex align-items-center gap-2">
                    <i class="fas fa-user-edit text-primary"></i>
                    Informasi Profile
                </div>

                <form method="POST" action="{{ route('admin.profile.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <x-form.input
                                id="name"
                                name="name"
                                label="Nama Lengkap"
                                :value="old('name', $user->name)"
                                placeholder="Masukkan nama lengkap"
                                required
                                :error="$errors->first('name')"
                            />
                        </div>

                        <div class="col-md-6">
                            <x-form.input
                                type="email"
                                id="email"
                                name="email"
                                label="Email"
                                :value="old('email', $user->email)"
                                placeholder="Masukkan email"
                                required
                                :error="$errors->first('email')"
                            />
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <x-ui.button type="submit" variant="primary" icon="fas fa-save">
                            Simpan Perubahan
                        </x-ui.button>
                    </div>
                </form>
            </x-layout.modern-card>

            {{-- Change Password --}}
            <x-layout.modern-card>
                <div class="preview-title d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-lock text-warning"></i>
                        Ubah Password
                    </div>
                    <button type="button" id="password-toggle" class="btn btn-modern btn-sm {{ $showPasswordSection ? 'btn-danger' : 'btn-secondary' }}"
                        onclick="togglePasswordSection()">
                        {{ $showPasswordSection ? 'Batal' : 'Ubah Password' }}
                    </button>
                </div>

                <div id="password-section" class="{{ $showPasswordSection ? '' : 'd-none' }}">
                    <form method="POST" action="{{ route('admin.profile.password') }}" class="mt-3">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-12">
                                <x-form.input
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    label="Password Saat Ini"
                                    placeholder="Masukkan password saat ini"
                                    required
                                    :error="$errors->first('current_password')"
                                />
                            </div>

                            <div class="col-md-6">
                                <x-form.input
                                    type="password"
                                    id="password"
                                    name="password"
                                    label="Password Baru"
                                    placeholder="Masukkan password baru"
                                    required
                                    :error="$errors->first('password')"
                                />
                            </div>

                            <div class="col-md-6">
                                <x-form.input
                                    type="password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    label="Konfirmasi Password"
                                    placeholder="Konfirmasi password baru"
                                    required
                                />
                            </div>
                        </div>

                        <x-ui.alert variant="info" class="mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            Password harus minimal 8 karakter dan mengandung huruf dan angka.
                        </x-ui.alert>

                        <div class="d-flex justify-content-end mt-4">
                            <x-ui.button type="submit" variant="warning" icon="fas fa-key">
                                Perbarui Password
                            </x-ui.button>
                        </div>
                    </form>
                </div>

                <p id="password-hint" class="text-muted mb-0 {{ $showPasswordSection ? 'd-none' : '' }}">
                    <i class="fas fa-shield-alt me-2"></i>
                    Klik tombol "Ubah Password" untuk memperbarui password Anda.
                </p>
            </x-layout.modern-card>
        </div>
    </div>

    @push('scripts')
        <script>
            const avatarInput = document.getElementById('avatar-upload');
            const avatarPreview = document.getElementById('avatar-preview');
            const avatarInitials = document.getElementById('avatar-initials');

            if (avatarInput) {
                avatarInput.addEventListener('change', function (event) {
                    const file = event.target.files[0];

                    if (!file) {
                        return;
                    }

                    avatarPreview.src = URL.createObjectURL(file);
                    avatarPreview.style.display = '';
                    avatarInitials.style.display = 'none';
                });
            }

            function resetAvatarSelection() {
                if (!avatarInput) {
                    return;
                }

                avatarInput.value = '';

                const originalSrc = avatarPreview.dataset.originalSrc;

                if (originalSrc) {
                    avatarPreview.src = originalSrc;
                    avatarPreview.style.display = '';
                    avatarInitials.style.display = 'none';
                } else {
                    avatarPreview.removeAttribute('src');
                    avatarPreview.style.display = 'none';
                    avatarInitials.style.display = '';
                }
            }

            function togglePasswordSection() {
                const section = document.getElementById('password-section');
                const hint = document.getElementById('password-hint');
                const button = document.getElementById('password-toggle');
                const isHidden = section.classList.contains('d-none');

                section.classList.toggle('d-none', !isHidden);
                hint.classList.toggle('d-none', isHidden);
                button.textContent = isHidden ? 'Batal' : 'Ubah Password';
                button.classList.toggle('btn-secondary', !isHidden);
                button.classList.toggle('btn-danger', isHidden);
            }
        </script>
    @endpush
</x-layouts.app>
