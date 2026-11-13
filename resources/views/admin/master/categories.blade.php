<x-layouts.app title="Kategori - Admin">
    @php
        $indexQuery = array_filter([
            'q' => $search,
            'status' => $status,
            'page' => request('page'),
        ], fn ($value) => $value !== null && $value !== '');

        $closeUrl = route('admin.categories', $indexQuery);
    @endphp

    <x-layout.page-header title="Kategori" subtitle="Kelola kategori event yang tampil di halaman publik dan form organizer.">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="fas fa-plus" :href="route('admin.categories', $indexQuery + ['create' => 1])">
                Tambah Kategori
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
        <form method="GET" action="{{ route('admin.categories') }}" class="row g-2 align-items-end mb-4">
            <div class="col-md-5">
                <label for="q" class="form-label small fw-semibold">Cari kategori</label>
                <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Nama atau slug">
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
                    <a href="{{ route('admin.categories') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-semibold text-body">Daftar Kategori</h5>
            <span class="small text-muted">{{ $categories->total() }} kategori</span>
        </div>

        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Slug</th>
                        <th class="text-center">Urutan</th>
                        <th class="text-center">Event</th>
                        <th>Status</th>
                        <th style="width: 170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $category->name }}</div>
                                @if ($category->icon_url)
                                    <div class="small text-muted text-truncate" style="max-width: 260px;">{{ $category->icon_url }}</div>
                                @endif
                            </td>
                            <td class="text-secondary">{{ $category->slug }}</td>
                            <td class="text-center">{{ $category->sort_order }}</td>
                            <td class="text-center">{{ $category->events_count }}</td>
                            <td>
                                <x-ui.badge :variant="$category->is_active ? 'success' : 'danger'">
                                    {{ $category->is_active ? 'Aktif' : 'Nonaktif' }}
                                </x-ui.badge>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <x-ui.btn-edit :href="route('admin.categories', $indexQuery + ['edit' => $category->id])" tooltip="Edit kategori" />
                                    <x-ui.btn-delete :href="route('admin.categories', $indexQuery + ['delete' => $category->id])" tooltip="Hapus kategori" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada kategori yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($categories->hasPages())
            <div class="d-flex justify-content-end mt-4">{{ $categories->links() }}</div>
        @endif
    </x-layout.modern-card>

    @if ($showCreateModal && ! $editingCategory && ! $deletingCategory)
        <x-layout.modal-form title="Tambah Kategori" :action="route('admin.categories.store')" :close-url="$closeUrl" submit-label="Simpan Kategori">
            <x-form.input name="name" label="Nama kategori" :value="old('name')" placeholder="Contoh: Musik" required :error="$errors->first('name')" />
            <x-form.input name="slug" label="Slug" :value="old('slug')" placeholder="Kosongkan untuk otomatis dari nama" hint="(opsional, harus unik)" :error="$errors->first('slug')" />
            <x-form.input name="icon_url" label="URL ikon" :value="old('icon_url')" placeholder="https://..." :error="$errors->first('icon_url')" />
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

    @if ($editingCategory && ! $deletingCategory)
        <x-layout.modal-form title="Edit Kategori" :action="route('admin.categories.update', $editingCategory)" method="PUT" :close-url="$closeUrl" submit-label="Perbarui Kategori">
            <x-form.input name="name" label="Nama kategori" :value="old('name', $editingCategory->name)" required :error="$errors->first('name')" />
            <x-form.input name="slug" label="Slug" :value="old('slug', $editingCategory->slug)" hint="(harus unik)" :error="$errors->first('slug')" />
            <x-form.input name="icon_url" label="URL ikon" :value="old('icon_url', $editingCategory->icon_url)" placeholder="https://..." :error="$errors->first('icon_url')" />
            <x-form.input type="number" name="sort_order" label="Urutan" :value="old('sort_order', $editingCategory->sort_order)" min="0" :error="$errors->first('sort_order')" />
            <div class="mb-3">
                <label for="edit-active" class="form-label">Status</label>
                <select id="edit-active" name="is_active" class="form-select" required>
                    <option value="1" @selected(old('is_active', $editingCategory->is_active ? '1' : '0') == '1')>Aktif</option>
                    <option value="0" @selected(old('is_active', $editingCategory->is_active ? '1' : '0') === '0')>Nonaktif</option>
                </select>
            </div>
        </x-layout.modal-form>
    @endif

    @if ($deletingCategory)
        <x-ui.confirm-modal
            :show="true"
            title="Hapus Kategori"
            :message="'Hapus kategori ' . $deletingCategory->name . '? Tindakan ini tidak dapat dibatalkan.'"
            confirm-text="Hapus Kategori"
            :action="route('admin.categories.destroy', $deletingCategory)"
            method="DELETE"
            :cancel-url="$closeUrl"
            variant="danger"
        >
            @if ($deletingCategory->events_count > 0)
                <div class="alert alert-warning mb-0">
                    Kategori ini masih digunakan oleh {{ $deletingCategory->events_count }} event dan tidak dapat dihapus.
                </div>
            @endif
        </x-ui.confirm-modal>
    @endif
</x-layouts.app>
