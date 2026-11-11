@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'value' => null,
    'checked' => false,
    'disabled' => false,
    'error' => null,
])

@php
    $inputId = $id ?? ($name . '-' . $value) ?? 'radio-' . uniqid();
@endphp

<div class="form-check custom-radio">
    <input
        type="radio"
        id="{{ $inputId }}"
        name="{{ $name }}"
        value="{{ $value }}"
        {{ $checked ? 'checked' : '' }}
        {{ $disabled ? 'disabled' : '' }}
        {{ $attributes->merge(['class' => 'form-check-input' . ($error ? ' is-invalid' : '')]) }}
    >
    @if($label)
        <label for="{{ $inputId }}" class="form-check-label" style="color: var(--text-primary);">
            {{ $label }}
        </label>
    @endif
    {{ $slot }}

    @if($error)
        <div class="invalid-feedback">{{ $error }}</div>
    @endif
</div>

<style>
    .custom-radio .form-check-input {
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid var(--border-color);
        background-color: var(--input-bg);
        cursor: pointer;
        transition: all 0.2s;
    }

    .custom-radio .form-check-input:checked {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    .custom-radio .form-check-input:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
    }

    .custom-radio .form-check-input:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .custom-radio .form-check-label {
        cursor: pointer;
        margin-left: 0.5rem;
    }

    .custom-radio .form-check-input:disabled + .form-check-label {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>
