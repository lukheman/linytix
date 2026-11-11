@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => 'Pilih opsi...',
    'required' => false,
    'disabled' => false,
    'error' => null,
    'hint' => null,
])

@php
    $inputId = $id ?? $name ?? 'select-' . uniqid();
@endphp

<div class="form-group">
    @if($label)
        <label for="{{ $inputId }}" class="form-label">
            {{ $label }}
            @if($required)
                <span style="color: var(--bs-danger);">*</span>
            @endif
        </label>
    @endif

    <select
        id="{{ $inputId }}"
        name="{{ $name }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'form-select' . ($error ? ' is-invalid' : '')]) }}
        style="background: var(--input-bg); border-color: var(--border-color); color: var(--text-primary);"
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $value => $optionLabel)
            <option value="{{ $value }}" {{ $selected == $value ? 'selected' : '' }}>
                {{ $optionLabel }}
            </option>
        @endforeach
        {{ $slot }}
    </select>

    @if($error)
        <div class="invalid-feedback">{{ $error }}</div>
    @endif

    @if($hint && !$error)
        <div class="form-text" style="color: var(--text-muted);">{{ $hint }}</div>
    @endif
</div>

<style>
    .form-select {
        background-color: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-primary);
        padding: 0.75rem 2.5rem 0.75rem 1rem;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 16px 12px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-select:focus {
        background-color: var(--input-bg);
        border-color: var(--bs-primary);
        color: var(--text-primary);
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
    }

    .form-select:disabled {
        background-color: var(--bg-tertiary);
        opacity: 0.7;
        cursor: not-allowed;
    }

    .form-select option {
        background: var(--bg-secondary);
        color: var(--text-primary);
    }
</style>
