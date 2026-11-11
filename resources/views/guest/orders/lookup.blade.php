<x-layouts.public
    :settings="$settings"
    :title="'Cari Pesanan — '.($settings['site_name'] ?? 'LinyTix')"
    :canonical="route('guest.orders.lookup')"
>
    <section class="section" style="padding-top: 8.5rem;">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6">
                    <div class="text-center mb-4">
                        <div class="eyebrow">Akses guest</div>
                        <h1 class="display-6 mb-2">Cari pesanan Anda</h1>
                        <p class="text-secondary mb-0">
                            Masukkan nomor order dan email pembeli. Kami akan mengirim tautan akses ke email tersebut.
                        </p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success" role="status">{{ session('status') }}</div>
                    @endif

                    <div class="card-clean p-4 p-md-5">
                        <form method="POST" action="{{ route('guest.orders.lookup.store') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="order_number" class="form-label fw-semibold">Nomor order</label>
                                <input type="text" name="order_number" id="order_number"
                                    value="{{ old('order_number') }}"
                                    class="form-control @error('order_number') is-invalid @enderror"
                                    placeholder="Contoh: ORD-20261111-ABCD1234" required autofocus>
                                @error('order_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-semibold">Email pembeli</label>
                                <input type="email" name="email" id="email"
                                    value="{{ old('email') }}"
                                    class="form-control @error('email') is-invalid @enderror"
                                    placeholder="email@contoh.com" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-main w-100">
                                <i class="fa-solid fa-paper-plane me-1"></i> Kirim tautan akses
                            </button>
                        </form>

                        <p class="small text-secondary mb-0 mt-3">
                            Demi keamanan, kami tidak menampilkan data pesanan secara langsung. Tautan hanya dikirim ke email pembeli yang cocok.
                        </p>
                    </div>

                    <p class="text-center text-secondary mt-4 mb-0">
                        Sudah punya akun? <a href="{{ route('login') }}" class="text-link">Masuk</a>
                    </p>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
