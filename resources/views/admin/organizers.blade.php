<x-layouts.app title="Organizer - Admin">
    <x-layout.page-header title="Organizer" subtitle="Verifikasi organizer dan pantau profil penyelenggara event.">
        <x-slot:actions>
            <a class="btn btn-outline-primary" href="{{ route('events.index') }}" target="_blank">
                <i class="fas fa-arrow-up-right-from-square me-2"></i>Lihat Publik
            </a>
        </x-slot:actions>
    </x-layout.page-header>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-layout.modern-card>
        <form method="GET" action="{{ route('admin.organizers') }}" class="row g-2 align-items-end mb-4">
            <div class="col-md-5">
                <label for="q" class="form-label small fw-semibold">Cari organizer</label>
                <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Nama, slug, email, atau owner">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label small fw-semibold">Status verifikasi</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Semua status</option>
                    <option value="verified" @selected($status === 'verified')>Terverifikasi</option>
                    <option value="unverified" @selected($status === 'unverified')>Belum terverifikasi</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i>Filter
                </button>
                @if ($search !== '' || $status !== '')
                    <a href="{{ route('admin.organizers') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Organizer</th>
                        <th>Owner</th>
                        <th>Kontak</th>
                        <th>Rekening</th>
                        <th class="text-center">Event</th>
                        <th>Status</th>
                        <th style="width: 320px;">Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($organizers as $organizer)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $organizer->name }}</div>
                                <a href="{{ route('organizers.show', $organizer->slug) }}" target="_blank" class="small text-decoration-none">
                                    /organizers/{{ $organizer->slug }}
                                    <i class="fas fa-arrow-up-right-from-square ms-1" style="font-size: .65rem;"></i>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-body">{{ $organizer->owner?->name ?? '—' }}</div>
                                <small class="text-muted">{{ $organizer->owner?->email }}</small>
                            </td>
                            <td>
                                <div class="small">{{ $organizer->contact_email ?? '—' }}</div>
                                <div class="small text-muted">{{ $organizer->contact_phone ?? '—' }}</div>
                            </td>
                            <td>
                                <div class="small">{{ $organizer->bank_name ?? '—' }}</div>
                                <div class="small text-muted">{{ $organizer->maskedBankAccountNo() ?? '—' }}</div>
                                <div class="small text-muted">{{ $organizer->bank_account_name }}</div>
                            </td>
                            <td class="text-center">{{ $organizer->events_count }}</td>
                            <td>
                                @if ($organizer->is_verified)
                                    <x-ui.badge variant="success">Terverifikasi</x-ui.badge>
                                @else
                                    <x-ui.badge variant="warning">Belum terverifikasi</x-ui.badge>
                                @endif
                                @if ($organizer->verified_at)
                                    <div class="small text-muted mt-1">{{ $organizer->verified_at->translatedFormat('d M Y, H.i') }}</div>
                                @endif
                                @if ($organizer->verification_note)
                                    <div class="small text-secondary mt-1" style="max-width: 220px;">{{ $organizer->verification_note }}</div>
                                @endif
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.organizers.verify', $organizer) }}" class="d-flex flex-column gap-2">
                                    @csrf
                                    <input type="text" name="verification_note" value="{{ $organizer->verification_note }}" class="form-control form-control-sm" maxlength="1000" placeholder="Catatan verifikasi (opsional)">
                                    <div class="d-flex gap-1">
                                        @if ($organizer->is_verified)
                                            <button type="submit" name="action" value="unverify" class="btn btn-sm btn-outline-warning">
                                                <i class="fas fa-xmark me-1"></i>Batalkan verifikasi
                                            </button>
                                        @else
                                            <button type="submit" name="action" value="verify" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-check me-1"></i>Verifikasi
                                            </button>
                                        @endif
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada organizer yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $organizers->links() }}
        </div>
    </x-layout.modern-card>
</x-layouts.app>
