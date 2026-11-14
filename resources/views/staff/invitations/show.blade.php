<x-layouts.auth
    title="Undangan Staff — LinyTix"
    description="Terima undangan menjadi staff event di LinyTix."
    eyebrow="Undangan staff"
    heading="Undangan menjadi staff."
    subheading="Periksa detail penugasan di bawah, lalu terima undangan untuk mulai memindai tiket."
>
    @php
        $timezone = $event?->timezoneLabel() ?? 'Asia/Jakarta';
        $schedule = $event?->startsAtLocal()?->translatedFormat('l, d F Y, H.i');
        $expires = $invitation->expires_at?->copy()->setTimezone($timezone);
        $status = $invitation->status();
    @endphp

    @if ($status === \App\Models\EventStaffInvitation::STATUS_ACCEPTED)
        <div class="alert alert-success small" role="status">
            Undangan ini sudah diterima. Anda dapat langsung membuka halaman scan.
        </div>
    @elseif ($status === \App\Models\EventStaffInvitation::STATUS_EXPIRED)
        <div class="alert alert-warning small" role="status">
            Undangan ini sudah kedaluwarsa. Minta organizer mengirim ulang undangan.
        </div>
    @elseif ($status === \App\Models\EventStaffInvitation::STATUS_REVOKED)
        <div class="alert alert-danger small" role="status">
            Undangan ini telah dibatalkan oleh organizer.
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger small" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="border rounded p-3 mb-4">
        <div class="text-uppercase small text-muted mb-1">Event</div>
        <div class="fw-semibold fs-5">{{ $event?->title ?? '-' }}</div>

        <dl class="row small mt-3 mb-0">
            <dt class="col-5 col-sm-4 text-muted fw-normal">Organizer</dt>
            <dd class="col-7 col-sm-8 mb-2">{{ $event?->organizer?->name ?? '-' }}</dd>

            <dt class="col-5 col-sm-4 text-muted fw-normal">Jadwal</dt>
            <dd class="col-7 col-sm-8 mb-2">
                {{ $schedule ? $schedule.' '.$timezone : 'Belum ditentukan' }}
            </dd>

            <dt class="col-5 col-sm-4 text-muted fw-normal">Lokasi</dt>
            <dd class="col-7 col-sm-8 mb-2">
                {{ $event?->is_online ? 'Acara daring' : ($event?->venue?->name ?? 'Belum ditentukan') }}
            </dd>

            <dt class="col-5 col-sm-4 text-muted fw-normal">Gate</dt>
            <dd class="col-7 col-sm-8 mb-2">
                <span class="badge text-bg-info">{{ $invitation->gate }}</span>
            </dd>

            <dt class="col-5 col-sm-4 text-muted fw-normal">Email</dt>
            <dd class="col-7 col-sm-8 mb-2">{{ $invitation->email }}</dd>

            <dt class="col-5 col-sm-4 text-muted fw-normal">Berlaku sampai</dt>
            <dd class="col-7 col-sm-8 mb-0">
                {{ $expires?->translatedFormat('d F Y, H.i') }} {{ $timezone }}
            </dd>
        </dl>
    </div>

    @if ($status === \App\Models\EventStaffInvitation::STATUS_PENDING)
        @if ($isRecipient)
            <form method="POST" action="{{ $acceptRoute }}">
                @csrf
                <button type="submit" class="btn btn-main w-100">
                    Terima undangan <i class="fas fa-arrow-right ms-1"></i>
                </button>
            </form>
        @elseif (auth()->user())
            <div class="alert alert-warning small" role="status">
                Anda sedang masuk sebagai <strong>{{ auth()->user()->email }}</strong>, sedangkan undangan ini
                ditujukan untuk <strong>{{ $invitation->email }}</strong>. Keluar dan masuk dengan akun yang sesuai.
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-ghost w-100">Keluar dari akun</button>
            </form>
        @elseif ($registeredUser)
            <a href="{{ $loginUrl }}" class="btn btn-main w-100">
                Masuk untuk menerima <i class="fas fa-arrow-right ms-1"></i>
            </a>
        @else
            <a href="{{ $registerUrl }}" class="btn btn-main w-100">
                Buat akun &amp; terima undangan <i class="fas fa-arrow-right ms-1"></i>
            </a>
        @endif
    @elseif ($status === \App\Models\EventStaffInvitation::STATUS_ACCEPTED && $isRecipient)
        <a href="{{ route('scan.index') }}" class="btn btn-main w-100">
            Buka halaman scan <i class="fas fa-arrow-right ms-1"></i>
        </a>
    @endif

    <p class="auth-note text-center mt-4 mb-0">
        Butuh bantuan? <a href="{{ route('faq') }}" class="text-link">Baca FAQ</a>.
    </p>
</x-layouts.auth>
