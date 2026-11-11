<x-layouts.public
    :settings="$settings"
    title="Tiket tidak tersedia — {{ $settings['site_name'] ?? 'LinyTix' }}"
>
    <section class="section" style="padding-top: 8.5rem;">
        <div class="container">
            <div class="card-clean p-5 text-center mx-auto" style="max-width: 640px;">
                <div class="icon-box mx-auto mb-3"><i class="fa-regular fa-calendar-xmark"></i></div>
                <h1 class="h3">Tiket belum dapat dibeli</h1>
                <p class="text-secondary mb-4">Event ini sedang tidak menerima pembelian tiket. Silakan kembali ke halaman event atau jelajahi event lain.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('events.index') }}" class="btn btn-main">Jelajahi event</a>
                    <a href="{{ route('home') }}" class="btn btn-ghost">Beranda</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
