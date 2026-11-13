<x-layouts.auth
    title="Verifikasi Email — LinyTix"
    description="Verifikasi email akun LinyTix Anda untuk mengaktifkan pembelian tiket dan pendaftaran organizer."
    eyebrow="Verifikasi akun"
    heading="Verifikasi email Anda."
    subheading="Kami telah mengirim tautan verifikasi ke email terdaftar. Buka tautan tersebut untuk mengaktifkan akun."
>
    @if (session('status'))
        <div class="alert alert-success small" role="status">{{ session('status') }}</div>
    @endif

    <p class="text-secondary small mb-4">
        Jika email tidak ditemukan, periksa folder spam atau kirim ulang email verifikasi.
        Email terverifikasi diperlukan sebelum Anda dapat mengajukan profil organizer.
    </p>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-main w-100">
            Kirim ulang email verifikasi <i class="fas fa-paper-plane ms-1"></i>
        </button>
    </form>

    <div class="auth-divider">atau</div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-ghost w-100">Keluar dari akun</button>
    </form>
</x-layouts.auth>
