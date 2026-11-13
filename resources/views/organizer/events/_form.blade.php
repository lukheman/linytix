@php
    $editing = isset($event);
    $fullyEditable = ! $editing || $event->isFullyEditable();
    $nonCriticalOnly = $editing && ! $fullyEditable && $event->allowsNonCriticalEdits();
    $readOnly = $editing && ! $fullyEditable && ! $nonCriticalOnly;
    $lineupRows = old('lineups', $editing
        ? $event->lineups->map(fn ($lineup) => [
            'id' => $lineup->getKey(),
            'name' => $lineup->name,
            'perform_at' => $lineup->perform_at?->copy()->setTimezone($event->timezoneLabel())->format('Y-m-d\TH:i'),
        ])->all()
        : [['name' => '']]);
    $lineupRows = $lineupRows ?: [['name' => '']];
@endphp

@if ($readOnly)
    <div class="alert alert-warning">
        Event berstatus <strong>{{ $event->statusLabel() }}</strong> dan tidak dapat diubah saat ini.
        @if ($event->status === 'pending_review')
            Tunggu keputusan admin. Jika ditolak, event akan kembali dapat diedit dan diajukan ulang.
        @endif
    </div>

    <x-layout.modern-card title="Ringkasan Event">
        <div class="row g-3">
            <div class="col-md-8">
                <div class="fw-semibold fs-5">{{ $event->title }}</div>
                <div class="text-muted">{{ $event->short_desc }}</div>
                <div class="mt-3" style="white-space: pre-line;">{{ $event->description }}</div>
            </div>
            <div class="col-md-4">
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-secondary">Status</span>
                    <x-events.status-badge :status="$event->status" />
                </div>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span class="text-secondary">Jadwal</span>
                    <span class="small">{{ $event->startsAtLocal()?->translatedFormat('d M Y, H.i') ?? '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-secondary">Tiket aktif</span>
                    <span class="small">{{ $event->ticketTypes->where('is_active', true)->count() }}</span>
                </div>
            </div>
        </div>
    </x-layout.modern-card>
@else
    <form method="POST" action="{{ $editing ? route('organizer.events.update', $event) : route('organizer.events.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        @if ($nonCriticalOnly)
            <div class="alert alert-info">
                Event sudah tayang. Anda hanya dapat mengubah <strong>deskripsi</strong> dan <strong>galeri</strong>.
                Field penjualan dan jadwal dikunci untuk menjaga konsistensi tiket yang sudah terjual.
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <x-layout.modern-card title="Informasi Event" class="mb-4">
                    @if ($fullyEditable)
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="title" class="form-label small fw-semibold">Judul event <span class="text-danger">*</span></label>
                                <input type="text" id="title" name="title" value="{{ old('title', $event->title ?? '') }}" class="form-control @error('title') is-invalid @enderror" required maxlength="255">
                                @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="category_id" class="form-label small fw-semibold">Kategori <span class="text-danger">*</span></label>
                                <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                    <option value="">Pilih kategori</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->getKey() }}" @selected((string) old('category_id', $event->category_id ?? '') === (string) $category->getKey())>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="short_desc" class="form-label small fw-semibold">Ringkasan singkat</label>
                                <input type="text" id="short_desc" name="short_desc" value="{{ old('short_desc', $event->short_desc ?? '') }}" class="form-control @error('short_desc') is-invalid @enderror" maxlength="500" placeholder="Satu kalimat yang menggambarkan event">
                                @error('short_desc')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    @endif

                    <div class="mt-3">
                        <label for="description" class="form-label small fw-semibold">Deskripsi <span class="text-danger">*</span></label>
                        <textarea id="description" name="description" rows="8" class="form-control @error('description') is-invalid @enderror" @if ($fullyEditable) required @endif maxlength="20000">{{ old('description', $event->description ?? '') }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    @if ($fullyEditable)
                        <div class="mt-3">
                            <label for="terms" class="form-label small fw-semibold">Syarat & ketentuan</label>
                            <textarea id="terms" name="terms" rows="4" class="form-control @error('terms') is-invalid @enderror" maxlength="20000">{{ old('terms', $event->terms ?? '') }}</textarea>
                            @error('terms')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endif
                </x-layout.modern-card>

                @if ($fullyEditable)
                    <x-layout.modern-card title="Waktu & Lokasi" class="mb-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="is_online" name="is_online" value="1" @checked(old('is_online', $event->is_online ?? false))>
                                    <label class="form-check-label" for="is_online">Event online</label>
                                </div>
                            </div>

                            <div class="col-md-6" id="venue-field">
                                <label for="venue_id" class="form-label small fw-semibold">Venue <span class="text-danger">*</span></label>
                                <select id="venue_id" name="venue_id" class="form-select @error('venue_id') is-invalid @enderror">
                                    <option value="">Pilih venue</option>
                                    @foreach ($venues as $venue)
                                        <option value="{{ $venue->getKey() }}" @selected((string) old('venue_id', $event->venue_id ?? '') === (string) $venue->getKey())>
                                            {{ $venue->name }} — {{ $venue->city }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('venue_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6 d-none" id="online-url-field">
                                <label for="online_url" class="form-label small fw-semibold">URL event online <span class="text-danger">*</span></label>
                                <input type="url" id="online_url" name="online_url" value="{{ old('online_url', $event->online_url ?? '') }}" class="form-control @error('online_url') is-invalid @enderror" maxlength="2048" placeholder="https://meet.example.com/event">
                                @error('online_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-4">
                                <label for="timezone" class="form-label small fw-semibold">Timezone <span class="text-danger">*</span></label>
                                <select id="timezone" name="timezone" class="form-select @error('timezone') is-invalid @enderror" required>
                                    @foreach ($timezones as $value => $label)
                                        <option value="{{ $value }}" @selected(old('timezone', $event->timezone ?? 'Asia/Jakarta') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('timezone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="start_at" class="form-label small fw-semibold">Mulai <span class="text-danger">*</span></label>
                                <input type="datetime-local" id="start_at" name="start_at" value="{{ old('start_at', $editing ? $event->startsAtLocal()?->format('Y-m-d\TH:i') : '') }}" class="form-control @error('start_at') is-invalid @enderror" required>
                                @error('start_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="end_at" class="form-label small fw-semibold">Selesai <span class="text-danger">*</span></label>
                                <input type="datetime-local" id="end_at" name="end_at" value="{{ old('end_at', $editing ? $event->endsAtLocal()?->format('Y-m-d\TH:i') : '') }}" class="form-control @error('end_at') is-invalid @enderror" required>
                                @error('end_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="doors_open_at" class="form-label small fw-semibold">Pintu dibuka</label>
                                <input type="datetime-local" id="doors_open_at" name="doors_open_at" value="{{ old('doors_open_at', $editing && $event->doors_open_at ? $event->doors_open_at->copy()->setTimezone($event->timezoneLabel())->format('Y-m-d\TH:i') : '') }}" class="form-control @error('doors_open_at') is-invalid @enderror">
                                @error('doors_open_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="require_identity" name="require_identity" value="1" @checked(old('require_identity', $event->require_identity ?? false))>
                                    <label class="form-check-label" for="require_identity">Wajib identitas saat check-in</label>
                                </div>
                            </div>
                        </div>
                    </x-layout.modern-card>
                @endif

                <x-layout.modern-card title="Galeri">
                    <div class="mb-3">
                        <label for="gallery" class="form-label small fw-semibold">Tambah gambar galeri</label>
                        <input type="file" id="gallery" name="gallery[]" class="form-control @error('gallery') is-invalid @enderror @error('gallery.*') is-invalid @enderror" accept="image/jpeg,image/png,image/webp" multiple>
                        @error('gallery')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @error('gallery.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Maksimal {{ config('ticketing.event_uploads.gallery_max') }} gambar per unggah. Format JPG, PNG, atau WEBP.</div>
                    </div>

                    @if ($editing && $event->images->isNotEmpty())
                        <div class="row g-3">
                            @foreach ($event->images as $image)
                                <div class="col-6 col-md-4">
                                    <div class="border rounded overflow-hidden h-100">
                                        <img src="{{ $image->image_url }}" alt="{{ $image->caption ?: 'Galeri '.$event->title }}" class="w-100" style="height: 120px; object-fit: cover;">
                                        <div class="p-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="remove_images[]" value="{{ $image->getKey() }}" id="remove-image-{{ $image->getKey() }}">
                                                <label class="form-check-label small text-danger" for="remove-image-{{ $image->getKey() }}">Hapus gambar</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted small mb-0">Belum ada gambar galeri.</p>
                    @endif
                </x-layout.modern-card>
            </div>

            <div class="col-lg-4">
                @if ($fullyEditable)
                    <x-layout.modern-card title="Poster & Banner" class="mb-4">
                        <div class="mb-4">
                            <label for="poster" class="form-label small fw-semibold">Poster <span class="text-danger">*</span></label>
                            <input type="file" id="poster" name="poster" class="form-control @error('poster') is-invalid @enderror" accept="image/jpeg,image/png,image/webp" @if (! $editing) required @endif>
                            @error('poster')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">JPG/PNG/WEBP, min. 600×400 px, maks. 4 MB.</div>
                            @if ($editing && $event->poster_url)
                                <img src="{{ $event->poster_url }}" alt="Poster saat ini" class="img-fluid rounded mt-3" style="max-height: 220px; object-fit: cover;">
                            @endif
                        </div>

                        <div>
                            <label for="banner" class="form-label small fw-semibold">Banner</label>
                            <input type="file" id="banner" name="banner" class="form-control @error('banner') is-invalid @enderror" accept="image/jpeg,image/png,image/webp">
                            @error('banner')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">JPG/PNG/WEBP, min. 1200×400 px, maks. 4 MB.</div>
                            @if ($editing && $event->banner_url)
                                <img src="{{ $event->banner_url }}" alt="Banner saat ini" class="img-fluid rounded mt-3" style="max-height: 120px; object-fit: cover;">
                            @endif
                        </div>
                    </x-layout.modern-card>

                    <x-layout.modern-card title="Lineup" class="mb-4">
                        <input type="hidden" name="lineups_present" value="1">
                        <div id="lineup-rows">
                            @foreach ($lineupRows as $index => $lineup)
                                <div class="border rounded p-3 mb-2 lineup-row">
                                    <input type="hidden" name="lineups[{{ $index }}][id]" value="{{ $lineup['id'] ?? '' }}">
                                    <div class="mb-2">
                                        <input type="text" name="lineups[{{ $index }}][name]" value="{{ $lineup['name'] ?? '' }}" class="form-control form-control-sm" placeholder="Nama pengisi acara">
                                    </div>
                                    <div class="mb-2">
                                        <input type="datetime-local" name="lineups[{{ $index }}][perform_at]" value="{{ $lineup['perform_at'] ?? '' }}" class="form-control form-control-sm">
                                    </div>
                                    <div class="d-flex gap-2 align-items-center">
                                        <input type="file" name="lineups[{{ $index }}][photo]" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-lineup" title="Hapus baris">&times;</button>
                                    </div>
                                    @error('lineups.'.$index.'.photo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="add-lineup">
                            <i class="fas fa-plus me-1"></i>Tambah lineup
                        </button>
                        @error('lineups')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                    </x-layout.modern-card>
                @endif

                <x-layout.modern-card title="Status & Aksi" class="mb-4">
                    @if ($editing)
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-secondary">Status</span>
                            <x-events.status-badge :status="$event->status" />
                        </div>
                        @if ($event->status === 'rejected' && $event->rejection_reason)
                            <div class="alert alert-danger small mt-3 mb-0">
                                <strong>Alasan penolakan:</strong> {{ $event->rejection_reason }}
                            </div>
                        @endif
                    @endif

                    <button type="submit" class="btn btn-primary w-100 mt-3">
                        <i class="fas fa-floppy-disk me-1"></i>{{ $editing ? 'Simpan Perubahan' : 'Simpan Draft' }}
                    </button>

                    @if ($editing && $event->isFullyEditable())
                        <a href="{{ route('organizer.events.tickets.index', $event) }}" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="fas fa-ticket me-1"></i>Kelola Jenis Tiket
                        </a>
                    @endif

                    <a href="{{ route('organizer.events.index') }}" class="btn btn-link w-100 mt-1">Kembali ke daftar event</a>
                </x-layout.modern-card>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            (function () {
                const online = document.getElementById('is_online');
                const venueField = document.getElementById('venue-field');
                const onlineUrlField = document.getElementById('online-url-field');

                function toggleOnline() {
                    if (!online) return;
                    venueField?.classList.toggle('d-none', online.checked);
                    onlineUrlField?.classList.toggle('d-none', !online.checked);
                }

                online?.addEventListener('change', toggleOnline);
                toggleOnline();

                const rows = document.getElementById('lineup-rows');
                const addButton = document.getElementById('add-lineup');

                function reindex() {
                    if (!rows) return;
                    rows.querySelectorAll('.lineup-row').forEach((row, index) => {
                        row.querySelectorAll('input').forEach((input) => {
                            input.name = input.name.replace(/lineups\[\d+]/, `lineups[${index}]`);
                        });
                    });
                }

                addButton?.addEventListener('click', function () {
                    if (!rows) return;
                    const index = rows.querySelectorAll('.lineup-row').length;
                    const wrapper = document.createElement('div');
                    wrapper.className = 'border rounded p-3 mb-2 lineup-row';
                    wrapper.innerHTML = `
                        <input type="hidden" name="lineups[${index}][id]" value="">
                        <div class="mb-2"><input type="text" name="lineups[${index}][name]" class="form-control form-control-sm" placeholder="Nama pengisi acara"></div>
                        <div class="mb-2"><input type="datetime-local" name="lineups[${index}][perform_at]" class="form-control form-control-sm"></div>
                        <div class="d-flex gap-2 align-items-center">
                            <input type="file" name="lineups[${index}][photo]" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-lineup" title="Hapus baris">&times;</button>
                        </div>
                    `;
                    rows.appendChild(wrapper);
                });

                rows?.addEventListener('click', function (event) {
                    if (event.target.closest('.remove-lineup')) {
                        event.target.closest('.lineup-row')?.remove();
                        reindex();
                    }
                });
            })();
        </script>
    @endpush
@endif
