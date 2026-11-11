<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28px; }
        body { font-family: DejaVu Sans, sans-serif; color: #141614; font-size: 12px; }
        .ticket { border: 1px solid #e0e1dd; border-radius: 0; padding: 22px; }
        .header { width: 100%; border-collapse: collapse; border-bottom: 1px solid #e0e1dd; margin-bottom: 18px; }
        .header td { vertical-align: top; padding-bottom: 14px; }
        .eyebrow { color: #0f766e; font-size: 10px; font-weight: bold; letter-spacing: 1.4px; text-transform: uppercase; }
        h1 { font-size: 22px; margin: 6px 0 8px; }
        .muted { color: #565b58; }
        .status { display: inline-block; padding: 4px 10px; border-radius: 0; background: #edefec; color: #0f766e; font-size: 10px; font-weight: bold; text-transform: uppercase; }
        .content { width: 100%; border-collapse: collapse; }
        .content td { vertical-align: middle; }
        .qr { width: 190px; text-align: center; }
        .qr img, .qr svg { width: 170px; height: 170px; }
        .details { width: 100%; border-collapse: collapse; }
        .details td { padding: 6px 0; border-bottom: 1px solid #ebebe7; vertical-align: top; }
        .details .label { width: 38%; color: #565b58; }
        .code { font-family: DejaVu Sans Mono, monospace; font-size: 10px; word-break: break-all; }
        .note { margin-top: 18px; padding-top: 12px; border-top: 1px solid #e0e1dd; color: #565b58; font-size: 9px; line-height: 1.5; }
        .footer { margin-top: 16px; text-align: center; color: #565b58; font-size: 9px; }
    </style>
</head>
<body>
    @forelse ($tickets as $ticket)
        <div class="ticket">
            <table class="header">
                <tr>
                    <td>
                        <div class="eyebrow">{{ $siteName }}</div>
                        <h1>{{ $order->event?->title ?? 'Event' }}</h1>
                        <div class="muted">
                            {{ $order->event?->startsAtLocal()?->translatedFormat('l, d F Y, H.i') }} WIB
                        </div>
                        <div class="muted">
                            {{ $order->event?->is_online ? 'Online' : ($order->event?->venue?->name ?? 'Lokasi menyusul') }}
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <span class="status">{{ ucfirst($ticket->status) }}</span>
                    </td>
                </tr>
            </table>

            <table class="content">
                <tr>
                    <td class="qr">
                        <img src="data:image/svg+xml;base64,{{ base64_encode(\App\Support\QrCode::svg($ticket->ticket_code, 180)) }}" alt="QR {{ $ticket->ticket_code }}">
                        <div class="muted" style="font-size: 9px; margin-top: 6px;">Tunjukkan QR di pintu masuk</div>
                    </td>
                    <td>
                        <table class="details">
                            <tr>
                                <td class="label">Kode tiket</td>
                                <td class="code">{{ $ticket->ticket_code }}</td>
                            </tr>
                            <tr>
                                <td class="label">Jenis tiket</td>
                                <td>{{ $ticket->ticketType?->name ?? 'Tiket' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Nama pemegang</td>
                                <td>{{ $ticket->holder_name }}</td>
                            </tr>
                            <tr>
                                <td class="label">Nomor order</td>
                                <td>{{ $order->order_number }}</td>
                            </tr>
                            <tr>
                                <td class="label">Pemesan</td>
                                <td>{{ $order->buyer_name }}</td>
                            </tr>
                            @if ($ticket->used_at)
                                <tr>
                                    <td class="label">Dipakai pada</td>
                                    <td>{{ $ticket->used_at->translatedFormat('d M Y, H.i') }}</td>
                                </tr>
                            @endif
                        </table>
                    </td>
                </tr>
            </table>

            <div class="note">
                Tiket bersifat pribadi dan hanya berlaku untuk event yang tertera. Pemalsuan atau penggunaan ganda dapat menyebabkan tiket tidak berlaku.
            </div>
        </div>

        @if (! $loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @empty
        <div class="ticket">
            <h1>E-tiket belum tersedia</h1>
            <p class="muted">Tiket akan diterbitkan setelah pembayaran dikonfirmasi.</p>
        </div>
    @endforelse

    <div class="footer">
        Diterbitkan oleh {{ $siteName }} · {{ $order->order_number }}
    </div>
</body>
</html>
