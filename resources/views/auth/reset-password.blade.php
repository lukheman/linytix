<x-layouts.guest title="Reset Password — LinyTix" type="auth">
    <div class="login-container">
        <div class="login-card">
            <div class="brand-logo">
                <div class="icon-wrapper">
                    <i class="fas fa-lock-open"></i>
                </div>
                <h1>Reset Password</h1>
                <p>Buat password baru untuk akun Anda</p>
            </div>

            <form method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="form-floating position-relative">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" value="{{ old('email', $email) }}"
                        class="form-control @error('email') is-invalid @enderror" id="email"
                        placeholder="Email Address" autocomplete="email" required>
                    <label for="email">Email Address</label>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-floating position-relative">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password"
                        class="form-control @error('password') is-invalid @enderror" id="password"
                        placeholder="Password Baru" autocomplete="new-password" required>
                    <label for="password">Password Baru</label>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-floating position-relative">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" name="password_confirmation" class="form-control"
                        id="password_confirmation" placeholder="Konfirmasi Password"
                        autocomplete="new-password" required>
                    <label for="password_confirmation">Konfirmasi Password</label>
                </div>

                <button type="submit" class="btn btn-login mt-3">
                    Simpan Password Baru <i class="fas fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>
</x-layouts.guest>
