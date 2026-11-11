@props([
    'icon' => 'fas fa-chart-bar',
    'label',
    'value',
    'trend' => null,
    'trendValue' => null,
    'trendDirection' => 'up', // 'up' or 'down'
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
    $trendColor = $trendDirection === 'up' ? 'var(--bs-success)' : 'var(--bs-danger)';
    $trendIcon = $trendDirection === 'up' ? 'fa-arrow-up' : 'fa-arrow-down';
@endphp

<div class="stat-card" style="--accent-color: {{ $color }};">
    <div class="stat-icon" style="background: {{ $bgColor }}; color: {{ $color }};">
        <i class="{{ $icon }}"></i>
    </div>
    <p class="text-muted mb-1" style="font-size: 0.875rem;">{{ $label }}</p>
    <h3 class="mb-2" style="color: var(--text-primary); font-weight: 700;">{{ $value }}</h3>
    @if($trendValue)
        <small style="color: {{ $trendColor }}; font-weight: 600;">
            <i class="fas {{ $trendIcon }}"></i> {{ $trendValue }}
        </small>
    @endif
</div>
