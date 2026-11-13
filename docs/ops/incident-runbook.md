# Runbook Insiden

## Tingkat Severity

| Level | Definisi | Respons |
|---|---|---|
| SEV-1 | Situs down, checkout tidak bisa, data bocor, overselling masif | Semua tangan, update tiap 15 menit |
| SEV-2 | Pembayaran/webhook terganggu, queue backlog > 15 menit, fitur utama rusak | On-call + tech lead, update tiap 30 menit |
| SEV-3 | Fitur non-kritis terganggu, performa menurun | On-call, update harian |
| SEV-4 | Kosmetik/pertanyaan | Tiket biasa |

## Kontak

| Peran | Nama | Kontak |
|---|---|---|
| Incident commander | | |
| Backend on-call | | |
| Ops/infra | | |
| Payment gateway | | |
| Hosting/DB | | |
| Product Owner | | |

## 15 Menit Pertama

1. Tetapkan severity dan incident commander.
2. Buat channel insiden dan catat timeline.
3. Cek dashboard: uptime, 5xx, p95, queue, DB, webhook, disk.
4. Tentukan apakah perlu rollback atau perbaikan cepat.
5. Umumkan status awal ke stakeholder.
6. Jangan hapus log/bukti; amankan untuk investigasi.

## Playbook

### Situs down / 5xx tinggi

- Cek status hosting, load balancer, PHP-FPM, database, dan disk.
- Cek `storage/logs/laravel.log` dan error tracking.
- Jika deploy baru memicu, rollback.
- Jika DB penuh/lock, hentikan job non-kritis dan pulihkan koneksi.
- Verifikasi homepage, `/events`, checkout, dan `/up`.

### Database down / lambat

- Cek koneksi, CPU, IOPS, slow query, dan lock.
- Hentikan job berat sementara.
- Jika perlu, failover ke replica/restore backup.
- Setelah pulih, jalankan `php artisan migrate --force` hanya jika perlu dan `optimize:clear`.

### Queue backlog

- Cek `jobs`, `failed_jobs`, dan worker supervisor.
- Tambah worker sementara.
- Cek job yang menggantung (email/PDF/gateway).
- Jika backlog > 15 menit, umumkan keterlambatan email tiket.
- Setelah normal, kurangi worker dan cek `failed_jobs`.

### Webhook pembayaran gagal

- Cek `webhook_events` status `failed`/`invalid_signature`.
- Pastikan server key dan URL webhook benar.
- Cek rate limit dan IP gateway.
- Jalankan rekonsiliasi manual dari admin atau `ReconcilePayments`.
- Jika signature invalid massal, curigai kredensial/serangan; rotasi key.

### Overselling / stok tidak konsisten

- Hentikan penjualan tiket terdampak (nonaktifkan jenis tiket).
- Bandingkan `sold_count` dengan order `pending`/`paid`.
- Jangan menaikkan kuota diam-diam; hubungi pembeli terdampak.
- Refund atau sediakan slot pengganti sesuai kebijakan.
- Audit log dan postmortem wajib.

### Kebocoran data / security incident

- Isolasi akses, rotasi kredensial, dan `APP_KEY` bila perlu.
- Simpan bukti log; jangan hapus.
- Identifikasi data terdampak dan kewajiban notifikasi.
- Libatkan legal/DPO dan Product Owner.
- Patch, uji, lalu deploy dengan review keamanan.

## Rollback

1. Umumkan maintenance.
2. Aktifkan `php artisan down --retry=60`.
3. Hentikan worker.
4. Kembalikan artifact rilis sebelumnya.
5. Restore DB hanya jika migrasi/data tidak kompatibel.
6. `php artisan optimize:clear` dan build cache.
7. Nyalakan worker, cek webhook dan queue.
8. Verifikasi alur kritis.
9. `php artisan up` dan umumkan pemulihan.

## Template Komunikasi

```
[SEV-X] [LinyTix] [Waktu]
Status: Investigating / Identified / Monitoring / Resolved
Dampak: ...
Tindakan: ...
ETA: ...
Update berikutnya: ...
```

## Postmortem

- Timeline kejadian dan deteksi.
- Akar masalah dan faktor pendukung.
- Dampak ke pengguna, order, dan finansial.
- Tindakan perbaikan dengan PIC dan tenggat.
- Update runbook/monitoring agar tidak terulang.
