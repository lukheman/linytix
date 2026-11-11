@props([
    'icon' => 'fas fa-star',
    'title',
    'description' => null,
    'variant' => 'primary' // 'primary', 'secondary', 'success', 'warning', 'danger'
])
@php
    $colors = [
        'primary' => ['var(--bs-primary)', 'rgba(15, 118, 110, 0.1)'],
        'secondary' => ['var(--bs-secondary)', 'rgba(91, 98, 95, 0.1)'],
        'success' => ['var(--bs-success)', 'rgba(21, 128, 61, 0.1)'],
        'warning' => ['var(--bs-warning)', 'rgba(180, 83, 9, 0.1)'],
        'danger' => ['var(--bs-danger)', 'rgba(185, 28, 28, 0.1)'],
    ];
    $color = $colors[$variant][0] ?? $colors['primary'][0];
    $bgColor = $colors[$variant][1] ?? $colors['primary'][1];
@endphp

<div class="modern-card text-center">
<div class="stat-icon mx-auto" style="background: {{ $bgColor }}; color: {{ $color }};">
        <i class="{{ $icon }}"></i>
    </div>
    <h5 style="color: var(--text-primary);">{{ $title }}</h5>
    @if($description)
        <p class="text-muted mb-0" style="font-size: 0.875rem;">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>
