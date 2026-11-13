# E7-03 — Rencana UAT

Tanggal: 2026-11-13
Status: **skenario otomatis tersedia; UAT manual & sign-off Product Owner menunggu**.

Status eksekusi per 2026-11-13: **belum dijalankan manual**. Seluruh skenario otomatis lulus melalui `php artisan test --compact` (235 test); kolom Otomasi di bawah merujuk file test yang menjadi bukti.

## Peserta

| Peran | Nama | Tanggung jawab |
|---|---|---|
| Product Owner | | Menyetujui hasil UAT |
| QA | | Menjalankan skenario dan mencatat defect |
| Backend | | Memperbaiki defect |
| Ops | | Menyiapkan environment dan data uji |

## Environment

- Staging dengan konfigurasi mendekati produksi.
- Payment gateway sandbox (Midtrans) atau `fake` untuk skenario internal.
- Email memakai Mailpit/Mailtrap agar tautan dan e-tiket dapat diperiksa.
- Data uji: 1 event published, 1 event draft, 1 organizer terverifikasi, 1 staff per gate, 1 admin.

## Matriks Skenario

| ID | Skenario | Langkah utama | Hasil yang diharapkan | Otomasi |
|---|---|---|---|---|
| UAT-01 | Pembelian guest | Pilih tiket → isi data → buat order → bayar → webhook sukses | Order paid, e-tiket terbit, tautan guest dapat dibuka | `UatJourneyTest` — guest purchase |
| UAT-02 | Pembelian customer | Login → checkout → bayar → buka akun | Order dan tiket muncul di `/akun/pesanan` dan `/akun/tiket` | `UatJourneyTest` — customer account |
| UAT-03 | Pembayaran sukses | Webhook `success` dengan nominal sesuai | Order paid, tiket aktif, email terkirim sekali | `PaymentWebhookTest` |
| UAT-04 | Pembayaran gagal | Webhook `failed` sebelum paid | Order tetap pending/expired, tidak ada tiket | `PaymentWebhookTest` (failure setelah paid); failure sebelum paid belum diuji |
| UAT-05 | Pembayaran expired | Order melewati `expires_at`, jalankan `ExpireOrders` | Order expired, stok kembali sekali, email expired | `PaymentWebhookTest` |
| UAT-06 | Late payment | Webhook sukses setelah order expired | Jika stok ada: order paid + tiket; jika tidak: refund otomatis | `PaymentWebhookTest` |
| UAT-07 | Refund penuh | Admin ajukan refund pada order paid | Refund sukses, tiket refunded, kuota tidak kembali | `RefundTest` |
| UAT-08 | Pembatalan event | Admin batalkan event published | Event cancelled, semua order paid direfund, organizer diberi email | `AdminEventModerationTest` / `RefundTest` |
| UAT-09 | Organizer create → tayang | Buat event → unggah poster → buat tiket → submit → admin approve | Event published dan tampil publik | `UatJourneyTest` — organizer publish |
| UAT-10 | Scan valid | Staff scan kode tiket aktif | `success`, tiket `used`, check-in tercatat | `ScanTest` |
| UAT-11 | Scan duplikat | Scan kode yang sama dua kali | `duplicate`, menampilkan waktu/gate scan pertama | `ScanTest` |
| UAT-12 | Scan invalid | Kode tidak ada / event salah / order belum paid | `invalid` dengan alasan yang benar, percobaan diaudit | `ScanTest` |
| UAT-13 | Moderasi event | Admin approve/reject event pending | Status berubah, alasan reject tersimpan, organizer diberi email | `AdminEventModerationTest` |
| UAT-14 | Master data | Admin CRUD kategori/venue/FAQ/banner | Perubahan tersimpan, cache publik invalid, data nonaktif tersembunyi | `AdminMasterDataTest` |
| UAT-15 | Refund admin | Admin refund dari daftar/detail order | Refund idempoten, retry refund gagal berfungsi | `AdminOrderMonitoringTest` / `RefundTest` |
| UAT-16 | Monitoring order | Admin filter status/event/tanggal/cari | Hasil sesuai filter, PII termasking | `AdminOrderMonitoringTest` |

## Perintah Regresi Otomatis

```bash
php artisan test --compact
```

## Defect Log

| ID | Skenario | Deskripsi | Severity | Status | PIC |
|---|---|---|---|---|---|
| | | | | | |

## Sign-off

| Peran | Nama | Keputusan | Tanggal | Catatan |
|---|---|---|---|---|
| Product Owner | | Setuju / Tidak setuju | | |
| QA Lead | | Lulus / Lulus dengan catatan | | |
| Tech Lead | | Lulus / Lulus dengan catatan | | |

UAT dinyatakan selesai jika seluruh skenario P0 lulus, tidak ada defect critical/high terbuka, dan Product Owner menandatangani tabel di atas.
