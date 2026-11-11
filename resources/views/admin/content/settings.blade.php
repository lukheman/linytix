@php
    $sections = [
        'Identitas & Beranda Ticketing' => [
            'site_name' => ['label' => 'Nama Situs', 'type' => 'text'],
            'footer_tagline' => ['label' => 'Tagline Footer', 'type' => 'text'],
            'hero_eyebrow' => ['label' => 'Hero — Label Kecil', 'type' => 'text'],
            'hero_title' => ['label' => 'Hero — Judul Utama', 'type' => 'text'],
            'hero_subtitle' => ['label' => 'Hero — Subjudul', 'type' => 'textarea'],
            'hero_note' => ['label' => 'Hero — Catatan Kepercayaan', 'type' => 'text'],
        ],
        'Bantuan Pembeli' => [
            'email' => ['label' => 'Email Dukungan', 'type' => 'email'],
            'whatsapp_number' => ['label' => 'Nomor WhatsApp', 'type' => 'text', 'hint' => 'Gunakan format internasional, contoh 6281234567890.'],
            'whatsapp_template' => ['label' => 'Template Pesan WhatsApp', 'type' => 'textarea'],
        ],
    ];
@endphp

<x-layouts.app title="Pengaturan Ticketing - Admin">
    <x-layout.page-header title="Pengaturan Ticketing" subtitle="Kelola identitas platform dan kanal bantuan pembeli." />

    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        @foreach ($sections as $sectionTitle => $fields)
            <x-layout.modern-card class="mb-4">
                <h2 class="h5 mb-3">{{ $sectionTitle }}</h2>
                <div class="row g-3">
                    @foreach ($fields as $key => $field)
                        <div class="col-md-{{ $field['type'] === 'textarea' ? '12' : '6' }}">
                            <label class="form-label" for="setting-{{ $key }}">{{ $field['label'] }}</label>
                            @if ($field['type'] === 'textarea')
                                <textarea id="setting-{{ $key }}" name="{{ $key }}" class="form-control" rows="3">{{ old($key, $settings[$key] ?? '') }}</textarea>
                            @else
                                <input id="setting-{{ $key }}" type="{{ $field['type'] }}" name="{{ $key }}" class="form-control" value="{{ old($key, $settings[$key] ?? '') }}">
                            @endif
                            @if (! empty($field['hint']))
                                <div class="form-text">{{ $field['hint'] }}</div>
                            @endif
                            @error($key)
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </x-layout.modern-card>
        @endforeach

        <button class="btn btn-primary">Simpan Pengaturan</button>
    </form>
</x-layouts.app>
