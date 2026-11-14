# E7-05 — Launch Checklist

Tanggal target: 2026-11-13
Status: **checklist siap; verifikasi eksternal (domain, DNS, gateway produksi, monitoring) menunggu ops**.

## Go/No-Go

| Area | Item | Bukti / perintah | Status | PIC |
|---|---|---|---|---|
| Aplikasi | `php artisan ticketing:launch-check --strict` lulus | Output command | [ ] | |
| Aplikasi | `php artisan test --compact` lulus | Lokal 2026-11-14: 281 passed, 1.756 assertion | [x] | |
| Aplikasi | `vendor/bin/pint --test` bersih | Lokal 2026-11-14: seluruh repo lulus | [x] | |
| Aplikasi | `php artisan optimize` berhasil (config/route/view cache) | Lokal 2026-11-14: config/events/routes/views cached | [x] | |
| Aplikasi | `php artisan storage:link` aktif | `public/storage` ada | [ ] | |
| Aplikasi | Scheduler dan queue worker aktif | `schedule:list`, supervisor status | [ ] | |
| Aplikasi | `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...` | `.env` produksi | [ ] | |
| Aplikasi | `SESSION_SECURE_COOKIE=true` | `.env` produksi | [ ] | |

> Verifikasi lokal 2026-11-14: test suite dan Pint lulus, `php artisan optimize` berhasil. `ticketing:launch-check` sengaja masih gagal di `.env` lokal (debug aktif, log level debug, cookie tidak secure, Xendit masih sandbox) dan `public/storage` belum dibuat. Bagian `MAIL_*` sudah lulus setelah SMTP dikonfigurasi. Item lain memerlukan environment produksi/staging.

## Domain, HTTPS, Redirect

| Item | Kriteria | Status |
|---|---|---|
| DNS A/AAAA | Mengarah ke load balancer/server produksi | [ ] |
| TLS | Sertifikat valid, auto-renew aktif | [ ] |
| HTTP → HTTPS | Redirect 301 | [ ] |
| www → apex (atau sebaliknya) | Redirect 301 konsisten | [ ] |
| Canonical | Halaman publik memakai domain kanonik | [ ] |
| HSTS | `Strict-Transport-Security` aktif setelah yakin HTTPS stabil | [ ] |
| Security headers | `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` | [ ] |

## Email Domain

| Item | Kriteria | Status |
|---|---|---|
| SPF | `v=spf1 include:<provider> ~all` | [ ] |
| DKIM | Selector aktif dan signature valid | [ ] |
| DMARC | `v=DMARC1; p=quarantine; rua=mailto:dmarc@domain` | [ ] |
| MAIL_FROM | Domain terverifikasi, bukan `example.com` | [ ] |
| Mailer test | `php artisan ticketing:mail-test <email>` berhasil, email masuk inbox | [ ] |
| Uji kirim | Mail-tester score ≥ 9/10, masuk inbox | [ ] |
| Email transaksional | Verifikasi, tiket, refund, pembatalan terkirim | [ ] |

## Payment Gateway

Pilih satu gateway aktif (`PAYMENT_GATEWAY=midtrans` atau `xendit`); `fake` hanya untuk lokal/CI.

| Item | Kriteria | Status |
|---|---|---|
| Mode produksi | `PAYMENT_GATEWAY` bukan `fake`, flag produksi gateway aktif `true` | [ ] |
| Kredensial | Secret/server key produksi tersimpan di secret manager | [ ] |
| Webhook URL | `https://domain/webhooks/payment/<gateway>` terdaftar di dashboard gateway | [ ] |
| Webhook refund | Xendit: `https://domain/webhooks/payment/xendit/refund` terdaftar | [ ] |
| Callback token | Xendit: `XENDIT_CALLBACK_TOKEN` terisi dan cocok dengan dashboard | [ ] |
| Signature | Transaksi uji produksi lolos verifikasi signature/token | [ ] |
| Nominal | Nominal webhook Payment Session sama dengan total order memakai `amount` | [ ] |
| Refund | Refund uji QRIS/e-wallet berhasil, kasus `PENDING` selesai via callback/rekonsiliasi, dan VA punya prosedur manual | [ ] |
| Rekonsiliasi | Job `ReconcilePayments` dan `ReconcileRefunds` berjalan | [ ] |
| Launch check | `php artisan ticketing:launch-check --strict` lulus | [ ] |

## Monitoring & Alert

| Item | Kriteria | Status |
|---|---|---|
| Uptime | Monitor homepage, `/events`, `/up` | [ ] |
| Error tracking | Sentry/Bugsnag menerima error staging & produksi | [ ] |
| Queue | Alert backlog > 5 menit dan `failed_jobs` > 0 | [ ] |
| Database | Alert koneksi, slow query, disk | [ ] |
| Pembayaran | Alert webhook gagal/refund gagal | [ ] |
| Trafik | Dashboard p95, 5xx, 429 | [ ] |
| Log | Retensi dan akses terbatas; tidak ada PII | [ ] |
| On-call | Jadwal dan kontak tersedia | [ ] |

## Legal, PSE, Support

| Item | Kriteria | Status |
|---|---|---|
| Privacy Policy | Halaman `/privacy-policy` final | [ ] |
| Terms | Halaman `/terms` final | [ ] |
| Refund Policy | Halaman `/refund-policy` final | [ ] |
| Tentang Kami | Halaman `/tentang-kami` final | [ ] |
| Kontak support | Email/WhatsApp aktif dan dipantau | [ ] |
| PSE Kominfo | Pendaftaran sistem elektronik selesai | [ ] |
| Data pribadi | Kebijakan retensi dan permintaan penghapusan | [ ] |
| Invoice/pajak | Format dan kewajiban pajak sesuai entitas | [ ] |

## Operasional

| Item | Kriteria | Status |
|---|---|---|
| Backup | Cron harian aktif dan terverifikasi | [ ] |
| Restore drill | Lulus < 4 jam | [ ] |
| Rollback | Rencana rollback diuji di staging | [ ] |
| Maintenance mode | `php artisan down/up` diuji | [ ] |
| Secret rotation | Prosedur rotasi `APP_KEY` dan gateway | [ ] |
| Runbook insiden | `docs/ops/incident-runbook.md` tersedia | [ ] |
| Kontak eskalasi | Developer, ops, gateway, hosting | [ ] |

## Rollback Plan

1. Tentukan pemicu rollback: error rate > 5%, checkout gagal > 2%, data korup, atau security incident.
2. Hentikan deploy baru dan umumkan status.
3. Kembalikan aplikasi ke rilis sebelumnya (symlink/artifact sebelumnya).
4. Jika migrasi tidak kompatibel, restore database dari backup pra-deploy.
5. `php artisan optimize:clear`, lalu build cache ulang.
6. Nyalakan worker, cek queue dan webhook.
7. Verifikasi login, daftar event, checkout sandbox, scan, refund.
8. Catat waktu dan penyebab; lanjutkan postmortem.

Rollback dinyatakan teruji jika langkah 1–7 pernah dilakukan di staging dengan database hasil restore dan aplikasi kembali sehat.

## Sign-off

| Peran | Nama | Keputusan | Tanggal |
|---|---|---|---|
| Product Owner | | Go / No-go | |
| Tech Lead | | Go / No-go | |
| Ops Lead | | Go / No-go | |
