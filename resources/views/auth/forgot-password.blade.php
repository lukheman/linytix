<x-layouts.guest title="Lupa Password — LinyTix" type="auth">
    <div class="login-container">
        <div class="login-card">
            <div class="brand-logo">
                <div class="icon-wrapper">
                    <i class="fas fa-key"></i>
                </div>
                <h1>Lupa Password?</h1>
                <p>Masukkan email Anda untuk menerima tautan reset</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <div class="form-floating position-relative">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" value="{{ old('email') }}"
                        class="form-control @error('email') is-invalid @enderror" id="email"
                        placeholder="Email Address" autocomplete="email" required autofocus>
                    <label for="email">Email Address</label>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-login mt-3">
                    Kirim Tautan Reset <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="signup-link mt-4">
                Ingat password Anda? <a href="{{ route('login') }}">Masuk</a>
            </div>
        </div>
    </div>
</x-layouts.guest>
