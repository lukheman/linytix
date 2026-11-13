<x-layouts.app title="FAQ - Admin">
    @php
        $indexQuery = array_filter([
            'q' => $search,
            'status' => $status,
            'page' => request('page'),
        ], fn ($value) => $value !== null && $value !== '');

        $closeUrl = route('admin.faqs', $indexQuery);
    @endphp

    <x-layout.page-header title="FAQ" subtitle="Kelola pertanyaan yang tampil di halaman bantuan publik.">
        <x-slot:actions>
            <x-ui.button variant="primary" icon="fas fa-plus" :href="route('admin.faqs', $indexQuery + ['create' => 1])">
                Tambah FAQ
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
        <form method="GET" action="{{ route('admin.faqs') }}" class="row g-2 align-items-end mb-4">
            <div class="col-md-5">
                <label for="q" class="form-label small fw-semibold">Cari FAQ</label>
                <input type="search" id="q" name="q" value="{{ $search }}" class="form-control" placeholder="Pertanyaan atau jawaban">
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
                    <a href="{{ route('admin.faqs') }}" class="btn btn-outline-secondary">Reset</a>
                @endif
            </div>
        </form>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 fw-semibold text-body">Daftar FAQ</h5>
            <span class="small text-muted">{{ $faqs->total() }} FAQ</span>
        </div>

        <div class="table-responsive">
            <table class="table table-modern align-middle">
                <thead>
                    <tr>
                        <th>Pertanyaan</th>
                        <th class="text-center">Urutan</th>
                        <th>Status</th>
                        <th style="width: 170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($faqs as $faq)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $faq->question }}</div>
                                <div class="small text-muted text-truncate" style="max-width: 560px;">{{ $faq->answer }}</div>
                            </td>
                            <td class="text-center">{{ $faq->sort_order }}</td>
                            <td>
                                <x-ui.badge :variant="$faq->is_active ? 'success' : 'danger'">
                                    {{ $faq->is_active ? 'Aktif' : 'Nonaktif' }}
                                </x-ui.badge>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <x-ui.btn-edit :href="route('admin.faqs', $indexQuery + ['edit' => $faq->id])" tooltip="Edit FAQ" />
                                    <x-ui.btn-delete :href="route('admin.faqs', $indexQuery + ['delete' => $faq->id])" tooltip="Hapus FAQ" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Belum ada FAQ yang cocok.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($faqs->hasPages())
            <div class="d-flex justify-content-end mt-4">{{ $faqs->links() }}</div>
        @endif
    </x-layout.modern-card>

    @if ($showCreateModal && ! $editingFaq && ! $deletingFaq)
        <x-layout.modal-form title="Tambah FAQ" :action="route('admin.faqs.store')" :close-url="$closeUrl" submit-label="Simpan FAQ">
            <div class="mb-3">
                <label for="create-question" class="form-label">Pertanyaan</label>
                <textarea id="create-question" name="question" rows="2" class="form-control" maxlength="2000" required>{{ old('question') }}</textarea>
                @error('question')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="create-answer" class="form-label">Jawaban</label>
                <textarea id="create-answer" name="answer" rows="4" class="form-control" maxlength="20000" required>{{ old('answer') }}</textarea>
                @error('answer')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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

    @if ($editingFaq && ! $deletingFaq)
        <x-layout.modal-form title="Edit FAQ" :action="route('admin.faqs.update', $editingFaq)" method="PUT" :close-url="$closeUrl" submit-label="Perbarui FAQ">
            <div class="mb-3">
                <label for="edit-question" class="form-label">Pertanyaan</label>
                <textarea id="edit-question" name="question" rows="2" class="form-control" maxlength="2000" required>{{ old('question', $editingFaq->question) }}</textarea>
                @error('question')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="edit-answer" class="form-label">Jawaban</label>
                <textarea id="edit-answer" name="answer" rows="4" class="form-control" maxlength="20000" required>{{ old('answer', $editingFaq->answer) }}</textarea>
                @error('answer')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <x-form.input type="number" name="sort_order" label="Urutan" :value="old('sort_order', $editingFaq->sort_order)" min="0" :error="$errors->first('sort_order')" />
            <div class="mb-3">
                <label for="edit-active" class="form-label">Status</label>
                <select id="edit-active" name="is_active" class="form-select" required>
                    <option value="1" @selected(old('is_active', $editingFaq->is_active ? '1' : '0') == '1')>Aktif</option>
                    <option value="0" @selected(old('is_active', $editingFaq->is_active ? '1' : '0') === '0')>Nonaktif</option>
                </select>
            </div>
        </x-layout.modal-form>
    @endif

    @if ($deletingFaq)
        <x-ui.confirm-modal
            :show="true"
            title="Hapus FAQ"
            :message="'Hapus FAQ ini? Tindakan ini tidak dapat dibatalkan.'"
            confirm-text="Hapus FAQ"
            :action="route('admin.faqs.destroy', $deletingFaq)"
            method="DELETE"
            :cancel-url="$closeUrl"
            variant="danger"
        >
            <div class="small text-muted">{{ $deletingFaq->question }}</div>
        </x-ui.confirm-modal>
    @endif
</x-layouts.app>
