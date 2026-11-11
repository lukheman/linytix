@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'value' => null,
    'checked' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
])

@php
    $inputId = $id ?? $name ?? 'checkbox-' . uniqid();
@endphp

<div class="form-check custom-checkbox">
    <input
        type="checkbox"
        id="{{ $inputId }}"
        name="{{ $name }}"
        value="{{ $value ?? 1 }}"
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

    @if($hint && !$error)
        <div class="form-text" style="color: var(--text-muted); margin-left: 1.75rem;">{{ $hint }}</div>
    @endif
</div>

<style>
    .custom-checkbox .form-check-input {
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid var(--border-color);
        border-radius: 6px;
        background-color: var(--input-bg);
        cursor: pointer;
        transition: all 0.2s;
    }

    .custom-checkbox .form-check-input:checked {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
    }

    .custom-checkbox .form-check-input:focus {
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
    }

    .custom-checkbox .form-check-input:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .custom-checkbox .form-check-label {
        cursor: pointer;
        margin-left: 0.5rem;
    }

    .custom-checkbox .form-check-input:disabled + .form-check-label {
        opacity: 0.5;
        cursor: not-allowed;
    }
</style>
