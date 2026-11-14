# Indeks Dokumen QA & Ops — LinyTix

Terakhir diperbarui: 2026-11-14

Halaman ini merangkum dokumen QA dan operasional untuk Epik 7 (QA & Launch). Gunakan tabel status untuk melihat apa yang sudah terbukti dan apa yang masih menunggu eksekusi eksternal.

## Dokumen

| Dokumen | Cakupan | Status | PIC |
|---|---|---|---|
| [`qa/load-test-plan.md`](qa/load-test-plan.md) | Skenario k6 (500 VU, 50 order/menit, 20 scan/detik), verifikasi stok, template hasil | Artefak siap; eksekusi staging belum | QA + Ops |
| [`qa/security-review.md`](qa/security-review.md) | Otorisasi, CSRF, XSS, SQLi, mass assignment, upload, webhook, signed URL, secret/PII | Review selesai; 4 temuan high/medium fixed, 2 low accepted | Backend |
| [`qa/uat-plan.md`](qa/uat-plan.md) | 16 skenario UAT, defect log, sign-off Product Owner | Skenario otomatis lulus; UAT manual & sign-off belum | QA + PO |
| [`ops/backup-restore.md`](ops/backup-restore.md) | Backup harian, restore drill, runbook restore produksi | Drill lokal lulus; cron & drill produksi belum | Ops |
| [`ops/mail-delivery.md`](ops/mail-delivery.md) | Konfigurasi SMTP (Mailtrap/Gmail), verifikasi kirim, kirim ulang e-tiket, troubleshooting | Konfigurasi tersedia; verifikasi inbox menunggu kredensial | Backend + Ops |
| [`ops/launch-checklist.md`](ops/launch-checklist.md) | Go/No-Go, domain/HTTPS, email domain, gateway, monitoring, legal, rollback | Sebagian item lokal terverifikasi; item eksternal belum | Ops + Tech Lead |
| [`ops/incident-runbook.md`](ops/incident-runbook.md) | Severity, kontak, playbook insiden, rollback, postmortem | Tersedia; kontak & jadwal on-call perlu diisi | Ops |

## Artefak pendukung

| Artefak | Fungsi |
|---|---|
| `tests/load/public-browse.js` | Ramp 500 VU ke halaman publik |
| `tests/load/checkout-rush.js` | 50 order/menit + verifikasi alur checkout |
| `tests/load/scan.js` | 20 scan/detik dengan login staff |
| `tests/load/verify-stock.sql` | Cek `sold_count <= quota` dan konsistensi order |
| `scripts/backup-database.sh` | Backup MySQL/MariaDB atau SQLite + checksum + retensi |
| `scripts/restore-drill.sh` | Restore ke database uji + ukur durasi terhadap RTO |
| `php artisan ticketing:launch-check` | Validasi konfigurasi produksi (`--strict` untuk warning sebagai gagal) |
| `php artisan ticketing:mail-test <email>` | Kirim email uji untuk memverifikasi konfigurasi SMTP |
| `tests/Feature/SecurityReviewTest.php` | Bukti otomasi security review |
| `tests/Feature/UatJourneyTest.php` | Bukti otomasi alur UAT utama |
| `tests/Feature/XenditGatewayTest.php` | Bukti integrasi Xendit: Payment Session, callback, Refund API v3, refund, rekonsiliasi |
| `tests/Feature/LaunchCheckCommandTest.php` | Bukti command launch-check |

## Status ringkas per 2026-11-14

| Area | Sudah terbukti lokal | Menunggu eksternal |
|---|---|---|
| Test suite | 298 test / 1.810 assertion lulus | — |
| Formatting | `vendor/bin/pint --test` bersih | Verifikasi ulang di CI/staging |
| Blade | `php artisan view:cache` berhasil | — |
| Security review | 13 test security lulus | Uji penetrasi eksternal (disarankan) |
| Backup/restore | Drill lokal 1 detik, tabel inti terbaca | Cron produksi + drill backup produksi |
| Load test | Script & threshold siap | Eksekusi staging + hasil aktual |
| UAT | Skenario otomatis lulus | UAT manual + sign-off PO |
| Launch | `launch-check` tersedia | Domain/DNS, SPF/DKIM/DMARC, gateway produksi, monitoring, legal/PSE, rollback drill |

## Aturan pemeliharaan

1. Jangan mencentang item yang belum dijalankan; isi tanggal, bukti (log/tautan), dan PIC.
2. Setiap temuan baru dicatat di tabel defect/temuan dokumen terkait.
3. Setelah eksekusi staging/produksi, perbarui `BACKLOG.md` dan `PRD.md` bila status berubah.
4. Jangan menaruh kredensial, token, atau PII di dokumen ini.
5. AC hanya dicentang bila perilaku terimplementasi dan ada bukti test/kode; gap cakupan test dicatat pada baris AC atau catatan story.
