@props(['status'])

@php
    $map = [
        'pending' => ['Menunggu pembayaran', 'warning'],
        'paid' => ['Lunas', 'success'],
        'expired' => ['Kedaluwarsa', 'secondary'],
        'cancelled' => ['Dibatalkan', 'secondary'],
        'refunded' => ['Direfund', 'info'],
        'active' => ['Aktif', 'success'],
        'used' => ['Sudah dipakai', 'secondary'],
    ];

    [$label, $variant] = $map[$status] ?? [\Illuminate\Support\Str::headline((string) $status), 'secondary'];
@endphp

<span {{ $attributes->merge(['class' => 'badge text-bg-'.$variant]) }}>{{ $label }}</span>
