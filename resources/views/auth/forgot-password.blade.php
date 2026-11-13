<x-layouts.auth
    title="Lupa Password — LinyTix"
    description="Atur ulang password akun LinyTix melalui tautan aman yang dikirim ke email Anda."
    eyebrow="Pemulihan akun"
    heading="Lupa password?"
    subheading="Masukkan email terdaftar. Kami kirimkan tautan aman untuk mengatur ulang password Anda."
>
    @if (session('status'))
        <div class="alert alert-success small" role="status">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-4">
            <label class="auth-label" for="email">Email</label>
            <input type="email" name="email" value="{{ old('email') }}"
                class="auth-input @error('email') is-invalid @enderror" id="email"
                placeholder="nama@email.com" autocomplete="email" required autofocus>
            @error('email')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-main w-100">
            Kirim tautan reset <i class="fas fa-arrow-right ms-1"></i>
        </button>
    </form>

    <p class="auth-note text-center mt-4 mb-0">
        Ingat password Anda? <a href="{{ route('login') }}" class="text-link">Masuk</a>
    </p>
</x-layouts.auth>
