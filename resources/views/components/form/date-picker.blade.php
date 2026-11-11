@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'value' => null,
    'placeholder' => 'Pilih tanggal...',
    'required' => false,
    'disabled' => false,
    'min' => null,
    'max' => null,
    'error' => null,
    'hint' => null,
    'type' => 'date', // date, datetime-local, time, month, week
])

@php
    $inputId = $id ?? $name ?? 'datepicker-' . uniqid();
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

    <div class="date-picker-wrapper position-relative">
        <input
            type="{{ $type }}"
            id="{{ $inputId }}"
            name="{{ $name }}"
            value="{{ $value }}"
            placeholder="{{ $placeholder }}"
            {{ $min ? "min=$min" : '' }}
            {{ $max ? "max=$max" : '' }}
            {{ $disabled ? 'disabled' : '' }}
            {{ $required ? 'required' : '' }}
            {{ $attributes->merge(['class' => 'form-control date-picker-input' . ($error ? ' is-invalid' : '')]) }}
        >
        <i class="fas fa-calendar-alt date-picker-icon"></i>
    </div>

    @if($error)
        <div class="invalid-feedback d-block">{{ $error }}</div>
    @endif

    @if($hint && !$error)
        <div class="form-text" style="color: var(--text-muted);">{{ $hint }}</div>
    @endif
</div>

<style>
    .date-picker-wrapper {
        position: relative;
    }

    .date-picker-input {
        background: var(--input-bg);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        color: var(--text-primary);
        padding: 0.75rem 2.5rem 0.75rem 1rem;
    }

    .date-picker-input:focus {
        background: var(--input-bg);
        border-color: var(--bs-primary);
        color: var(--text-primary);
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
    }

    .date-picker-input:disabled {
        background-color: var(--bg-tertiary);
        opacity: 0.7;
        cursor: not-allowed;
    }

    .date-picker-icon {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-muted);
        pointer-events: none;
    }

    /* Custom date picker styling */
    .date-picker-input::-webkit-calendar-picker-indicator {
        opacity: 0;
        cursor: pointer;
        position: absolute;
        right: 0;
        top: 0;
        width: 100%;
        height: 100%;
    }

    /* Dark mode support for native date picker */
    @media (prefers-color-scheme: dark) {
        .date-picker-input::-webkit-calendar-picker-indicator {
            filter: invert(1);
        }
    }

    [data-theme="dark"] .date-picker-input::-webkit-calendar-picker-indicator {
        filter: invert(1);
    }
</style>
