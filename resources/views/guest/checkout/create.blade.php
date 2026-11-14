@php
    $start = $event->startsAtLocal();
    $selectedId = (string) old('ticket_type_id', $selectedTicketTypeId);
@endphp

<x-layouts.public
    :settings="$settings"
    :title="'Checkout '.$event->title.' — '.($settings['site_name'] ?? 'LinyTix')"
    description="Pilih jenis tiket dan isi data pembeli untuk {{ $event->title }}."
>
    <section class="section" style="padding-top: 8.5rem;">
        <div class="container">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('events.index') }}">Event</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('events.show', $event->slug) }}">{{ $event->title }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Checkout</li>
                </ol>
            </nav>

            <div class="row g-4 g-lg-5">
                <div class="col-lg-7">
                    <div class="mb-4">
                        <div class="eyebrow">Checkout</div>
                        <h1 class="display-6 mt-2 mb-2">Pilih tiket dan lengkapi data.</h1>
                        <p class="text-secondary mb-0">Pesanan disimpan selama {{ (int) config('payment.order_expiry_minutes', 15) }} menit. Selesaikan pembayaran sebelum batas waktu.</p>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger" role="alert">
                            <div class="fw-semibold mb-1">Periksa kembali data berikut:</div>
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('checkout.store', $event->slug) }}" id="checkout-form">
                        @csrf
                        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', $idempotencyKey) }}">
                        <input type="hidden" name="expected_unit_price" id="expected-unit-price" value="{{ old('expected_unit_price') }}">

                        <div class="card-clean p-4 mb-4">
                            <h2 class="h5 mb-3">Jenis tiket</h2>

                            @foreach ($ticketTypes as $ticketType)
                                @php
                                    $available = $ticketType->isOnSaleNow();
                                    $min = max(1, (int) $ticketType->min_per_order);
                                    $max = max($min, min((int) $ticketType->max_per_order, $ticketType->remainingQuota()));
                                @endphp
                                <label class="ticket-option d-flex gap-3 align-items-start p-3 mb-2 {{ $available ? '' : 'is-disabled' }}">
                                    <input
                                        type="radio"
                                        class="form-check-input mt-1"
                                        name="ticket_type_id"
                                        value="{{ $ticketType->getKey() }}"
                                        data-ticket-option
                                        data-price="{{ (int) $ticketType->price }}"
                                        data-min="{{ $min }}"
                                        data-max="{{ $max }}"
                                        data-remaining="{{ $ticketType->remainingQuota() }}"
                                        @checked($selectedId === $ticketType->getKey())
                                        @disabled(! $available)
                                        required
                                    >
                                    <span class="flex-grow-1">
                                        <span class="d-flex flex-wrap justify-content-between gap-2">
                                            <span class="fw-semibold">{{ $ticketType->name }}</span>
                                            <span class="fw-bold">{{ $ticketType->price > 0 ? 'Rp '.number_format((int) $ticketType->price, 0, ',', '.') : 'Gratis' }}</span>
                                        </span>
                                        @if ($ticketType->description)
                                            <span class="d-block small text-secondary mt-1">{{ $ticketType->description }}</span>
                                        @endif
                                        <span class="d-block small text-secondary mt-1">
                                            {{ $available ? 'Sisa '.$ticketType->remainingQuota().' tiket' : $ticketType->saleStatusLabel() }}
                                            · Maks. {{ $max }} per pesanan
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <div class="card-clean p-4 mb-4">
                            <h2 class="h5 mb-3">Jumlah tiket</h2>
                            <label for="quantity" class="form-label small fw-semibold">Jumlah</label>
                            <input
                                type="number"
                                class="form-control @error('quantity') is-invalid @enderror"
                                id="quantity"
                                name="quantity"
                                min="1"
                                max="100"
                                value="{{ old('quantity', 1) }}"
                                required
                            >
                            @error('quantity')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Harga total dihitung otomatis oleh sistem berdasarkan jenis tiket yang dipilih.</div>
                        </div>

                        <div class="card-clean p-4 mb-4">
                            <h2 class="h5 mb-3">Data pembeli</h2>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="buyer_name" class="form-label small fw-semibold">Nama lengkap</label>
                                    <input type="text" class="form-control @error('buyer_name') is-invalid @enderror" id="buyer_name" name="buyer_name" value="{{ old('buyer_name', auth()->user()?->name) }}" required autocomplete="name">
                                    @error('buyer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="buyer_email" class="form-label small fw-semibold">Email</label>
                                    <input type="email" class="form-control @error('buyer_email') is-invalid @enderror" id="buyer_email" name="buyer_email" value="{{ old('buyer_email', auth()->user()?->email) }}" required autocomplete="email">
                                    @error('buyer_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    <div class="form-text">E-tiket dan tautan pesanan dikirim ke email ini.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="buyer_phone" class="form-label small fw-semibold">Nomor telepon</label>
                                    <input type="tel" class="form-control @error('buyer_phone') is-invalid @enderror" id="buyer_phone" name="buyer_phone" value="{{ old('buyer_phone', auth()->user()?->phone) }}" required autocomplete="tel">
                                    @error('buyer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="card-clean p-4 mb-4 {{ $event->require_identity ? '' : 'd-none' }}" id="holder-names">
                            <h2 class="h5 mb-1">Nama pemegang tiket</h2>
                            <p class="small text-secondary mb-3">Event ini meminta nama untuk setiap tiket. Pastikan jumlahnya sesuai.</p>
                            <div class="row g-3" id="holder-inputs"></div>
                            @error('holder_names')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                            @error('holder_names.*')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                        </div>

                        <div class="d-flex flex-column flex-sm-row gap-2">
                            <button type="submit" class="btn btn-main btn-lg flex-grow-1">
                                <i class="fa-solid fa-arrow-right me-1"></i> Buat pesanan
                            </button>
                            <a href="{{ route('events.show', $event->slug) }}" class="btn btn-ghost btn-lg">Kembali</a>
                        </div>
                    </form>
                </div>

                <div class="col-lg-5">
                    <div class="sticky-card">
                        <div class="card-clean overflow-hidden">
                            <div class="checkout-poster">
                                <img src="{{ $event->displayPosterUrl() }}" alt="Poster {{ $event->title }}">
                            </div>
                            <div class="p-4">
                                @if ($event->category)
                                    <span class="badge rounded-pill text-bg-light border mb-2">{{ $event->category->name }}</span>
                                @endif
                                <h2 class="h5 mb-2">{{ $event->title }}</h2>
                                <div class="small text-secondary mb-1">
                                    <i class="fa-regular fa-calendar me-2"></i>{{ $start?->translatedFormat('l, d F Y, H.i') }} {{ $start?->format('T') }}
                                </div>
                                <div class="small text-secondary mb-1">
                                    <i class="fa-solid fa-location-dot me-2"></i>{{ $event->is_online ? 'Online' : ($event->venue?->name ?? 'Lokasi menyusul') }}
                                </div>
                                @if ($event->organizer)
                                    <div class="small text-secondary">
                                        <i class="fa-solid fa-users me-2"></i>{{ $event->organizer->name }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="card-clean p-4 mt-4">
                            <h2 class="h6 mb-2">Sebelum melanjutkan</h2>
                            <ul class="small text-secondary mb-0 ps-3">
                                <li>Pastikan email dan nomor telepon aktif.</li>
                                <li>Pesanan berlaku {{ (int) config('payment.order_expiry_minutes', 15) }} menit.</li>
                                <li>E-tiket diterbitkan setelah pembayaran dikonfirmasi.</li>
                                <li>Tiket tidak dapat dikembalikan kecuali event dibatalkan.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @push('styles')
        <style>
            .ticket-option {
                border: 1px solid var(--line);
                border-radius: 1rem;
                background: #fff;
                cursor: pointer;
                transition: border-color .2s ease, box-shadow .2s ease;
            }

            .ticket-option:hover {
                border-color: rgba(13, 104, 101, .45);
            }

            .ticket-option:has(input:checked) {
                border-color: var(--teal);
                
            }

            .ticket-option.is-disabled {
                opacity: .55;
                cursor: not-allowed;
            }

            .checkout-poster {
                aspect-ratio: 16 / 10;
                overflow: hidden;
                background: var(--mint);
            }

            .checkout-poster img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (function () {
                const options = document.querySelectorAll('[data-ticket-option]');
                const quantity = document.getElementById('quantity');
                const expected = document.getElementById('expected-unit-price');
                const holderWrap = document.getElementById('holder-names');
                const holderInputs = document.getElementById('holder-inputs');
                const requireIdentity = @json((bool) $event->require_identity);
                const oldNames = @json(array_values((array) old('holder_names', [])));

                if (!quantity || !expected) {
                    return;
                }

                function selectedOption() {
                    return document.querySelector('[data-ticket-option]:checked');
                }

                function escapeValue(value) {
                    return value == null ? '' : String(value);
                }

                function renderHolderInputs() {
                    if (!holderWrap || !holderInputs) {
                        return;
                    }

                    holderWrap.classList.toggle('d-none', !requireIdentity);

                    if (!requireIdentity) {
                        holderInputs.innerHTML = '';
                        return;
                    }

                    const existing = Array.from(holderInputs.querySelectorAll('input')).map(function (input) {
                        return input.value;
                    });
                    const source = existing.length ? existing : oldNames;
                    const count = Math.max(1, parseInt(quantity.value || '1', 10));
                    holderInputs.innerHTML = '';

                    for (let index = 0; index < count; index++) {
                        const col = document.createElement('div');
                        col.className = 'col-md-6';

                        const label = document.createElement('label');
                        label.className = 'form-label small fw-semibold';
                        label.setAttribute('for', 'holder-' + index);
                        label.textContent = 'Nama pemegang tiket ' + (index + 1);

                        const input = document.createElement('input');
                        input.type = 'text';
                        input.className = 'form-control';
                        input.id = 'holder-' + index;
                        input.name = 'holder_names[]';
                        input.required = true;
                        input.value = escapeValue(source[index] || '');

                        col.appendChild(label);
                        col.appendChild(input);
                        holderInputs.appendChild(col);
                    }
                }

                function syncQuantity() {
                    const option = selectedOption();

                    if (!option) {
                        return;
                    }

                    const min = parseInt(option.dataset.min || '1', 10);
                    const max = parseInt(option.dataset.max || '10', 10);
                    const current = parseInt(quantity.value || String(min), 10);

                    quantity.min = String(min);
                    quantity.max = String(max);
                    quantity.value = String(Math.min(Math.max(current, min), max));
                    expected.value = option.dataset.price || '0';

                    renderHolderInputs();
                }

                options.forEach(function (option) {
                    option.addEventListener('change', syncQuantity);
                });

                quantity.addEventListener('input', renderHolderInputs);
                quantity.addEventListener('change', renderHolderInputs);

                syncQuantity();
            })();
        </script>
    @endpush
</x-layouts.public>
