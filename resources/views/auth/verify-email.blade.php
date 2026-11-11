<x-layouts.guest title="Verifikasi Email — LinyTix" type="auth">
    <div class="login-container">
        <div class="login-card">
            <div class="brand-logo">
                <div class="icon-wrapper">
                    <i class="fas fa-envelope-circle-check"></i>
                </div>
                <h1>Verifikasi Email</h1>
                <p>Kami telah mengirim tautan verifikasi ke email Anda</p>
            </div>

            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            <p class="text-secondary mb-4">
                Klik tautan pada email untuk mengaktifkan verifikasi. Jika tidak menemukannya,
                periksa folder spam atau kirim ulang email verifikasi.
            </p>

            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="btn btn-login">
                    Kirim Ulang Email Verifikasi <i class="fas fa-paper-plane"></i>
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-outline-secondary w-100 rounded-pill">Keluar</button>
            </form>
        </div>
    </div>
</x-layouts.guest>
