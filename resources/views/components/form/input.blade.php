@props([
    'type' => 'text',
    'name' => null,
    'id' => null,
    'label' => null,
    'placeholder' => null,
    'value' => null,
    'icon' => null,
    'iconPosition' => 'left', // 'left' or 'right'
    'error' => null,
    'hint' => null,
    'required' => false,
    'disabled' => false
])

@php
    $inputId = $id ?? $name ?? Str::random(8);
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'mb-3']) }}>
    @if($label)
        <label for="{{ $inputId }}" class="form-label" style="color: var(--text-primary); font-weight: 500;">
            {{ $label }}
            @if($required)
                <span style="color: var(--bs-danger);">*</span>
            @endif
        </label>
    @endif

    @if($icon)
        <div class="input-group">
            @if($iconPosition === 'left')
                <span class="input-group-text bg-white border-end-0">
                    <i class="{{ $icon }} text-muted"></i>
                </span>
            @endif
            <input
                type="{{ $type }}"
                @if($name) name="{{ $name }}" @endif
                id="{{ $inputId }}"
                class="form-control {{ $iconPosition === 'left' ? 'border-start-0' : 'border-end-0' }} {{ $error ? 'is-invalid' : '' }}"
                @if($placeholder) placeholder="{{ $placeholder }}" @endif
                @if($value) value="{{ $value }}" @endif
                @if($required) required @endif
                @if($disabled) disabled @endif
                {{ $attributes->except('class') }}
            >
            @if($iconPosition === 'right')
                <span class="input-group-text bg-white border-start-0">
                    <i class="{{ $icon }} text-muted"></i>
                </span>
            @endif
        </div>
    @else
        <input
            type="{{ $type }}"
            @if($name) name="{{ $name }}" @endif
            id="{{ $inputId }}"
            class="form-control {{ $error ? 'is-invalid' : '' }}"
            @if($placeholder) placeholder="{{ $placeholder }}" @endif
            @if($value) value="{{ $value }}" @endif
            @if($required) required @endif
            @if($disabled) disabled @endif
            {{ $attributes->except('class') }}
        >
    @endif

    @if($error)
        <div class="invalid-feedback d-block">{{ $error }}</div>
    @endif

    @if($hint && !$error)
        <small class="text-muted">{{ $hint }}</small>
    @endif
</div>
