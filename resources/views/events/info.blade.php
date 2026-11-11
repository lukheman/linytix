@php
    $pages = [
        'about' => [
            'title' => 'Tentang Kami',
            'intro' => 'Kami menghubungkan penonton dengan pengalaman terbaik dan membantu penyelenggara menjual tiket secara aman.',
            'sections' => [
                ['heading' => 'Apa yang kami lakukan', 'body' => 'Platform ini menyediakan katalog event, pembelian tiket online, pembayaran lokal, dan e-tiket ber-QR. Pembeli dapat menemukan konser, seminar, workshop, dan festival dalam satu tempat.'],
                ['heading' => 'Untuk penyelenggara', 'body' => 'Organizer dapat menyiapkan event, mengatur jenis tiket dan kuota, memantau penjualan, serta mengelola check-in di lokasi acara.'],
                ['heading' => 'Komitmen kami', 'body' => 'Kami mengutamakan transparansi harga, keamanan pembayaran, dan kejelasan status pesanan. Setiap event melewati proses moderasi sebelum tayang.'],
            ],
        ],
        'privacy' => [
            'title' => 'Kebijakan Privasi',
            'intro' => 'Kebijakan ini menjelaskan bagaimana data pribadi Anda dikumpulkan dan digunakan.',
            'sections' => [
                ['heading' => 'Data yang kami kumpulkan', 'body' => 'Nama, alamat email, nomor telepon, dan data transaksi yang diperlukan untuk memproses pembelian tiket. Untuk event tertentu, nama pemegang tiket dapat diminta sesuai kebutuhan penyelenggara.'],
                ['heading' => 'Penggunaan data', 'body' => 'Data digunakan untuk memproses pesanan, menerbitkan tiket, mengirim notifikasi, mencegah penyalahgunaan, dan memenuhi kewajiban hukum.'],
                ['heading' => 'Berbagi data', 'body' => 'Data hanya dibagikan kepada penyelenggara event terkait, penyedia pembayaran, dan penyedia layanan email sejauh diperlukan untuk operasional.'],
                ['heading' => 'Hak Anda', 'body' => 'Anda dapat meminta akses, koreksi, atau penghapusan data sesuai ketentuan yang berlaku melalui kanal kontak resmi.'],
            ],
        ],
        'terms' => [
            'title' => 'Syarat & Ketentuan',
            'intro' => 'Dengan menggunakan layanan ini, Anda menyetujui ketentuan berikut.',
            'sections' => [
                ['heading' => 'Pembelian tiket', 'body' => 'Pesanan dianggap sah setelah pembayaran berhasil dikonfirmasi. Tiket diterbitkan setelah status pembayaran sukses.'],
                ['heading' => 'Batas waktu pembayaran', 'body' => 'Setiap pesanan memiliki batas waktu pembayaran. Jika melewati batas waktu, pesanan dapat kedaluwarsa dan stok tiket dilepas kembali.'],
                ['heading' => 'Penggunaan tiket', 'body' => 'Tiket bersifat pribadi dan hanya berlaku untuk event yang tertera. Pemalsuan atau penggunaan ganda dapat menyebabkan tiket tidak berlaku.'],
                ['heading' => 'Perubahan event', 'body' => 'Penyelenggara dapat mengubah jadwal atau lokasi event dengan pemberitahuan. Perubahan signifikan akan diinformasikan melalui email pembeli.'],
            ],
        ],
        'refund' => [
            'title' => 'Kebijakan Refund',
            'intro' => 'Ketentuan pengembalian dana mengikuti status event dan kebijakan penyelenggara.',
            'sections' => [
                ['heading' => 'Event dibatalkan', 'body' => 'Jika event dibatalkan oleh penyelenggara, pembeli berhak atas pengembalian dana penuh untuk tiket yang dibeli.'],
                ['heading' => 'Permintaan pembeli', 'body' => 'Permintaan pengembalian dana di luar pembatalan event dapat diajukan melalui kanal kontak resmi dan akan ditinjau sesuai kebijakan yang berlaku.'],
                ['heading' => 'Biaya', 'body' => 'Biaya layanan dan biaya pembayaran umumnya tidak dapat dikembalikan, kecuali event dibatalkan oleh penyelenggara.'],
                ['heading' => 'Waktu proses', 'body' => 'Pengembalian dana yang disetujui diproses ke metode pembayaran asal. Waktu penyelesaian bergantung pada penyedia pembayaran.'],
            ],
        ],
    ];

    $page = $pages[$pageKey] ?? null;
    $title = $pageTitle ?? $page['title'] ?? 'Informasi';
@endphp

<x-layouts.public
    :title="$seo['title']"
    :description="$seo['description']"
    :settings="$settings"
    :canonical="url()->current()"
>
    <section class="hero">
        <div class="container">
            <div class="eyebrow">{{ $pageKey === 'faq' ? 'Bantuan' : 'Informasi' }}</div>
            <h1 class="display-4 mt-2">{{ $title }}</h1>
            <p class="lead text-secondary mt-3 mb-0">{{ $page['intro'] ?? $seo['description'] }}</p>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    @if ($pageKey === 'faq')
                        @if ($faqs->isEmpty())
                            <div class="card-clean p-4 text-secondary">Belum ada FAQ yang dipublikasikan.</div>
                        @else
                            <div class="accordion" id="faqAccordion">
                                @foreach ($faqs as $faq)
                                    <div class="accordion-item border-0 mb-3 card-clean overflow-hidden">
                                        <h2 class="accordion-header" id="faqHeading{{ $faq->id }}">
                                            <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }} fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse{{ $faq->id }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="faqCollapse{{ $faq->id }}">
                                                {{ $faq->question }}
                                            </button>
                                        </h2>
                                        <div id="faqCollapse{{ $faq->id }}" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="faqHeading{{ $faq->id }}" data-bs-parent="#faqAccordion">
                                            <div class="accordion-body text-secondary">{{ $faq->answer }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @elseif ($page)
                        <div class="d-flex flex-column gap-4">
                            @foreach ($page['sections'] as $section)
                                <div class="card-clean p-4">
                                    <h2 class="h5">{{ $section['heading'] }}</h2>
                                    <p class="text-secondary mb-0">{{ $section['body'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="card-clean p-4 text-secondary">Halaman tidak ditemukan.</div>
                    @endif

                    <div class="card-clean p-4 mt-4">
                        <h2 class="h5">Masih butuh bantuan?</h2>
                        <p class="text-secondary mb-3">Hubungi tim kami melalui halaman kontak atau WhatsApp resmi.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('contact') }}" class="btn btn-main">Hubungi kami</a>
                            <a href="{{ route('faq') }}" class="btn btn-ghost">Lihat FAQ</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
