@php
    $siteName = $settings['site_name'] ?? 'LinyTix';
@endphp

<x-layouts.public
    :settings="$settings"
    :title="'Daftar Organizer — '.$siteName"
    :description="'Ajukan profil organizer Anda dan mulai jual tiket event di '.$siteName.'.'"
>
    <section class="section" style="padding-top: 8.5rem;">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-5">
                    <div class="eyebrow">Untuk penyelenggara</div>
                    <h1 class="display-6 mt-2 mb-3">Daftar sebagai organizer.</h1>
                    <p class="text-secondary mb-4">
                        Kelola event, jual tiket, dan pantau penjualan dalam satu tempat. Setelah mengajukan,
                        admin akan memverifikasi profil Anda sebelum event dapat diajukan untuk tayang.
                    </p>

                    <div class="alert alert-info small mb-4">
                        <i class="fas fa-circle-info me-1"></i>
                        <strong>Anda bisa mendaftarkan event di sini.</strong>
                        Setelah profil organizer diverifikasi admin, buat event pertama Anda dari dashboard organizer.
                    </div>

                    <div class="card-clean p-4 mb-4">
                        <h2 class="h5 mb-3">Cara mendaftar organizer</h2>
                        <ol class="text-secondary small mb-0 ps-3">
                            <li class="mb-2">Isi data organizer dan kontak pada formulir.</li>
                            <li class="mb-2">Admin meninjau pengajuan dan memverifikasi profil.</li>
                            <li>Jika ada perbaikan, catatan admin tampil di dashboard Anda.</li>
                        </ol>
                    </div>

                    <div class="card-clean p-4 mb-4">
                        <h2 class="h5 mb-3">Cara mendaftarkan event</h2>
                        <ol class="text-secondary small mb-0 ps-3">
                            <li class="mb-2">Buat event draft: judul, kategori, jadwal, venue/online, deskripsi, dan poster.</li>
                            <li class="mb-2">Tambahkan minimal satu jenis tiket aktif beserta harga dan kuota.</li>
                            <li class="mb-2">Ajukan event untuk review admin. Field penjualan terkunci selama review.</li>
                            <li>Setelah disetujui, event tayang di katalog dan tiket bisa dijual.</li>
                        </ol>
                    </div>

                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <a href="{{ route('organizer.guide') }}" class="text-link">
                            <i class="fas fa-book-open me-1"></i> Baca panduan lengkap
                        </a>
                    </div>

                    <a href="{{ route('akun.dashboard') }}" class="text-link">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke akun saya
                    </a>
                </div>

                <div class="col-lg-7">
                    <div class="card-clean p-4 p-md-5">
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('organizer.register.store') }}">
                            @csrf

                            <h2 class="h4 mb-3">Data organizer</h2>

                            <div class="mb-3">
                                <label for="name" class="form-label small fw-semibold">Nama organizer</label>
                                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                                    class="form-control @error('name') is-invalid @enderror" required maxlength="255"
                                    placeholder="Contoh: Nusa Production">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label small fw-semibold">Deskripsi</label>
                                <textarea id="description" name="description" rows="4" maxlength="5000"
                                    class="form-control @error('description') is-invalid @enderror"
                                    placeholder="Ceritakan jenis event yang Anda selenggarakan.">{{ old('description') }}</textarea>
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="contact_email" class="form-label small fw-semibold">Email kontak</label>
                                    <input type="email" id="contact_email" name="contact_email"
                                        value="{{ old('contact_email', $user->email) }}"
                                        class="form-control @error('contact_email') is-invalid @enderror" required maxlength="255">
                                    @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="contact_phone" class="form-label small fw-semibold">Telepon kontak</label>
                                    <input type="text" id="contact_phone" name="contact_phone"
                                        value="{{ old('contact_phone', $user->phone) }}"
                                        class="form-control @error('contact_phone') is-invalid @enderror" required maxlength="30"
                                        placeholder="08xxxxxxxxxx">
                                    @error('contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <h2 class="h5 mt-4 mb-3">Rekening pencairan <span class="text-secondary fw-normal">(opsional)</span></h2>
                            <p class="text-secondary small">Dapat dilengkapi nanti di halaman profil organizer.</p>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="bank_name" class="form-label small fw-semibold">Nama bank</label>
                                    <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name') }}"
                                        class="form-control @error('bank_name') is-invalid @enderror" maxlength="255"
                                        placeholder="Contoh: Bank BCA">
                                    @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="bank_account_no" class="form-label small fw-semibold">Nomor rekening</label>
                                    <input type="text" id="bank_account_no" name="bank_account_no" value="{{ old('bank_account_no') }}"
                                        class="form-control @error('bank_account_no') is-invalid @enderror" maxlength="50"
                                        inputmode="numeric" autocomplete="off">
                                    @error('bank_account_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12">
                                    <label for="bank_account_name" class="form-label small fw-semibold">Nama pemilik rekening</label>
                                    <input type="text" id="bank_account_name" name="bank_account_name"
                                        value="{{ old('bank_account_name') }}"
                                        class="form-control @error('bank_account_name') is-invalid @enderror" maxlength="255">
                                    @error('bank_account_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input @error('agree_terms') is-invalid @enderror"
                                    type="checkbox" name="agree_terms" value="1" id="agree_terms"
                                    @checked(old('agree_terms')) required>
                                <label class="form-check-label small" for="agree_terms">
                                    Saya menyetujui
                                    <a href="{{ route('info.terms') }}" target="_blank" class="text-link">Syarat &amp; Ketentuan</a>
                                    dan
                                    <a href="{{ route('info.privacy') }}" target="_blank" class="text-link">Kebijakan Privasi</a>.
                                </label>
                                @error('agree_terms')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>

                            <button type="submit" class="btn btn-main w-100">
                                <i class="fas fa-paper-plane me-1"></i> Kirim pengajuan organizer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
