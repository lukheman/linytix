# E7-04 — Backup & Restore Drill

## Tujuan

| Parameter | Nilai |
|---|---|
| RPO | 24 jam (backup harian) |
| RTO | 4 jam |
| Retensi minimum | 7 harian, 4 mingguan, 3 bulanan |
| Lokasi | Backup lokal + object storage offsite |

## Artefak

| File | Fungsi |
|---|---|
| `scripts/backup-database.sh` | Backup MySQL/MariaDB atau SQLite, kompresi, checksum, retensi |
| `scripts/restore-drill.sh` | Restore ke database uji, verifikasi tabel inti, ukur durasi vs RTO |

## Backup Harian

Contoh cron di server aplikasi:

```cron
0 2 * * * cd /var/www/linytix && BACKUP_DIR=/var/backups/linytix RETENTION_DAYS=7 ./scripts/backup-database.sh >> storage/logs/backup.log 2>&1
```

Lalu sinkronkan ke object storage (contoh):

```bash
rclone copy /var/backups/linytix remote:linytix-backup --include "linytix-*"
```

Backup berisi data pribadi; enkripsi sebelum keluar server, misalnya:

```bash
age -r "$BACKUP_PUBLIC_KEY" -o "$FILE.age" "$FILE"
```

Simpan kunci privat di secret manager, bukan di server aplikasi.

## Verifikasi Harian

1. Cek file baru ada dan ukurannya wajar.
2. Verifikasi checksum: `sha256sum -c linytix-....sha256`.
3. Verifikasi gzip: `gzip -t linytix-....sql.gz`.
4. Cek log backup tidak mengandung error.
5. Kirim alert jika backup tidak muncul dalam 26 jam.

## Restore Drill

Jalankan minimal mingguan ke database uji:

```bash
./scripts/restore-drill.sh /var/backups/linytix/linytix-20261113T020000Z.sql.gz
```

Script akan:

1. Membuat database `ticketing_restore_drill_<epoch>`.
2. Restore dump.
3. Menghitung `users`, `orders`, `tickets`.
4. Mengukur durasi dan membandingkan dengan RTO 4 jam.
5. Menghapus database drill (kecuali `KEEP_DRILL_DB=1`).

Contoh hasil smoke test lokal 2026-11-13:

```
users=5 orders=0 tickets=0
Durasi: 1 detik (RTO 14400 detik)
PASS: restore berada dalam RTO dan data inti terbaca.
```

Catatan: hasil lokal hanya membuktikan script berjalan; drill produksi wajib memakai backup produksi terbaru dan dicatat tanggal, ukuran, serta durasinya.

## Runbook Restore Produksi

1. Umumkan maintenance dan aktifkan `php artisan down --retry=60`.
2. Hentikan worker: `php artisan queue:restart` dan hentikan supervisor worker.
3. Ambil backup darurat kondisi saat ini (walau rusak) untuk forensik.
4. Pulihkan database:
   - MySQL: buat database baru, restore dump, arahkan `DB_DATABASE` ke database baru.
   - SQLite: hentikan aplikasi, ganti file database dengan hasil restore.
5. Jalankan `php artisan migrate --force` hanya jika versi skema backup lebih lama.
6. Bersihkan cache: `php artisan optimize:clear`.
7. Build ulang cache: `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
8. Jalankan `php artisan ticketing:launch-check --strict`.
9. Nyalakan worker dan scheduler.
10. Verifikasi: login admin, buka daftar event, cek order terakhir, cek tiket, kirim email uji.
11. Matikan maintenance: `php artisan up`.
12. Catat waktu mulai/selesai dan bandingkan dengan RTO.

## Uji Rollback Backup

- Restore ke database uji.
- Jalankan aplikasi staging dengan database hasil restore.
- Uji login, checkout sandbox, scan tiket, dan refund.
- Jika gagal, jangan lanjut ke produksi; perbaiki backup/restore lebih dulu.

## Checklist Drill

- [ ] Backup harian terbaru tersedia dan checksum valid.
- [ ] Restore ke database uji berhasil.
- [ ] Tabel inti (`users`, `events`, `orders`, `tickets`, `payments`) terbaca.
- [ ] Durasi restore < 4 jam.
- [ ] Aplikasi staging dapat membaca data hasil restore.
- [ ] Database drill dibersihkan.
- [ ] Hasil dicatat di tabel berikut.

| Tanggal | Backup | Ukuran | Durasi restore | users/orders/tickets | Hasil | PIC |
|---|---|---|---|---|---|---|
| 2026-11-13 | `linytix-20261113T030529Z.sql.gz` (lokal) | dump MySQL terkompresi | 1 detik | 5/0/0 | PASS (RTO 14.400 detik) | Lokal |

> Drill lokal memverifikasi backup, checksum, restore, dan keterbacaan tabel inti. Item "aplikasi staging membaca data hasil restore" dan drill dari backup produksi belum dilakukan.
