<x-layouts.app title="Buat Event - Nusa Event" brand-name="Nusa Event" brand-icon="fas fa-ticket">
    <x-layout.page-header title="Buat Event" subtitle="Simpan sebagai draft terlebih dahulu. Ajukan review setelah field wajib dan minimal satu tiket aktif tersedia.">
        <x-slot:actions>
            <a href="{{ route('organizer.events.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Kembali
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

    @include('organizer.events._form')
</x-layouts.app>
