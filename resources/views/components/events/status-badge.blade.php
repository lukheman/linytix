@props(['status'])

@php
    $map = [
        'draft' => ['Draft', 'secondary'],
        'pending_review' => ['Menunggu Review', 'warning'],
        'rejected' => ['Ditolak', 'danger'],
        'published' => ['Published', 'success'],
        'sold_out' => ['Sold Out', 'info'],
        'ended' => ['Selesai', 'secondary'],
        'cancelled' => ['Dibatalkan', 'dark'],
    ];

    [$label, $variant] = $map[$status] ?? [\Illuminate\Support\Str::headline((string) $status), 'secondary'];
@endphp

<span {{ $attributes->merge(['class' => 'badge text-bg-'.$variant]) }}>{{ $label }}</span>
