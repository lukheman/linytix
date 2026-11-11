@props([
    'brandName' => 'LinyTix',
    'brandIcon' => 'fas fa-layer-group'
])

<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <i class="{{ $brandIcon }}"></i>
        <span>{{ $brandName }}</span>
    </div>
    <div class="sidebar-menu">
        {{ $slot }}
    </div>
</div>
