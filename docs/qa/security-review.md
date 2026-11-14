# E7-02 — Security Review

Tanggal review: 2026-11-14

## Ruang Lingkup

- Otorisasi vertikal dan horizontal seluruh route sensitif (`/admin`, `/organizer`, `/akun`, `/scan`, checkout, webhook).
- CSRF, XSS tersimpan, SQL injection, mass assignment, upload berbahaya.
- Signature webhook, replay, dan rate limit.
- Signed URL tampering dan expiry.
- Secret/PII di log dan repository.

## Bukti Otomasi

| Area | Test |
|---|---|
| Otorisasi vertikal | `tests/Feature/SecurityReviewTest.php` — admin/organizer/customer/staff matrix |
| Otorisasi horizontal | `SecurityReviewTest` — event organizer lain, order/tiket customer lain, staff event lain |
| CSRF | `SecurityReviewTest` — middleware menolak POST tanpa token, hanya `webhooks/*` dikecualikan |
| XSS | `SecurityReviewTest` — title/description/JSON-LD |
| SQL injection | `SecurityReviewTest` — pencarian publik dan admin |
| Mass assignment | `SecurityReviewTest` — register, checkout, update user; `OrganizerRegistrationTest` — input `role`/`is_verified` diabaikan |
| Rate limit pengajuan organizer | `OrganizerRegistrationTest` — 5 pengajuan/menit per user, percobaan ke-6 ditolak 429 |
| Upload berbahaya | `SecurityReviewTest` — poster PHP dan avatar SVG |
| Webhook | `SecurityReviewTest` + `tests/Feature/PaymentWebhookTest.php` + `tests/Feature/XenditGatewayTest.php` — signature/token, replay, rate limit |
| Signed URL | `SecurityReviewTest` + `tests/Feature/CheckoutTest.php` — tamper/expiry |
| Secret/PII | `SecurityReviewTest` — `.env` dan `storage/logs` tidak tracked; review kode logging |

Jalankan:

```bash
php artisan test --compact tests/Feature/SecurityReviewTest.php tests/Feature/PaymentWebhookTest.php tests/Feature/CheckoutTest.php
```

Hasil 2026-11-14: `SecurityReviewTest` 13 test lulus; suite penuh 281 test / 1.756 assertion; `vendor/bin/pint --test` bersih untuk seluruh repo.

## Temuan & Perbaikan

| ID | Severity | Temuan | Status | Perbaikan |
|---|---|---|---|---|
| SEC-01 | High | `/admin/dashboard` dan `/admin/pengaturan` dapat diakses semua user terautentikasi; customer dapat melihat statistik/order terbaru dan mengubah konten situs | Fixed | Route diberi `role:admin`; customer diarahkan ke `/akun`; `canAccessUrl` menolak path `/admin` untuk non-admin |
| SEC-02 | High | JSON-LD memakai `json_encode` tanpa `JSON_HEX_TAG`, sehingga judul event `</script><script>...` dapat menjadi XSS | Fixed | Encoding JSON-LD memakai `JSON_HEX_TAG`, `JSON_HEX_AMP`, `JSON_HEX_APOS`, `JSON_HEX_QUOT` |
| SEC-03 | Medium | Endpoint webhook tidak memiliki rate limit | Fixed | Limiter `payment-webhook` (default 120/menit per IP+gateway), dapat diatur via env |
| SEC-04 | Medium | Halaman edit event organizer memakai policy `view`; event published dapat dibuka organizer lain | Fixed | `Organizer\EventController@edit` memakai policy `update` |
| SEC-05 | Low | `/admin/profile` dan `/admin/components` dapat diakses user non-admin | Accepted | Hanya berisi profil sendiri dan dokumentasi komponen; tidak ada data sensitif. Tetap dipantau. |
| SEC-06 | Low | Belum ada CSP/WAF dan allowlist IP webhook | Accepted | Signature webhook sudah wajib; CSP perlu penyesuaian inline script/CDN. Masuk backlog hardening pasca-launch. |

Tidak ada temuan critical/high yang terbuka.

## CSRF

- Middleware `ValidateCsrfToken` aktif pada grup `web`.
- Satu-satunya pengecualian: `webhooks/*` karena gateway tidak dapat mengirim token CSRF.
- Webhook tetap dilindungi signature/token gateway dan rate limit.

## Webhook

- Signature diverifikasi sebelum order diproses.
- Midtrans memakai signature HMAC; Xendit memakai token statis `x-callback-token` yang dibandingkan dengan `hash_equals` dan gagal tertutup bila token belum dikonfigurasi.
- Callback refund Xendit memakai endpoint terpisah dan memvalidasi token, mata uang, serta nominal refund.
- Payload invalid disimpan sebagai `webhook_events.signature_valid = false` dan ditolak 403.
- Replay dengan `external_id` sama tidak membuat tiket ganda.
- Rate limit default 120/menit per IP+gateway; dapat diubah lewat `TICKETING_RATE_LIMIT_PAYMENT_WEBHOOK`.

## Signed URL

- Halaman order guest, PDF, checkout, pembayaran, dan polling memakai temporary signed URL.
- Signature tamper/expired menghasilkan 403.
- Akses non-signed hanya untuk pemilik order atau admin.

## Secret & PII

- `.env`, `.env.production`, dan `storage/logs` tidak masuk repository.
- Log aplikasi tidak menulis email, telepon, nomor identitas, password, atau server key.
- Audit log memakai masking PII untuk nomor identitas dan telepon (`App\Support\Pii`).
- Kredensial gateway dibaca dari config/env, bukan hardcode.

## Residual Risk

- Belum ada CSP ketat karena halaman memakai inline script dan CDN.
- Belum ada WAF/rate limit tingkat edge.
- Rotasi kredensial gateway dan `APP_KEY` belum diotomasi; ikuti runbook insiden.
- Uji penetrasi eksternal independen disarankan sebelum kampanye besar.
