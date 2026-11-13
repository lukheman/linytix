# E7-01 — Rencana & Artefak Load Test

Status: **artefak siap; eksekusi menunggu environment staging**. Hasil aktual diisi pada tabel di bawah setelah run.

Status eksekusi per 2026-11-13: **belum dijalankan** (0 dari 3 skenario). Tidak ada angka hasil yang boleh diisi sebelum run staging.

| Skenario | Perintah | Status | Bukti |
|---|---|---|---|
| Public browse 500 VU | `k6 run tests/load/public-browse.js` | Belum | — |
| Checkout rush 50/menit | `k6 run tests/load/checkout-rush.js` | Belum | — |
| Scan 20/detik | `k6 run tests/load/scan.js` | Belum | — |
| Verifikasi stok | `tests/load/verify-stock.sql` | Belum | — |

## Target

| Metrik | Target |
|---|---|
| Concurrent users halaman publik | 500 |
| Order masuk | 50 order/menit |
| Scan tiket | 20 scan/detik |
| p95 halaman publik | < 2 detik |
| p95 checkout | < 2 detik |
| Overselling | 0 |
| Queue backlog | < 5 menit |

## Artefak

| File | Fungsi |
|---|---|
| `tests/load/public-browse.js` | Ramp 500 VU ke beranda, daftar event, detail event |
| `tests/load/checkout-rush.js` | Constant arrival rate 50 order/menit + verifikasi stok |
| `tests/load/scan.js` | Constant arrival rate 20 scan/detik |
| `tests/load/verify-stock.sql` | Cek `sold_count <= quota` dan konsistensi order |
| `tests/load/lib/config.js` | Konfigurasi env dan threshold |

## Prasyarat Staging

1. Staging memakai stack yang sama dengan produksi: MySQL/PostgreSQL, Redis untuk cache/queue, worker aktif, dan `APP_ENV=staging`.
2. Event uji sudah tayang, memiliki satu jenis tiket dengan kuota besar (mis. 5.000) dan harga tetap.
3. Rate limit dinaikkan **hanya di staging** agar skenario 50 order/menit dan 20 scan/detik tidak tertahan throttle:

```dotenv
TICKETING_RATE_LIMIT_CHECKOUT_STORE=10000
TICKETING_RATE_LIMIT_CHECKOUT_PAY=10000
TICKETING_RATE_LIMIT_CHECKOUT_STATUS=100000
TICKETING_RATE_LIMIT_SCAN=100000
TICKETING_RATE_LIMIT_PAYMENT_WEBHOOK=10000
```

4. Setelah load test, kembalikan nilai produksi (default: checkout 10/menit, scan 60/menit, webhook 120/menit).
5. Siapkan minimal 2 worker queue dan pantau `failed_jobs`, `jobs`, serta waktu tunggu job tertua.

## Menjalankan

```bash
# 1. Halaman publik — 500 VU
BASE_URL=https://staging.example.test \
EVENT_SLUG=konser-load-test \
k6 run tests/load/public-browse.js

# 2. Rebutan tiket — 50 order/menit selama 10 menit
BASE_URL=https://staging.example.test \
EVENT_SLUG=konser-load-test \
TICKET_TYPE_ID=<uuid-ticket-type> \
EXPECTED_UNIT_PRICE=150000 \
ORDERS_PER_MINUTE=50 \
DURATION=10m \
k6 run tests/load/checkout-rush.js

# 3. Scan gate — 20 scan/detik selama 5 menit
BASE_URL=https://staging.example.test \
EVENT_ID=<uuid-event> \
STAFF_EMAIL=staff-load@example.test \
STAFF_PASSWORD='<password>' \
TICKET_CODES="$(paste -sd, ticket-codes.txt)" \
SCANS_PER_SECOND=20 \
DURATION=5m \
k6 run tests/load/scan.js
```

Contoh ekspor kode tiket untuk scan:

```sql
SELECT ticket_code
FROM tickets
WHERE event_id = '<uuid-event>' AND status = 'active'
LIMIT 20000;
```

## Verifikasi Tidak Ada Overselling

Setelah `checkout-rush.js`:

```bash
mysql -u "$DB_USERNAME" -p "$DB_DATABASE" < tests/load/verify-stock.sql
```

Ganti `:event_id` dengan UUID event. Syarat lulus:

- Semua baris berstatus `OK`.
- `sold_count` tidak pernah lebih besar dari `quota`.
- Jumlah tiket pada order `pending`/`paid` konsisten dengan `sold_count`.

## Queue Backlog

Selama checkout rush, jalankan berkala:

```sql
SELECT COUNT(*) AS jobs, MIN(available_at) AS oldest_available_at FROM jobs;
SELECT COUNT(*) AS failed_jobs FROM failed_jobs;
```

Backlog dianggap lulus jika job tertua tidak lebih dari 5 menit dan `failed_jobs` tidak bertambah. Jika memakai Redis queue, pantau `queue:monitor` atau metrik panjang list queue.

## Template Hasil

| Tanggal | Environment | Skenario | p95 | Error rate | 5xx | 429 | Overselling | Queue backlog | Catatan |
|---|---|---|---|---|---|---|---|---|---|
| | staging | public browse 500 VU | | | | | — | — | |
| | staging | checkout 50/menit | | | | | | | |
| | staging | scan 20/detik | | | | | | — | |

## Register Bottleneck (hipotesis awal)

| Area | Risiko | Mitigasi |
|---|---|---|
| `ticket_types.sold_count` | Row lock pada satu jenis tiket populer membuat update stok serial | Update atomik `quota - sold_count >= qty` menjamin tidak overselling; pantau lock wait. Jika perlu, pecah kuota per jenis tiket/gelombang. |
| Halaman daftar/detail event | N+1 query relasi organizer, venue, ticket types | Eager loading sudah dipakai; verifikasi dengan query log saat load test. |
| Checkout | Validasi + transaksi + insert order per request | Index pada `orders.event_id`, `orders.created_at`, `order_items.ticket_type_id`; pantau slow query. |
| Queue | Email tiket, PDF, notifikasi | Tambah worker; pastikan job idempoten; pantau backlog. |
| Session/cache | Session database/redis dan cache publik | Gunakan Redis; hindari cache stampede pada halaman publik. |
| Throttle | 429 pada load test terpusat | Naikkan hanya di staging; produksi memakai batas default. |
| Webhook | Lonjakan retry gateway | Rate limit 120/menit per IP+gateway; idempotensi lewat `webhook_events`. |

## Kriteria Lulus

- Semua threshold k6 lulus.
- Tidak ada overselling.
- Tidak ada 5xx di luar skenario negatif yang disengaja.
- Queue backlog < 5 menit.
- Bottleneck yang ditemukan memiliki tiket perbaikan dan diverifikasi ulang.
