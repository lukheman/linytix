<x-layouts.public
    :settings="$settings"
    :title="'Akses Pesanan — '.($settings['site_name'] ?? 'LinyTix')"
>
    <section class="section" style="padding-top: 8.5rem;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="card-clean p-5 text-center">
                        <div class="icon-box mx-auto mb-3"><i class="fa-solid fa-link-slash"></i></div>
                        <h1 class="h3">Tautan tidak valid atau kedaluwarsa</h1>
                        <p class="text-secondary mb-4">
                            Tautan akses pesanan hanya berlaku sampai 30 hari setelah event berakhir.
                            Silakan minta tautan baru dengan nomor order dan email pembeli.
                        </p>
                        <a href="{{ route('guest.orders.lookup') }}" class="btn btn-main">Minta tautan baru</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
