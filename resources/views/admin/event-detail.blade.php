<x-layouts.app title="Detail Event - Admin">
    @php
        $isPending = $event->status === \App\Models\Event::STATUS_PENDING_REVIEW;
        $isCancellable = in_array($event->status, [
            \App\Models\Event::STATUS_PUBLISHED,
            \App\Models\Event::STATUS_SOLD_OUT,
        ], true);
    @endphp

    <x-layout.page-header title="{{ $event->title }}" subtitle="Detail event, tiket, organizer, dan aksi moderasi.">
        <x-slot:actions>
            <a href="{{ route('admin.events') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
            @if ($event->isOnSale())
                <a href="{{ route('events.show', $event->slug) }}" target="_blank" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-up-right-from-square me-2"></i>Lihat Publik
                </a>
            @endif
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
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

    <div class="row g-4">
        <div class="col-lg-4">
            <x-layout.modern-card class="h-100">
                <h5 class="fw-semibold mb-3">Poster</h5>
                <div class="rounded overflow-hidden border mb-3" style="aspect-ratio: 4 / 3; background: var(--mint);">
                    <img src="{{ $event->displayPosterUrl() }}" alt="Poster {{ $event->title }}" class="w-100 h-100" style="object-fit: cover;">
                </div>

                <div class="mb-3">
                    <x-events.status-badge :status="$event->status" />
                    @if ($event->is_featured)
                        <x-ui.badge variant="primary" class="ms-1">Featured</x-ui.badge>
                    @endif
                </div>

                <dl class="row small mb-0">
                    <dt class="col-5 text-muted fw-normal">Kategori</dt>
                    <dd class="col-7 mb-2">{{ $event->category?->name ?? '—' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Lokasi</dt>
                    <dd class="col-7 mb-2">
                        @if ($event->is_online)
                            Online
                        @else
                            {{ $event->venue?->name ?? 'Venue menyusul' }}
                        @endif
                    </dd>

                    <dt class="col-5 text-muted fw-normal">Zona waktu</dt>
                    <dd class="col-7 mb-2">{{ $event->timezoneLabel() }}</dd>

                    <dt class="col-5 text-muted fw-normal">Mulai</dt>
                    <dd class="col-7 mb-2">{{ $event->startsAtLocal()?->translatedFormat('d M Y, H.i') ?? '—' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Selesai</dt>
                    <dd class="col-7 mb-2">{{ $event->endsAtLocal()?->translatedFormat('d M Y, H.i') ?? '—' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Harga mulai</dt>
                    <dd class="col-7 mb-2">Rp {{ number_format($event->lowestActivePrice(), 0, ',', '.') }}</dd>

                    <dt class="col-5 text-muted fw-normal">Pesanan</dt>
                    <dd class="col-7 mb-0">{{ $event->orders_count }} total, {{ $event->paid_orders_count }} lunas</dd>
                </dl>
            </x-layout.modern-card>
        </div>

        <div class="col-lg-8">
            <x-layout.modern-card title="Informasi Event" class="mb-4">
                <div class="row g-3 small mb-3">
                    <div class="col-md-6">
                        <div class="text-muted">Slug</div>
                        <div class="fw-semibold">/events/{{ $event->slug }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Dibuat</div>
                        <div class="fw-semibold">{{ $event->created_at?->translatedFormat('d M Y, H.i') }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Dipublikasikan</div>
                        <div class="fw-semibold">{{ $event->published_at?->translatedFormat('d M Y, H.i') ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Disetujui oleh</div>
                        <div class="fw-semibold">
                            {{ $event->approver?->name ?? '—' }}
                            @if ($event->approved_at)
                                <span class="text-muted fw-normal">· {{ $event->approved_at->translatedFormat('d M Y, H.i') }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($event->short_desc)
                    <p class="text-secondary mb-3">{{ $event->short_desc }}</p>
                @endif

                <h6 class="fw-semibold">Deskripsi</h6>
                <div class="text-secondary mb-3" style="white-space: pre-line;">{{ $event->description ?: 'Belum ada deskripsi.' }}</div>

                @if ($event->terms)
                    <h6 class="fw-semibold">Syarat & Ketentuan</h6>
                    <div class="text-secondary" style="white-space: pre-line;">{{ $event->terms }}</div>
                @endif

                @if ($event->is_online && $event->online_url)
                    <div class="mt-3">
                        <span class="text-muted small">URL Online:</span>
                        <a href="{{ $event->online_url }}" target="_blank" rel="noopener" class="small">{{ $event->online_url }}</a>
                    </div>
                @endif

                @if ($event->lineups->isNotEmpty())
                    <hr>
                    <h6 class="fw-semibold">Lineup</h6>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($event->lineups as $lineup)
                            <x-ui.badge variant="secondary">{{ $lineup->name }}</x-ui.badge>
                        @endforeach
                    </div>
                @endif

                @if ($event->images->isNotEmpty())
                    <hr>
                    <h6 class="fw-semibold">Galeri</h6>
                    <div class="row g-2">
                        @foreach ($event->images as $image)
                            <div class="col-4">
                                <img src="{{ $image->image_url }}" alt="{{ $image->caption ?: 'Galeri '.$event->title }}" class="w-100 rounded border" style="height: 90px; object-fit: cover;">
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-layout.modern-card>

            <x-layout.modern-card title="Organizer" class="mb-4">
                <div class="row g-3 small">
                    <div class="col-md-6">
                        <div class="text-muted">Nama organizer</div>
                        <div class="fw-semibold">{{ $event->organizer?->name ?? '—' }}</div>
                        @if ($event->organizer)
                            <a href="{{ route('organizers.show', $event->organizer->slug) }}" target="_blank" class="small text-decoration-none">
                                /organizers/{{ $event->organizer->slug }}
                                <i class="fas fa-arrow-up-right-from-square ms-1" style="font-size: .65rem;"></i>
                            </a>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Owner</div>
                        <div class="fw-semibold">{{ $event->organizer?->owner?->name ?? '—' }}</div>
                        <div class="text-muted">{{ $event->organizer?->owner?->email ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Kontak organizer</div>
                        <div class="fw-semibold">{{ $event->organizer?->contact_email ?? '—' }}</div>
                        <div class="text-muted">{{ $event->organizer?->contact_phone ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted">Status verifikasi</div>
                        @if ($event->organizer?->is_verified)
                            <x-ui.badge variant="success">Terverifikasi</x-ui.badge>
                        @else
                            <x-ui.badge variant="warning">Belum terverifikasi</x-ui.badge>
                        @endif
                    </div>
                </div>
            </x-layout.modern-card>

            <x-layout.modern-card title="Jenis Tiket" class="mb-4">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th class="text-end">Harga</th>
                                <th class="text-center">Kuota</th>
                                <th class="text-center">Terjual</th>
                                <th class="text-center">Sisa</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($event->ticketTypes as $ticketType)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $ticketType->name }}</div>
                                        @if ($ticketType->description)
                                            <div class="small text-muted">{{ $ticketType->description }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end">Rp {{ number_format((int) $ticketType->price, 0, ',', '.') }}</td>
                                    <td class="text-center">{{ $ticketType->quota }}</td>
                                    <td class="text-center">{{ $ticketType->sold_count }}</td>
                                    <td class="text-center">{{ $ticketType->remainingQuota() }}</td>
                                    <td>
                                        <x-ui.badge :variant="$ticketType->is_active ? 'success' : 'secondary'">
                                            {{ $ticketType->saleStatusLabel() }}
                                        </x-ui.badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Belum ada jenis tiket.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-layout.modern-card>

            @if ($event->canAdjustSalesSettings())
                <x-layout.modern-card title="Pengaturan Penjualan Tiket" class="mb-4">
                    <p class="small text-muted">
                        Kuota dan jadwal jual dapat diubah kapan pun tanpa mengubah harga atau nama tiket.
                    </p>

                    @forelse ($event->ticketTypes as $ticketType)
                        <form method="POST" action="{{ route('admin.events.tickets.sales', [$event, $ticketType]) }}" class="border rounded p-3 mb-3">
                            @csrf
                            @method('PUT')
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <div class="fw-semibold">{{ $ticketType->name }}</div>
                                    <div class="small text-muted">
                                        Rp {{ number_format((int) $ticketType->price, 0, ',', '.') }} · Terjual {{ $ticketType->sold_count }}
                                    </div>
                                </div>
                                <x-ui.badge :variant="$ticketType->is_active ? 'success' : 'secondary'">
                                    {{ $ticketType->saleStatusLabel() }}
                                </x-ui.badge>
                            </div>

                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label small" for="admin-quota-{{ $ticketType->getKey() }}">Kuota</label>
                                    <input type="number" id="admin-quota-{{ $ticketType->getKey() }}" name="quota" value="{{ $ticketType->quota }}" class="form-control form-control-sm" min="{{ max(1, $ticketType->sold_count) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small" for="admin-sale-start-{{ $ticketType->getKey() }}">Jual mulai</label>
                                    <input type="datetime-local" id="admin-sale-start-{{ $ticketType->getKey() }}" name="sale_start_at" value="{{ $ticketType->sale_start_at?->copy()->setTimezone($event->timezoneLabel())->format('Y-m-d\TH:i') }}" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small" for="admin-sale-end-{{ $ticketType->getKey() }}">Jual sampai</label>
                                    <input type="datetime-local" id="admin-sale-end-{{ $ticketType->getKey() }}" name="sale_end_at" value="{{ $ticketType->sale_end_at?->copy()->setTimezone($event->timezoneLabel())->format('Y-m-d\TH:i') }}" class="form-control form-control-sm">
                                </div>
                                <div class="col-12 d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <input type="hidden" name="is_active" value="0">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="admin-active-{{ $ticketType->getKey() }}" @checked($ticketType->is_active)>
                                        <label class="form-check-label small" for="admin-active-{{ $ticketType->getKey() }}">Aktif dijual</label>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-floppy-disk me-1"></i>Simpan Penjualan
                                    </button>
                                </div>
                            </div>
                        </form>
                    @empty
                        <p class="text-muted mb-0">Belum ada jenis tiket.</p>
                    @endforelse
                </x-layout.modern-card>
            @endif

            <x-layout.modern-card title="Aksi Moderasi">
                @if ($event->status === \App\Models\Event::STATUS_REJECTED && $event->rejection_reason)
                    <div class="alert alert-danger mb-3">
                        <strong>Alasan penolakan:</strong> {{ $event->rejection_reason }}
                    </div>
                @endif

                @if ($isPending)
                    <div class="row g-3">
                        <div class="col-md-5">
                            <form method="POST" action="{{ route('admin.events.approve', $event) }}" onsubmit="return confirm('Publikasikan event ini?');">
                                @csrf
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-check me-1"></i>Setujui & Publikasikan
                                </button>
                            </form>
                            <p class="small text-muted mt-2 mb-0">
                                Status berubah menjadi <strong>Published</strong> dan organizer menerima email persetujuan.
                            </p>
                        </div>
                        <div class="col-md-7">
                            <form method="POST" action="{{ route('admin.events.reject', $event) }}" class="d-flex flex-column gap-2">
                                @csrf
                                <textarea name="rejection_reason" rows="3" class="form-control" maxlength="2000" placeholder="Alasan penolakan (wajib)" required>{{ old('rejection_reason') }}</textarea>
                                <button type="submit" class="btn btn-outline-danger">
                                    <i class="fas fa-xmark me-1"></i>Tolak & Kembalikan ke Organizer
                                </button>
                            </form>
                            <p class="small text-muted mt-1 mb-0">Alasan penolakan wajib diisi dan akan dikirim ke organizer.</p>
                        </div>
                    </div>
                @elseif ($isCancellable)
                    <div class="alert alert-warning">
                        <strong>Perhatian:</strong> pembatalan event akan membatalkan seluruh pesanan lunas dan mengajukan refund otomatis.
                    </div>
                    <form method="POST" action="{{ route('admin.events.cancel') }}" onsubmit="return confirm('Batalkan event ini dan proses refund semua pesanan lunas?');">
                        @csrf
                        <input type="hidden" name="event_id" value="{{ $event->getKey() }}">
                        <div class="mb-2">
                            <label for="cancel-reason" class="form-label small fw-semibold">Alasan pembatalan</label>
                            <textarea id="cancel-reason" name="reason" rows="3" class="form-control" minlength="5" maxlength="1000" placeholder="Alasan pembatalan event (wajib)" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="fas fa-ban me-1"></i>Batalkan Event & Proses Refund
                        </button>
                    </form>
                    <p class="small text-muted mt-2 mb-0">
                        {{ $event->paid_orders_count }} pesanan lunas akan masuk refund batch. Organizer menerima email pembatalan.
                    </p>
                @else
                    <p class="text-muted mb-0">
                        Tidak ada aksi moderasi yang tersedia untuk status
                        <strong>{{ $event->statusLabel() }}</strong>.
                    </p>
                @endif
            </x-layout.modern-card>
        </div>
    </div>
</x-layouts.app>
