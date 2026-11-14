<x-layouts.app title="Kelola Staff - Nusa Event" brand-name="Nusa Event" brand-icon="fas fa-ticket">
    <x-layout.page-header title="Kelola Staff" subtitle="{{ $event->title }}">
        <x-slot:actions>
            <a href="{{ route($backRoute) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
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
        <div class="col-xl-7">
            <x-layout.modern-card title="Staff Bertugas">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Gate</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($staff as $member)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $member->name }}</div>
                                        @if (! $member->is_active)
                                            <span class="badge text-bg-secondary">Tidak aktif</span>
                                        @endif
                                    </td>
                                    <td class="small">{{ $member->email }}</td>
                                    <td>
                                        <span class="badge text-bg-info">{{ $member->pivot->gate ?: 'Belum ditentukan' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route($destroyRoute, [$event, $member]) }}" onsubmit="return confirm('Hapus penugasan {{ $member->name }} dari event ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-user-minus me-1"></i>Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Belum ada staff yang ditugaskan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-layout.modern-card>

            <x-layout.modern-card title="Undangan Menunggu" class="mt-4">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Gate</th>
                                <th>Berlaku sampai</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($invitations as $invitation)
                                <tr>
                                    <td class="small">{{ $invitation->email }}</td>
                                    <td>
                                        <span class="badge text-bg-info">{{ $invitation->gate }}</span>
                                    </td>
                                    <td class="small text-muted">
                                        {{ $invitation->expires_at?->translatedFormat('d M Y, H.i') }}
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <form method="POST" action="{{ route($invitationResendRoute, [$event, $invitation]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Kirim ulang undangan">
                                                    <i class="fas fa-paper-plane me-1"></i>Kirim Ulang
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route($invitationDestroyRoute, [$event, $invitation]) }}" onsubmit="return confirm('Batalkan undangan untuk {{ $invitation->email }}?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Batalkan undangan">
                                                    <i class="fas fa-xmark me-1"></i>Batalkan
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Tidak ada undangan yang menunggu.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-layout.modern-card>
        </div>

        <div class="col-xl-5">
            <x-layout.modern-card title="Undang Staff">
                <form method="POST" action="{{ route($invitationStoreRoute, $event) }}" class="vstack gap-3">
                    @csrf

                    <div>
                        <label for="email" class="form-label small fw-semibold">Email staff</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control" placeholder="staff@example.com" required>
                        <div class="form-text">
                            Jika email sudah terdaftar, staff langsung ditugaskan. Jika belum, sistem mengirim
                            undangan untuk membuat akun.
                        </div>
                    </div>

                    <div>
                        <label for="gate" class="form-label small fw-semibold">Gate penugasan</label>
                        <input type="text" id="gate" name="gate" value="{{ old('gate', 'Gate Utama') }}" class="form-control" maxlength="100" placeholder="Contoh: Gate A" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Kirim Undangan
                    </button>
                </form>
            </x-layout.modern-card>

            <x-layout.modern-card title="Catatan" class="mt-4">
                <ul class="small text-secondary mb-0">
                    <li>Staff hanya dapat membuka halaman scan untuk event yang ditugaskan.</li>
                    <li>Staff tidak dapat mengubah data event maupun jenis tiket.</li>
                    <li>Gate ditampilkan pada halaman scan sebagai lokasi penugasan.</li>
                    <li>Link undangan berlaku 7 hari dan hanya dapat dipakai satu kali.</li>
                </ul>
            </x-layout.modern-card>
        </div>
    </div>
</x-layouts.app>
