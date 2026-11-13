<x-layouts.app title="Venue - Admin">
    @php
        $indexQuery = array_filter([
            'q' => $search,
            'status' => $status,
            'page' => request('page'),
        ], fn ($value) => $value !== null && $value !== '');

        $closeUrl = route('admin.venues', $indexQuery);
    @endphp

    <x-layout.page-header title="Venue" subtitle="Kelola lokasi event beserta kapasitas dan koordinatnya.">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="fas fa-plus" :href="route('admin.venues', $indexQuery + ['create' => 1])">
                Tambah Venue
            </x-ui.button>
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

    <x-layout.modern-card>
        <form method="GET" action="{{ route('admin.venues') }}" class="row g-2 align-items-end mb-4">
            <div class="col-md-5">
                <label for="q" class="form-label small fw-semibold">Cari venue</label>
                <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Nama, slug, kota, atau provinsi">
            </div>
            <div class="col-md-3">
                <label for="status" class="form-label small fw-semibold">Status</label>
                <select id="status" name="status" class="form-select">
                    <option value="">Semua status</option>
                    <option value="active" @selected($status === 'active')>Aktif</option>
                    <option value="inactive" @selected($status === 'inactive')>Nonaktif</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Filter</button>
                @if ($search !== '' || $status !== '')
                    <a href="{{ route('admin.venues') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-semibold text-body">Daftar Venue</h5>
            <span class="small text-muted">{{ $venues->total() }} venue</span>
        </div>

        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Venue</th>
                        <th>Kota</th>
                        <th class="text-center">Kapasitas</th>
                        <th class="text-center">Event</th>
                        <th>Status</th>
                        <th style="width: 170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($venues as $venue)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $venue->name }}</div>
                                <div class="small text-muted">{{ $venue->slug }}</div>
                                <div class="small text-muted text-truncate" style="max-width: 300px;">{{ $venue->address }}</div>
                            </td>
                            <td>
                                <div>{{ $venue->city }}</div>
                                <div class="small text-muted">{{ $venue->province }}</div>
                            </td>
                            <td class="text-center">{{ $venue->capacity ? number_format($venue->capacity, 0, ',', '.') : '—' }}</td>
                            <td class="text-center">{{ $venue->events_count }}</td>
                            <td>
                                <x-ui.badge :variant="$venue->is_active ? 'success' : 'danger'">
                                    {{ $venue->is_active ? 'Aktif' : 'Nonaktif' }}
                                </x-ui.badge>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <x-ui.btn-edit :href="route('admin.venues', $indexQuery + ['edit' => $venue->id])" tooltip="Edit venue" />
                                    <x-ui.btn-delete :href="route('admin.venues', $indexQuery + ['delete' => $venue->id])" tooltip="Hapus venue" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada venue yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($venues->hasPages())
            <div class="d-flex justify-content-end mt-4">{{ $venues->links() }}</div>
        @endif
    </x-layout.modern-card>

    @if ($showCreateModal && ! $editingVenue && ! $deletingVenue)
        <x-layout.modal-form title="Tambah Venue" :action="route('admin.venues.store')" :close-url="$closeUrl" submit-label="Simpan Venue">
            <x-form.input name="name" label="Nama venue" :value="old('name')" placeholder="Contoh: Istora Senayan" required :error="$errors->first('name')" />
            <x-form.input name="slug" label="Slug" :value="old('slug')" placeholder="Kosongkan untuk otomatis dari nama" hint="(opsional, harus unik)" :error="$errors->first('slug')" />
            <div class="mb-3">
                <label for="create-address" class="form-label">Alamat</label>
                <textarea id="create-address" name="address" rows="2" class="form-control" required>{{ old('address') }}</textarea>
                @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6">
                    <x-form.input name="city" label="Kota" :value="old('city')" required :error="$errors->first('city')" />
                </div>
                <div class="col-md-6">
                    <x-form.input name="province" label="Provinsi" :value="old('province')" required :error="$errors->first('province')" />
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <x-form.input type="number" name="capacity" label="Kapasitas" :value="old('capacity')" min="1" :error="$errors->first('capacity')" />
                </div>
                <div class="col-md-4">
                    <x-form.input type="number" name="latitude" label="Latitude" :value="old('latitude')" step="any" :error="$errors->first('latitude')" />
                </div>
                <div class="col-md-4">
                    <x-form.input type="number" name="longitude" label="Longitude" :value="old('longitude')" step="any" :error="$errors->first('longitude')" />
                </div>
            </div>
            <x-form.input name="maps_url" label="URL maps" :value="old('maps_url')" placeholder="https://maps.google.com/..." :error="$errors->first('maps_url')" />
            <div class="mb-3">
                <label for="create-active" class="form-label">Status</label>
                <select id="create-active" name="is_active" class="form-select" required>
                    <option value="1" @selected(old('is_active', '1') == '1')>Aktif</option>
                    <option value="0" @selected(old('is_active') === '0')>Nonaktif</option>
                </select>
            </div>
        </x-layout.modal-form>
    @endif

    @if ($editingVenue && ! $deletingVenue)
        <x-layout.modal-form title="Edit Venue" :action="route('admin.venues.update', $editingVenue)" method="PUT" :close-url="$closeUrl" submit-label="Perbarui Venue">
            <x-form.input name="name" label="Nama venue" :value="old('name', $editingVenue->name)" required :error="$errors->first('name')" />
            <x-form.input name="slug" label="Slug" :value="old('slug', $editingVenue->slug)" :error="$errors->first('slug')" />
            <div class="mb-3">
                <label for="edit-address" class="form-label">Alamat</label>
                <textarea id="edit-address" name="address" rows="2" class="form-control" required>{{ old('address', $editingVenue->address) }}</textarea>
                @error('address')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="row">
                <div class="col-md-6">
                    <x-form.input name="city" label="Kota" :value="old('city', $editingVenue->city)" required :error="$errors->first('city')" />
                </div>
                <div class="col-md-6">
                    <x-form.input name="province" label="Provinsi" :value="old('province', $editingVenue->province)" required :error="$errors->first('province')" />
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <x-form.input type="number" name="capacity" label="Kapasitas" :value="old('capacity', $editingVenue->capacity)" min="1" :error="$errors->first('capacity')" />
                </div>
                <div class="col-md-4">
                    <x-form.input type="number" name="latitude" label="Latitude" :value="old('latitude', $editingVenue->latitude)" step="any" :error="$errors->first('latitude')" />
                </div>
                <div class="col-md-4">
                    <x-form.input type="number" name="longitude" label="Longitude" :value="old('longitude', $editingVenue->longitude)" step="any" :error="$errors->first('longitude')" />
                </div>
            </div>
            <x-form.input name="maps_url" label="URL maps" :value="old('maps_url', $editingVenue->maps_url)" :error="$errors->first('maps_url')" />
            <div class="mb-3">
                <label for="edit-active" class="form-label">Status</label>
                <select id="edit-active" name="is_active" class="form-select" required>
                    <option value="1" @selected(old('is_active', $editingVenue->is_active ? '1' : '0') == '1')>Aktif</option>
                    <option value="0" @selected(old('is_active', $editingVenue->is_active ? '1' : '0') === '0')>Nonaktif</option>
                </select>
            </div>
        </x-layout.modal-form>
    @endif

    @if ($deletingVenue)
        <x-ui.confirm-modal
            :show="true"
            title="Hapus Venue"
            :message="'Hapus venue ' . $deletingVenue->name . '? Tindakan ini tidak dapat dibatalkan.'"
            confirm-text="Hapus Venue"
            :action="route('admin.venues.destroy', $deletingVenue)"
            method="DELETE"
            :cancel-url="$closeUrl"
            variant="danger"
        >
            @if ($deletingVenue->events_count > 0)
                <div class="alert alert-warning mb-0">
                    Venue ini masih digunakan oleh {{ $deletingVenue->events_count }} event dan tidak dapat dihapus.
                </div>
            @endif
        </x-ui.confirm-modal>
    @endif
</x-layouts.app>
