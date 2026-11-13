<x-layouts.auth
    title="Daftar — LinyTix"
    description="Buat akun LinyTix untuk membeli tiket event, menyimpan pesanan, dan mendaftarkan event sebagai organizer."
    eyebrow="Akun baru"
    heading="Buat akun LinyTix."
    subheading="Satu akun untuk membeli tiket, menyimpan pesanan, dan mendaftarkan event sebagai organizer."
>
    <form method="POST" action="{{ route('register.store') }}" id="register-form">
        @csrf

        @if (request('intent') === 'organizer' || old('intent') === 'organizer')
            <input type="hidden" name="intent" value="organizer">
            <div class="alert alert-info small" role="status">
                <strong>Anda mendaftar untuk menjadi organizer.</strong>
                Setelah akun dibuat dan email diverifikasi, lanjutkan pengajuan profil organizer dari dashboard akun.
                <a href="{{ route('organizer.guide') }}" class="text-link">Baca panduan</a>.
            </div>
        @endif

        <div class="mb-3">
            <label class="auth-label" for="name">Nama lengkap</label>
            <input type="text" name="name" value="{{ old('name') }}"
                class="auth-input @error('name') is-invalid @enderror" id="name"
                placeholder="Nama sesuai identitas" autocomplete="name" required autofocus>
            @error('name')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="auth-label" for="email">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                class="auth-input @error('email') is-invalid @enderror" id="email"
                placeholder="nama@email.com" autocomplete="email" required>
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="auth-label" for="password">Password</label>
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

        <div class="mb-3">
            <label class="auth-label" for="password_confirmation">Konfirmasi password</label>
            <div class="auth-password">
                <input type="password" name="password_confirmation"
                    class="auth-input @error('password_confirmation') is-invalid @enderror"
                    id="password_confirmation" placeholder="Ulangi password"
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

        <div class="form-check mb-4">
            <input class="form-check-input @error('agree_terms') is-invalid @enderror" type="checkbox"
                name="agree_terms" value="1" id="agree_terms" @checked(old('agree_terms')) required>
            <label class="form-check-label small" for="agree_terms">
                Saya menyetujui <a href="{{ route('info.terms') }}" class="text-link">Syarat &amp; Ketentuan</a>
                dan <a href="{{ route('info.privacy') }}" class="text-link">Kebijakan Privasi</a>.
            </label>
            @error('agree_terms')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-main w-100" id="register-button">
            <span id="register-text">Buat akun <i class="fas fa-arrow-right ms-1"></i></span>
            <span id="register-loading" class="d-none">
                <i class="fas fa-spinner fa-spin me-2"></i>Membuat akun…
            </span>
        </button>
    </form>

    <div class="auth-divider">atau</div>

    <a href="{{ route('auth.google.redirect') }}"
        class="btn btn-ghost w-100 d-inline-flex align-items-center justify-content-center gap-2">
        <i class="fab fa-google"></i> Daftar dengan Google
    </a>

    <p class="auth-note text-center mt-4 mb-0">
        Sudah punya akun? <a href="{{ route('login') }}" class="text-link">Masuk</a>
    </p>

    @push('scripts')
        <script>
            document.getElementById('register-form').addEventListener('submit', function () {
                document.getElementById('register-button').disabled = true;
                document.getElementById('register-text').classList.add('d-none');
                document.getElementById('register-loading').classList.remove('d-none');
            });
        </script>
    @endpush
</x-layouts.auth>
