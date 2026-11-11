<x-layouts.app title="Profil Organizer - LinyTix" brand-name="LinyTix" brand-icon="fas fa-ticket">
    <x-layout.page-header title="Profil Organizer" subtitle="Kelola identitas publik, kontak, dan rekening pencairan organizer.">
        <x-slot:actions>
            @if ($organizer->is_verified)
                <x-ui.badge variant="success">Terverifikasi</x-ui.badge>
            @else
                <x-ui.badge variant="warning">Belum terverifikasi</x-ui.badge>
            @endif
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

    @if (! $organizer->is_verified)
        <div class="alert alert-warning">
            Profil belum diverifikasi admin. Anda tetap dapat menyiapkan event draft, tetapi belum dapat submit review atau publish.
            @if ($organizer->verification_note)
                <div class="mt-2"><strong>Catatan admin:</strong> {{ $organizer->verification_note }}</div>
            @endif
        </div>
    @elseif ($organizer->verification_note)
        <div class="alert alert-info">
            <strong>Catatan admin:</strong> {{ $organizer->verification_note }}
        </div>
    @endif

    <form method="POST" action="{{ route('organizer.profile.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-7">
                <x-layout.modern-card title="Identitas Organizer" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label for="name" class="form-label small fw-semibold">Nama organizer</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $organizer->name) }}" class="form-control @error('name') is-invalid @enderror" required maxlength="255">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-5">
                            <label for="slug" class="form-label small fw-semibold">Slug</label>
                            <div class="input-group">
                                <span class="input-group-text">/organizers/</span>
                                <input type="text" id="slug" name="slug" value="{{ old('slug', $organizer->slug) }}" class="form-control @error('slug') is-invalid @enderror" required maxlength="255">
                                @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-text">Slug unik. Jika diubah dan organizer sudah memiliki event publik, URL lama otomatis dialihkan (301).</div>
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label small fw-semibold">Deskripsi</label>
                            <textarea id="description" name="description" rows="4" maxlength="5000" class="form-control @error('description') is-invalid @enderror">{{ old('description', $organizer->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label for="logo_url" class="form-label small fw-semibold">URL logo</label>
                            <input type="url" id="logo_url" name="logo_url" value="{{ old('logo_url', $organizer->logo_url) }}" class="form-control @error('logo_url') is-invalid @enderror" maxlength="2048" placeholder="https://contoh.com/logo.png">
                            @error('logo_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if ($organizer->logoUrl())
                                <div class="mt-2">
                                    <img src="{{ $organizer->logoUrl() }}" alt="Logo {{ $organizer->name }}" style="max-height: 72px; max-width: 200px; object-fit: contain;">
                                </div>
                            @endif
                        </div>
                    </div>
                </x-layout.modern-card>

                <x-layout.modern-card title="Kontak">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="contact_email" class="form-label small fw-semibold">Email kontak</label>
                            <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $organizer->contact_email) }}" class="form-control @error('contact_email') is-invalid @enderror" maxlength="255">
                            @error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="contact_phone" class="form-label small fw-semibold">Telepon</label>
                            <input type="text" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $organizer->contact_phone) }}" class="form-control @error('contact_phone') is-invalid @enderror" maxlength="30">
                            @error('contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </x-layout.modern-card>
            </div>

            <div class="col-lg-5">
                <x-layout.modern-card title="Rekening Pencairan" class="mb-4">
                    <p class="text-muted small">
                        Data rekening hanya dapat dilihat oleh owner organizer dan admin. Nomor rekening dimasking pada audit log.
                    </p>

                    <div class="mb-3">
                        <label for="bank_name" class="form-label small fw-semibold">Nama bank</label>
                        <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name', $organizer->bank_name) }}" class="form-control @error('bank_name') is-invalid @enderror" maxlength="255" placeholder="Contoh: Bank BCA">
                        @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label for="bank_account_no" class="form-label small fw-semibold">Nomor rekening</label>
                        <input type="text" id="bank_account_no" name="bank_account_no" value="{{ old('bank_account_no', $organizer->bank_account_no) }}" class="form-control @error('bank_account_no') is-invalid @enderror" maxlength="50" inputmode="numeric" autocomplete="off">
                        @error('bank_account_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="bank_account_name" class="form-label small fw-semibold">Nama pemilik rekening</label>
                        <input type="text" id="bank_account_name" name="bank_account_name" value="{{ old('bank_account_name', $organizer->bank_account_name) }}" class="form-control @error('bank_account_name') is-invalid @enderror" maxlength="255">
                        @error('bank_account_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </x-layout.modern-card>

                <x-layout.modern-card title="Status Verifikasi">
                    <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                        <span class="text-secondary">Status</span>
                        @if ($organizer->is_verified)
                            <x-ui.badge variant="success">Terverifikasi</x-ui.badge>
                        @else
                            <x-ui.badge variant="warning">Belum terverifikasi</x-ui.badge>
                        @endif
                    </div>
                    <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                        <span class="text-secondary">Diverifikasi pada</span>
                        <span class="small">{{ $organizer->verified_at?->translatedFormat('d M Y, H.i') ?? '—' }}</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between py-2">
                        <span class="text-secondary">Diverifikasi oleh</span>
                        <span class="small">{{ $organizer->verifier?->name ?? '—' }}</span>
                    </div>

                    <button type="submit" class="btn btn-main w-100 mt-3">
                        <i class="fas fa-floppy-disk me-1"></i> Simpan Perubahan
                    </button>
                </x-layout.modern-card>
            </div>
        </div>
    </form>
</x-layouts.app>
