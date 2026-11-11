@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'checked' => false,
    'disabled' => false,
    'size' => 'md', // sm, md, lg
    'onLabel' => null,
    'offLabel' => null,
])

@php
    $inputId = $id ?? $name ?? 'toggle-' . uniqid();
    $sizes = [
        'sm' => ['width' => '36px', 'height' => '20px', 'circle' => '14px', 'translate' => '16px'],
        'md' => ['width' => '48px', 'height' => '26px', 'circle' => '20px', 'translate' => '22px'],
        'lg' => ['width' => '60px', 'height' => '32px', 'circle' => '26px', 'translate' => '28px'],
    ];
    $sizeConfig = $sizes[$size] ?? $sizes['md'];
@endphp

<div class="toggle-wrapper d-flex align-items-center gap-2">
    @if($offLabel)
        <span class="toggle-label-off" style="color: var(--text-secondary);">{{ $offLabel }}</span>
    @endif

    <label class="toggle-switch" style="width: {{ $sizeConfig['width'] }}; height: {{ $sizeConfig['height'] }};">
        <input
            type="checkbox"
            id="{{ $inputId }}"
            name="{{ $name }}"
            {{ $checked ? 'checked' : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $attributes }}
        >
        <span class="toggle-slider" style="--circle-size: {{ $sizeConfig['circle'] }}; --translate-x: {{ $sizeConfig['translate'] }};"></span>
    </label>

    @if($onLabel)
        <span class="toggle-label-on" style="color: var(--text-secondary);">{{ $onLabel }}</span>
    @elseif($label)
        <label for="{{ $inputId }}" class="toggle-label mb-0" style="color: var(--text-primary); cursor: pointer;">
            {{ $label }}
        </label>
    @endif
</div>

<style>
    .toggle-switch {
        position: relative;
        display: inline-block;
        flex-shrink: 0;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: var(--border-color);
        transition: all 0.3s ease;
        border-radius: 50px;
    }

    .toggle-slider::before {
        position: absolute;
        content: "";
        height: var(--circle-size);
        width: var(--circle-size);
        left: 3px;
        bottom: 50%;
        transform: translateY(50%);
        background-color: white;
        transition: all 0.3s ease;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .toggle-switch input:checked + .toggle-slider {
        background-color: var(--bs-primary);
    }

    .toggle-switch input:checked + .toggle-slider::before {
        transform: translateX(var(--translate-x)) translateY(50%);
    }

    .toggle-switch input:focus + .toggle-slider {
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
    }

    .toggle-switch input:disabled + .toggle-slider {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>
