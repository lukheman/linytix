<x-layouts.auth
    title="Reset Password — LinyTix"
    description="Buat password baru untuk akun LinyTix Anda."
    eyebrow="Pemulihan akun"
    heading="Atur password baru."
    subheading="Gunakan password yang kuat dan belum pernah dipakai untuk akun ini."
>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
            <label class="auth-label" for="email">Email</label>
            <input type="email" name="email" value="{{ old('email', $email) }}"
                class="auth-input @error('email') is-invalid @enderror" id="email"
                placeholder="nama@email.com" autocomplete="email" required>
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="auth-label" for="password">Password baru</label>
            <div class="auth-password">
                <input type="password" name="password"
                    class="auth-input @error('password') is-invalid @enderror" id="password"
                    placeholder="Minimal 8 karakter" autocomplete="new-password" required>
                <button type="button" class="auth-toggle" onclick="togglePassword('password', 'toggleIcon1')"
                    aria-label="Tampilkan password" aria-pressed="false">
                    <i class="fa-solid fa-eye" id="toggleIcon1"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label class="auth-label" for="password_confirmation">Konfirmasi password</label>
            <div class="auth-password">
                <input type="password" name="password_confirmation"
                    class="auth-input @error('password_confirmation') is-invalid @enderror"
                    id="password_confirmation" placeholder="Ulangi password baru"
                    autocomplete="new-password" required>
                <button type="button" class="auth-toggle"
                    onclick="togglePassword('password_confirmation', 'toggleIcon2')"
                    aria-label="Tampilkan konfirmasi password" aria-pressed="false">
                    <i class="fa-solid fa-eye" id="toggleIcon2"></i>
                </button>
            </div>
            @error('password_confirmation')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-main w-100">
            Simpan password baru <i class="fas fa-arrow-right ms-1"></i>
        </button>
    </form>

    <p class="auth-note text-center mt-4 mb-0">
        <a href="{{ route('login') }}" class="text-link">Kembali ke halaman masuk</a>
    </p>
</x-layouts.auth>
