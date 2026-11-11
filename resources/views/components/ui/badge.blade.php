@props([
    'variant' => 'primary', // 'primary', 'secondary', 'success', 'warning', 'danger', 'info'
    'icon' => null
])
@php
    $variants = [
        'primary' => ['rgba(15, 118, 110, 0.1)', 'var(--bs-primary)'],
        'secondary' => ['rgba(14, 165, 233, 0.1)', 'var(--bs-secondary)'],
        'success' => ['rgba(16, 185, 129, 0.1)', 'var(--bs-success)'],
        'warning' => ['rgba(245, 158, 11, 0.1)', 'var(--bs-warning)'],
        'danger' => ['rgba(239, 68, 68, 0.1)', 'var(--bs-danger)'],
        'info' => ['rgba(91, 98, 95, 0.1)', 'var(--bs-secondary)'],
    ];
    $bg = $variants[$variant][0] ?? $variants['primary'][0];
    $color = $variants[$variant][1] ?? $variants['primary'][1];

    // Default icons for variants
    $defaultIcons = [
        'primary' => 'fas fa-circle',
        'success' => 'fas fa-check-circle',
        'warning' => 'fas fa-exclamation-circle',
        'danger' => 'fas fa-times-circle',
        'info' => 'fas fa-info-circle',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'badge-modern']) }} style="background: {{ $bg }}; color: {{ $color }};">
    @if($icon)
        <i class="{{ $icon }}" @if($icon === 'fas fa-circle') style="font-size: 0.5rem;" @endif></i>
    @endif
    {{ $slot }}
</span>
