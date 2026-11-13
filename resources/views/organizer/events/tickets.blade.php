<x-layouts.app title="Kelola Tiket - Nusa Event" brand-name="Nusa Event" brand-icon="fas fa-ticket">
    <x-layout.page-header title="Kelola Jenis Tiket" subtitle="{{ $event->title }}">
        <x-slot:actions>
            <a href="{{ route('organizer.events.edit', $event) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali ke Event
            </a>
        </x-slot:actions>
    </x-layout.page-header>

    @if (! $event->isFullyEditable())
        <div class="alert alert-warning">
            Jenis tiket tidak dapat diubah karena event berstatus <strong>{{ $event->statusLabel() }}</strong>.
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $capacity = $event->is_online ? null : $event->venue?->capacity;
        $totalQuota = (int) $event->ticketTypes->sum('quota');
        $overCapacity = $capacity !== null && $totalQuota > $capacity;
    @endphp

    @if ($overCapacity)
        <div class="alert alert-danger">
            <strong>Kuota melebihi kapasitas venue.</strong>
            Total kuota tiket saat ini <strong>{{ number_format($totalQuota, 0, ',', '.') }}</strong>,
            sedangkan kapasitas {{ $event->venue?->name }} hanya
            <strong>{{ number_format($capacity, 0, ',', '.') }}</strong>.
            Kurangi kuota jenis tiket sebelum mengajukan review.
        </div>
    @elseif ($capacity !== null)
        <div class="alert alert-info">
            Kapasitas {{ $event->venue?->name }}: <strong>{{ number_format($capacity, 0, ',', '.') }}</strong> ·
            Total kuota tiket: <strong>{{ number_format($totalQuota, 0, ',', '.') }}</strong>
            (sisa {{ number_format(max(0, $capacity - $totalQuota), 0, ',', '.') }}).
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <x-layout.modern-card title="Daftar Jenis Tiket">
                @forelse ($event->ticketTypes as $ticketType)
                    <form method="POST" action="{{ route('organizer.events.tickets.update', [$event, $ticketType]) }}" class="border rounded p-3 mb-3">
                        @csrf
                        @method('PUT')
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-semibold">{{ $ticketType->name }}</div>
                            <span class="badge text-bg-{{ $ticketType->is_active ? 'success' : 'secondary' }}">
                                {{ $ticketType->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small">Nama</label>
                                <input type="text" name="name" value="{{ $ticketType->name }}" class="form-control form-control-sm" required maxlength="255" @disabled(! $event->isFullyEditable())>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Harga</label>
                                <input type="number" name="price" value="{{ $ticketType->price }}" class="form-control form-control-sm" min="0" required @disabled(! $event->isFullyEditable())>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Kuota</label>
                                <input type="number" name="quota" value="{{ $ticketType->quota }}" class="form-control form-control-sm" min="{{ max(1, $ticketType->sold_count) }}" required @disabled(! $event->isFullyEditable())>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Min/order</label>
                                <input type="number" name="min_per_order" value="{{ $ticketType->min_per_order }}" class="form-control form-control-sm" min="1" max="10" required @disabled(! $event->isFullyEditable())>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Maks/order</label>
                                <input type="number" name="max_per_order" value="{{ $ticketType->max_per_order }}" class="form-control form-control-sm" min="1" max="10" required @disabled(! $event->isFullyEditable())>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Jual mulai</label>
                                <input type="datetime-local" name="sale_start_at" value="{{ $ticketType->sale_start_at?->copy()->setTimezone($event->timezoneLabel())->format('Y-m-d\TH:i') }}" class="form-control form-control-sm" @disabled(! $event->isFullyEditable())>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small">Jual sampai</label>
                                <input type="datetime-local" name="sale_end_at" value="{{ $ticketType->sale_end_at?->copy()->setTimezone($event->timezoneLabel())->format('Y-m-d\TH:i') }}" class="form-control form-control-sm" @disabled(! $event->isFullyEditable())>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Deskripsi</label>
                                <input type="text" name="description" value="{{ $ticketType->description }}" class="form-control form-control-sm" maxlength="2000" @disabled(! $event->isFullyEditable())>
                            </div>
                            <div class="col-12 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input type="hidden" name="is_active" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active-{{ $ticketType->getKey() }}" @checked($ticketType->is_active) @disabled(! $event->isFullyEditable())>
                                    <label class="form-check-label small" for="active-{{ $ticketType->getKey() }}">Aktif dijual</label>
                                </div>
                                <div class="small text-muted">Terjual: {{ $ticketType->sold_count }}</div>
                            </div>
                        </div>

                        @if ($event->isFullyEditable())
                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-floppy-disk me-1"></i>Simpan
                                </button>
                                @if ($ticketType->sold_count === 0)
                                    <button type="submit" form="delete-ticket-{{ $ticketType->getKey() }}" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash me-1"></i>Hapus
                                    </button>
                                @endif
                            </div>
                        @endif
                    </form>

                    @if ($event->isFullyEditable() && $ticketType->sold_count === 0)
                        <form method="POST" action="{{ route('organizer.events.tickets.destroy', [$event, $ticketType]) }}" id="delete-ticket-{{ $ticketType->getKey() }}" class="d-none" onsubmit="return confirm('Hapus jenis tiket ini?');">
                            @csrf
                            @method('DELETE')
                        </form>
                    @endif
                @empty
                    <p class="text-muted mb-0">Belum ada jenis tiket. Tambahkan minimal satu jenis tiket aktif agar event dapat diajukan review.</p>
                @endforelse
            </x-layout.modern-card>
        </div>

        <div class="col-lg-5">
            @if ($event->isFullyEditable())
                <x-layout.modern-card title="Tambah Jenis Tiket">
                    <form method="POST" action="{{ route('organizer.events.tickets.store', $event) }}">
                        @csrf
                        <div class="mb-3">
                            <label for="name" class="form-label small fw-semibold">Nama jenis tiket <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control @error('name') is-invalid @enderror" required maxlength="255" placeholder="Contoh: Regular, VIP, Presale">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-2">
                            <div class="col-6">
                                <label for="price" class="form-label small fw-semibold">Harga <span class="text-danger">*</span></label>
                                <input type="number" id="price" name="price" value="{{ old('price', 0) }}" class="form-control @error('price') is-invalid @enderror" min="0" required>
                                @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label for="quota" class="form-label small fw-semibold">Kuota <span class="text-danger">*</span></label>
                                <input type="number" id="quota" name="quota" value="{{ old('quota', 100) }}" class="form-control @error('quota') is-invalid @enderror" min="1" required>
                                @error('quota')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label for="min_per_order" class="form-label small fw-semibold">Min/order</label>
                                <input type="number" id="min_per_order" name="min_per_order" value="{{ old('min_per_order', 1) }}" class="form-control" min="1" max="10" required>
                            </div>
                            <div class="col-6">
                                <label for="max_per_order" class="form-label small fw-semibold">Maks/order</label>
                                <input type="number" id="max_per_order" name="max_per_order" value="{{ old('max_per_order', 4) }}" class="form-control" min="1" max="10" required>
                            </div>
                            <div class="col-6">
                                <label for="sale_start_at" class="form-label small fw-semibold">Jual mulai</label>
                                <input type="datetime-local" id="sale_start_at" name="sale_start_at" value="{{ old('sale_start_at') }}" class="form-control @error('sale_start_at') is-invalid @enderror">
                                @error('sale_start_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label for="sale_end_at" class="form-label small fw-semibold">Jual sampai</label>
                                <input type="datetime-local" id="sale_end_at" name="sale_end_at" value="{{ old('sale_end_at') }}" class="form-control @error('sale_end_at') is-invalid @enderror">
                                @error('sale_end_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <div class="form-text">
                                    Jadwal jual harus berakhir sebelum event dimulai
                                    ({{ $event->startsAtLocal()?->translatedFormat('d M Y, H.i') ?? 'jadwal event belum diisi' }}).
                                    Kosongkan untuk menjual sampai kuota habis.
                                </div>
                            </div>
                        </div>
                        <div class="mb-3 mt-2">
                            <label for="description" class="form-label small fw-semibold">Deskripsi</label>
                            <input type="text" id="description" name="description" value="{{ old('description') }}" class="form-control" maxlength="2000">
                        </div>
                        <div class="form-check mb-3">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', true))>
                            <label class="form-check-label small" for="is_active">Aktif dijual</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-1"></i>Tambah Tiket
                        </button>
                    </form>
                </x-layout.modern-card>
            @endif

            <x-layout.modern-card title="Syarat Submit Review" class="mt-4">
                <ul class="small text-secondary mb-0">
                    <li>Minimal satu jenis tiket <strong>aktif</strong>.</li>
                    <li>Poster sudah diunggah.</li>
                    <li>Kategori, jadwal, timezone, dan deskripsi terisi.</li>
                    <li>Venue terisi untuk event offline atau URL untuk event online.</li>
                    <li>Organizer sudah terverifikasi admin.</li>
                </ul>
                <a href="{{ route('organizer.events.edit', $event) }}" class="btn btn-outline-secondary w-100 mt-3">Kembali ke form event</a>
            </x-layout.modern-card>
        </div>
    </div>
</x-layouts.app>
