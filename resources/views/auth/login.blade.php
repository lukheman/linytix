<x-layouts.auth
    title="Masuk — LinyTix"
    description="Masuk ke akun LinyTix untuk membeli tiket event, membuka e-tiket, dan mengelola pesanan Anda."
    eyebrow="Akun"
    heading="Selamat datang kembali."
    subheading="Masuk untuk melanjutkan pembelian tiket, membuka e-tiket, dan mengelola event Anda."
>
    <form method="POST" action="{{ route('login.store') }}" id="login-form">
        @csrf

        @if (request('redirect'))
            <input type="hidden" name="redirect" value="{{ request('redirect') }}">
        @endif

        @if (session('status'))
            <div class="alert alert-success small" role="status">{{ session('status') }}</div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning small" role="status">{{ session('warning') }}</div>
        @endif

        <div class="mb-3">
            <label class="auth-label" for="email">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                class="auth-input @error('email') is-invalid @enderror" id="email"
                placeholder="nama@email.com" autocomplete="email" required autofocus>
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="auth-label" for="password">Password</label>
            <div class="auth-password">
                <input type="password" name="password"
                    class="auth-input @error('password') is-invalid @enderror" id="password"
                    placeholder="Masukkan password" autocomplete="current-password" required>
                <button type="button" class="auth-toggle" onclick="togglePassword('password', 'toggleIcon')"
                    aria-label="Tampilkan password" aria-pressed="false">
                    <i class="fa-solid fa-eye" id="toggleIcon"></i>
                </button>
            </div>
            @error('password')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="remember" value="1"
                    id="remember" @checked(old('remember'))>
                <label class="form-check-label small" for="remember">Ingat saya</label>
            </div>
            <a href="{{ route('password.request') }}" class="text-link small">Lupa password?</a>
        </div>

        <button type="submit" class="btn btn-main w-100" id="login-button">
            <span id="login-text">Masuk <i class="fas fa-arrow-right ms-1"></i></span>
            <span id="login-loading" class="d-none">
                <i class="fas fa-spinner fa-spin me-2"></i>Memproses…
            </span>
        </button>
    </form>

    <div class="auth-divider">atau</div>

    <a href="{{ route('auth.google.redirect') }}"
        class="btn btn-ghost w-100 d-inline-flex align-items-center justify-content-center gap-2">
        <i class="fab fa-google"></i> Masuk dengan Google
    </a>

    <p class="auth-note text-center mt-4 mb-0">
        Belum punya akun? <a href="{{ route('register') }}" class="text-link">Daftar sekarang</a>
    </p>

    @push('scripts')
        <script>
            document.getElementById('login-form').addEventListener('submit', function () {
                document.getElementById('login-button').disabled = true;
                document.getElementById('login-text').classList.add('d-none');
                document.getElementById('login-loading').classList.remove('d-none');
            });
        </script>
    @endpush
</x-layouts.auth>
