@php
    $variant = match ($result['result'] ?? null) {
        'success' => 'success',
        'duplicate' => 'warning',
        default => 'danger',
    };
@endphp

<div class="alert alert-{{ $variant }} mb-0" role="status">
    <h5 class="alert-heading fw-bold">{{ $result['title'] }}</h5>
    <p class="mb-2">{{ $result['message'] }}</p>

    @if (! empty($result['ticket']))
        <div class="row g-2 small">
            <div class="col-sm-6">
                <span class="text-muted d-block">Pemegang tiket</span>
                <strong>{{ $result['ticket']['holder_name'] }}</strong>
            </div>
            <div class="col-sm-6">
                <span class="text-muted d-block">Jenis tiket</span>
                <strong>{{ $result['ticket']['ticket_type'] }}</strong>
            </div>
            @if (! empty($result['checked_in_at_label']))
                <div class="col-sm-6">
                    <span class="text-muted d-block">Waktu scan</span>
                    <strong>{{ $result['checked_in_at_label'] }}</strong>
                </div>
            @endif
            @if (! empty($result['gate']))
                <div class="col-sm-6">
                    <span class="text-muted d-block">Gate</span>
                    <strong>{{ $result['gate'] }}</strong>
                </div>
            @endif
            @if (! empty($result['previous']['checked_in_at_label']))
                <div class="col-sm-6">
                    <span class="text-muted d-block">Scan pertama</span>
                    <strong>{{ $result['previous']['checked_in_at_label'] }}</strong>
                </div>
            @endif
            @if (! empty($result['previous']['gate']))
                <div class="col-sm-6">
                    <span class="text-muted d-block">Gate pertama</span>
                    <strong>{{ $result['previous']['gate'] }}</strong>
                </div>
            @endif
        </div>
    @endif
</div>
