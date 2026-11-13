<x-layouts.app title="Banner - Admin">
    @php
        $indexQuery = array_filter([
            'q' => $search,
            'status' => $status,
            'page' => request('page'),
        ], fn ($value) => $value !== null && $value !== '');

        $closeUrl = route('admin.banners', $indexQuery);
    @endphp

    <x-layout.page-header title="Banner" subtitle="Kelola banner promosi yang tampil di halaman discovery event.">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="fas fa-plus" :href="route('admin.banners', $indexQuery + ['create' => 1])">
                Tambah Banner
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
        <form method="GET" action="{{ route('admin.banners') }}" class="row g-2 align-items-end mb-4">
            <div class="col-md-5">
                <label for="q" class="form-label small fw-semibold">Cari banner</label>
                <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="URL, event, atau tautan">
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
                    <a href="{{ route('admin.banners') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-semibold text-body">Daftar Banner</h5>
            <span class="small text-muted">{{ $banners->total() }} banner</span>
        </div>

        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Banner</th>
                        <th>Event</th>
                        <th>Periode</th>
                        <th class="text-center">Urutan</th>
                        <th>Status</th>
                        <th style="width: 170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($banners as $banner)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{ $banner->image_url }}" alt="Banner {{ $banner->event?->title ?? 'event' }}" class="rounded border" style="width: 120px; height: 48px; object-fit: cover;">
                                    <div class="small text-muted text-truncate" style="max-width: 240px;">{{ $banner->image_url }}</div>
                                </div>
                                @if ($banner->link_url)
                                    <div class="small text-muted text-truncate mt-1" style="max-width: 360px;">{{ $banner->link_url }}</div>
                                @endif
                            </td>
                            <td class="small">{{ $banner->event?->title ?? '—' }}</td>
                            <td class="small">
                                <div>Mulai: {{ $banner->start_at?->translatedFormat('d M Y, H.i') ?? '—' }}</div>
                                <div>Selesai: {{ $banner->end_at?->translatedFormat('d M Y, H.i') ?? '—' }}</div>
                            </td>
                            <td class="text-center">{{ $banner->sort_order }}</td>
                            <td>
                                <x-ui.badge :variant="$banner->is_active ? 'success' : 'danger'">
                                    {{ $banner->is_active ? 'Aktif' : 'Nonaktif' }}
                                </x-ui.badge>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <x-ui.btn-edit :href="route('admin.banners', $indexQuery + ['edit' => $banner->id])" tooltip="Edit banner" />
                                    <x-ui.btn-delete :href="route('admin.banners', $indexQuery + ['delete' => $banner->id])" tooltip="Hapus banner" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada banner yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($banners->hasPages())
            <div class="d-flex justify-content-end mt-4">{{ $banners->links() }}</div>
        @endif
    </x-layout.modern-card>

    @php
        $eventOptions = $events;
    @endphp

    @if ($showCreateModal && ! $editingBanner && ! $deletingBanner)
        <x-layout.modal-form title="Tambah Banner" :action="route('admin.banners.store')" :close-url="$closeUrl" submit-label="Simpan Banner">
            <div class="mb-3">
                <label for="create-event" class="form-label">Event terkait</label>
                <select id="create-event" name="event_id" class="form-select">
                    <option value="">Tanpa event</option>
                    @foreach ($eventOptions as $event)
                        <option value="{{ $event->id }}" @selected(old('event_id') === $event->id)>{{ $event->title }} ({{ $event->status }})</option>
                    @endforeach
                </select>
                @error('event_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <x-form.input name="image_url" label="URL gambar" :value="old('image_url')" placeholder="https://..." required :error="$errors->first('image_url')" />
            <x-form.input name="link_url" label="URL tautan" :value="old('link_url')" placeholder="https://..." :error="$errors->first('link_url')" />
            <div class="row">
                <div class="col-md-6">
                    <x-form.input type="datetime-local" name="start_at" label="Mulai tampil" :value="old('start_at')" :error="$errors->first('start_at')" />
                </div>
                <div class="col-md-6">
                    <x-form.input type="datetime-local" name="end_at" label="Selesai tampil" :value="old('end_at')" :error="$errors->first('end_at')" />
                </div>
            </div>
            <x-form.input type="number" name="sort_order" label="Urutan" :value="old('sort_order', 0)" min="0" :error="$errors->first('sort_order')" />
            <div class="mb-3">
                <label for="create-active" class="form-label">Status</label>
                <select id="create-active" name="is_active" class="form-select" required>
                    <option value="1" @selected(old('is_active', '1') == '1')>Aktif</option>
                    <option value="0" @selected(old('is_active') === '0')>Nonaktif</option>
                </select>
            </div>
        </x-layout.modal-form>
    @endif

    @if ($editingBanner && ! $deletingBanner)
        <x-layout.modal-form title="Edit Banner" :action="route('admin.banners.update', $editingBanner)" method="PUT" :close-url="$closeUrl" submit-label="Perbarui Banner">
            <div class="mb-3">
                <label for="edit-event" class="form-label">Event terkait</label>
                <select id="edit-event" name="event_id" class="form-select">
                    <option value="">Tanpa event</option>
                    @foreach ($eventOptions as $event)
                        <option value="{{ $event->id }}" @selected(old('event_id', $editingBanner->event_id) === $event->id)>{{ $event->title }} ({{ $event->status }})</option>
                    @endforeach
                </select>
                @error('event_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <x-form.input name="image_url" label="URL gambar" :value="old('image_url', $editingBanner->image_url)" required :error="$errors->first('image_url')" />
            <x-form.input name="link_url" label="URL tautan" :value="old('link_url', $editingBanner->link_url)" :error="$errors->first('link_url')" />
            <div class="row">
                <div class="col-md-6">
                    <x-form.input type="datetime-local" name="start_at" label="Mulai tampil" :value="old('start_at', $editingBanner->start_at?->format('Y-m-d\TH:i'))" :error="$errors->first('start_at')" />
                </div>
                <div class="col-md-6">
                    <x-form.input type="datetime-local" name="end_at" label="Selesai tampil" :value="old('end_at', $editingBanner->end_at?->format('Y-m-d\TH:i'))" :error="$errors->first('end_at')" />
                </div>
            </div>
            <x-form.input type="number" name="sort_order" label="Urutan" :value="old('sort_order', $editingBanner->sort_order)" min="0" :error="$errors->first('sort_order')" />
            <div class="mb-3">
                <label for="edit-active" class="form-label">Status</label>
                <select id="edit-active" name="is_active" class="form-select" required>
                    <option value="1" @selected(old('is_active', $editingBanner->is_active ? '1' : '0') == '1')>Aktif</option>
                    <option value="0" @selected(old('is_active', $editingBanner->is_active ? '1' : '0') === '0')>Nonaktif</option>
                </select>
            </div>
        </x-layout.modal-form>
    @endif

    @if ($deletingBanner)
        <x-ui.confirm-modal
            :show="true"
            title="Hapus Banner"
            :message="'Hapus banner ini? Tindakan ini tidak dapat dibatalkan.'"
            confirm-text="Hapus Banner"
            :action="route('admin.banners.destroy', $deletingBanner)"
            method="DELETE"
            :cancel-url="$closeUrl"
            variant="danger"
        >
            <img src="{{ $deletingBanner->image_url }}" alt="Banner" class="img-fluid rounded border mb-2">
        </x-ui.confirm-modal>
    @endif
</x-layouts.app>
