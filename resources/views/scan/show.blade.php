<x-layouts.app title="Scan {{ $event->title }} - Nusa Event" brand-name="Nusa Event" brand-icon="fas fa-qrcode">
    <x-layout.page-header title="{{ $event->title }}" subtitle="Halaman scan tiket event.">
        <x-slot:actions>
            <a href="{{ route('scan.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Daftar Event
            </a>
        </x-slot:actions>
    </x-layout.page-header>

    @if (session('scan_result'))
        <div class="mb-4">
            @include('scan.partials.result', ['result' => session('scan_result')])
        </div>
    @endif

    <div class="row g-4">
        <div class="col-xl-7">
            <x-layout.modern-card title="Scanner Kamera">
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" id="camera-start" class="btn btn-primary">
                        <i class="fas fa-camera me-1"></i>Mulai Kamera
                    </button>
                    <button type="button" id="camera-stop" class="btn btn-outline-secondary d-none">
                        <i class="fas fa-stop me-1"></i>Hentikan Kamera
                    </button>
                </div>

                <div id="reader" class="border rounded bg-light overflow-hidden mb-3" style="min-height: 260px;"></div>

                <div id="camera-status" class="alert alert-info small mb-0">
                    Kamera akan diminta saat halaman dimuat. Jika izin ditolak, gunakan input manual di bawah.
                </div>
            </x-layout.modern-card>

            <x-layout.modern-card title="Input Manual" class="mt-4">
                <form id="manual-scan-form" method="POST" action="{{ route('scan.store', $event) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-sm-8">
                        <label for="ticket_code" class="form-label small fw-semibold">Kode tiket</label>
                        <input type="text" id="ticket_code" name="ticket_code" value="{{ old('ticket_code') }}" class="form-control" placeholder="Tempel atau ketik kode tiket" autocomplete="off" required>
                    </div>
                    <div class="col-sm-4">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check me-1"></i>Validasi
                        </button>
                    </div>
                </form>

                <p class="small text-muted mb-0 mt-2">
                    Input manual berguna jika kamera tidak tersedia atau QR rusak.
                </p>
            </x-layout.modern-card>
        </div>

        <div class="col-xl-5">
            <x-layout.modern-card title="Hasil Scan">
                <div id="scan-result">
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-qrcode fa-2x mb-2"></i>
                        <p class="mb-0 small">Hasil validasi akan muncul di sini.</p>
                    </div>
                </div>
            </x-layout.modern-card>

            <x-layout.modern-card title="Riwayat Sesi" class="mt-4">
                <div id="scan-history">
                    <p class="small text-muted mb-0">Belum ada scan pada sesi ini.</p>
                </div>
            </x-layout.modern-card>

            <x-layout.modern-card title="Penugasan Anda" class="mt-4">
                <dl class="row small mb-0">
                    <dt class="col-5 text-muted">Event</dt>
                    <dd class="col-7 fw-semibold">{{ $event->title }}</dd>

                    <dt class="col-5 text-muted">Gate</dt>
                    <dd class="col-7">
                        @if ($isAdmin)
                            <span class="badge text-bg-secondary">Akses admin</span>
                        @else
                            <span class="badge text-bg-info">{{ $gate ?: 'Belum ditentukan' }}</span>
                        @endif
                    </dd>

                    <dt class="col-5 text-muted">Jadwal</dt>
                    <dd class="col-7">{{ $event->startsAtLocal()?->translatedFormat('d M Y, H.i') ?? '—' }}</dd>

                    <dt class="col-5 text-muted">Lokasi</dt>
                    <dd class="col-7">{{ $event->is_online ? 'Online' : ($event->venue?->name ?? 'Venue menyusul') }}</dd>
                </dl>
            </x-layout.modern-card>
        </div>
    </div>

    @push('scripts')
        <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
        <script>
            (function () {
                const scanUrl = @json(route('scan.store', $event));
                const csrfToken = @json(csrf_token());
                const startButton = document.getElementById('camera-start');
                const stopButton = document.getElementById('camera-stop');
                const statusBox = document.getElementById('camera-status');
                const resultBox = document.getElementById('scan-result');
                const historyBox = document.getElementById('scan-history');
                const manualForm = document.getElementById('manual-scan-form');
                const manualInput = document.getElementById('ticket_code');

                let scanner = null;
                let scanning = false;
                let processing = false;
                let lastCode = '';
                let lastCodeAt = 0;
                const history = [];

                function setStatus(message, variant) {
                    statusBox.className = 'alert alert-' + (variant || 'info') + ' small mb-0';
                    statusBox.textContent = message;
                }

                function formatTime(value) {
                    if (!value) {
                        return '—';
                    }

                    try {
                        return new Date(value).toLocaleString('id-ID', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                        });
                    } catch (error) {
                        return value;
                    }
                }

                function resultVariant(result) {
                    if (result === 'success') {
                        return 'success';
                    }

                    if (result === 'duplicate') {
                        return 'warning';
                    }

                    return 'danger';
                }

                function detailRow(label, value) {
                    if (!value) {
                        return null;
                    }

                    const row = document.createElement('div');
                    row.className = 'col-sm-6';

                    const labelElement = document.createElement('span');
                    labelElement.className = 'text-muted d-block';
                    labelElement.textContent = label;

                    const valueElement = document.createElement('strong');
                    valueElement.textContent = value;

                    row.append(labelElement, valueElement);

                    return row;
                }

                function renderResult(data) {
                    resultBox.innerHTML = '';

                    const wrapper = document.createElement('div');
                    wrapper.className = 'alert alert-' + resultVariant(data.result) + ' mb-0';

                    const title = document.createElement('h5');
                    title.className = 'alert-heading fw-bold';
                    title.textContent = data.title;
                    wrapper.appendChild(title);

                    const message = document.createElement('p');
                    message.className = 'mb-2';
                    message.textContent = data.message;
                    wrapper.appendChild(message);

                    if (data.ticket) {
                        const details = document.createElement('div');
                        details.className = 'row g-2 small';

                        const rows = [
                            detailRow('Pemegang tiket', data.ticket.holder_name),
                            detailRow('Jenis tiket', data.ticket.ticket_type),
                            detailRow('Waktu scan', data.checked_in_at_label || formatTime(data.checked_in_at)),
                            detailRow('Gate', data.gate),
                            detailRow('Scan pertama', data.previous ? (data.previous.checked_in_at_label || formatTime(data.previous.checked_in_at)) : null),
                            detailRow('Gate pertama', data.previous ? data.previous.gate : null),
                        ];

                        rows.filter(Boolean).forEach(function (row) {
                            details.appendChild(row);
                        });

                        wrapper.appendChild(details);
                    }

                    resultBox.appendChild(wrapper);

                    if (navigator.vibrate) {
                        navigator.vibrate(data.result === 'success' ? 120 : [80, 60, 80]);
                    }
                }

                function renderHistory() {
                    historyBox.innerHTML = '';

                    if (history.length === 0) {
                        const empty = document.createElement('p');
                        empty.className = 'small text-muted mb-0';
                        empty.textContent = 'Belum ada scan pada sesi ini.';
                        historyBox.appendChild(empty);
                        return;
                    }

                    const list = document.createElement('div');
                    list.className = 'list-group list-group-flush';

                    history.slice(0, 10).forEach(function (entry) {
                        const item = document.createElement('div');
                        item.className = 'list-group-item px-0 py-2';

                        const header = document.createElement('div');
                        header.className = 'd-flex justify-content-between align-items-start gap-2';

                        const title = document.createElement('span');
                        title.className = 'small fw-semibold text-' + resultVariant(entry.result);
                        title.textContent = entry.title;

                        const time = document.createElement('span');
                        time.className = 'small text-muted text-nowrap';
                        time.textContent = entry.time;

                        header.append(title, time);

                        const code = document.createElement('div');
                        code.className = 'small text-muted font-monospace';
                        code.textContent = entry.code || '—';

                        item.append(header, code);
                        list.appendChild(item);
                    });

                    historyBox.appendChild(list);
                }

                function addHistory(data, code) {
                    history.unshift({
                        result: data.result,
                        title: data.title,
                        code: data.ticket ? data.ticket.ticket_code : code,
                        time: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }),
                    });

                    renderHistory();
                }

                async function submitCode(code) {
                    const value = String(code || '').trim();

                    if (!value || processing) {
                        return;
                    }

                    processing = true;
                    manualInput.value = value;

                    try {
                        const response = await fetch(scanUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                            },
                            body: JSON.stringify({ ticket_code: value }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            renderResult({
                                result: 'invalid',
                                title: 'Tiket Tidak Valid',
                                message: data.message || 'Kode tiket tidak dapat diproses.',
                                ticket: null,
                            });
                            return;
                        }

                        renderResult(data);
                        addHistory(data, value);
                    } catch (error) {
                        renderResult({
                            result: 'invalid',
                            title: 'Gagal Memvalidasi',
                            message: 'Koneksi bermasalah. Periksa jaringan lalu coba lagi.',
                            ticket: null,
                        });
                    } finally {
                        processing = false;
                        manualInput.value = '';
                        manualInput.focus();
                    }
                }

                function onScanSuccess(decodedText) {
                    const now = Date.now();

                    if (decodedText === lastCode && now - lastCodeAt < 2500) {
                        return;
                    }

                    lastCode = decodedText;
                    lastCodeAt = now;
                    submitCode(decodedText);
                }

                function showCameraError() {
                    setStatus('Izin kamera ditolak atau kamera tidak tersedia. Gunakan input manual di bawah untuk memvalidasi tiket.', 'warning');
                    startButton.classList.remove('d-none');
                    stopButton.classList.add('d-none');
                }

                async function startScanner() {
                    if (!window.Html5Qrcode) {
                        showCameraError();
                        return;
                    }

                    if (scanning) {
                        return;
                    }

                    try {
                        const cameras = await Html5Qrcode.getCameras();

                        if (!cameras || cameras.length === 0) {
                            showCameraError();
                            return;
                        }

                        const preferred = cameras.find(function (camera) {
                            return /back|rear|environment/i.test(camera.label || '');
                        }) || cameras[cameras.length - 1];

                        scanner = new Html5Qrcode('reader');

                        await scanner.start(
                            preferred.id,
                            { fps: 10, qrbox: { width: 240, height: 240 } },
                            onScanSuccess,
                            function () {},
                        );

                        scanning = true;
                        startButton.classList.add('d-none');
                        stopButton.classList.remove('d-none');
                        setStatus('Kamera aktif. Arahkan QR tiket ke dalam bingkai.', 'success');
                    } catch (error) {
                        showCameraError();
                    }
                }

                async function stopScanner() {
                    if (!scanner || !scanning) {
                        return;
                    }

                    try {
                        await scanner.stop();
                        await scanner.clear();
                    } catch (error) {
                        // Kamera mungkin sudah berhenti sendiri.
                    }

                    scanner = null;
                    scanning = false;
                    startButton.classList.remove('d-none');
                    stopButton.classList.add('d-none');
                    setStatus('Kamera dihentikan. Anda masih dapat memakai input manual.', 'secondary');
                }

                startButton.addEventListener('click', startScanner);
                stopButton.addEventListener('click', stopScanner);

                manualForm.addEventListener('submit', function (event) {
                    event.preventDefault();
                    submitCode(manualInput.value);
                });

                window.addEventListener('load', function () {
                    startScanner();
                });

                window.addEventListener('pagehide', function () {
                    if (scanner && scanning) {
                        scanner.stop().catch(function () {});
                    }
                });
            })();
        </script>
    @endpush
</x-layouts.app>
