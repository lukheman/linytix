@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'value' => '',
    'placeholder' => '',
    'rows' => 4,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'hint' => null,
    'maxlength' => null,
    'showCount' => false,
])

@php
    $inputId = $id ?? $name ?? 'textarea-' . uniqid();
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

    <textarea
        id="{{ $inputId }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $disabled ? 'disabled' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        {{ $required ? 'required' : '' }}
        {{ $maxlength ? "maxlength=$maxlength" : '' }}
        {{ $attributes->merge(['class' => 'form-control' . ($error ? ' is-invalid' : '')]) }}
        style="background: var(--input-bg); border-color: var(--border-color); color: var(--text-primary); resize: vertical;"
    >{{ $value }}{{ $slot }}</textarea>

    <div class="d-flex justify-content-between align-items-center mt-1">
        @if($error)
            <div class="invalid-feedback d-block">{{ $error }}</div>
        @elseif($hint)
            <div class="form-text" style="color: var(--text-muted);">{{ $hint }}</div>
        @else
            <div></div>
        @endif

        @if($showCount && $maxlength)
            <small class="text-muted">
                <span class="char-count">0</span>/{{ $maxlength }}
            </small>
        @endif
    </div>
</div>

<style>
    .form-control[rows] {
        min-height: 100px;
    }

    .form-control:focus {
        background: var(--input-bg);
        border-color: var(--bs-primary);
        color: var(--text-primary);
        box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.1);
    }

    .form-control::placeholder {
        color: var(--text-muted);
    }

    .form-control:disabled,
    .form-control[readonly] {
        background-color: var(--bg-tertiary);
        opacity: 0.7;
    }
</style>
