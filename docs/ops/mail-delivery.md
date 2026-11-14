# Pengiriman Email Transaksional — LinyTix

Dokumen ini menjelaskan cara mengaktifkan pengiriman email nyata (e-tiket, verifikasi akun, refund, pembatalan) dan memverifikasinya.

## Ringkasan

| `MAIL_MAILER` | Perilaku |
|---|---|
| `log` | Email hanya ditulis ke `storage/logs/laravel.log`. **Tidak ada email yang terkirim.** |
| `array` | Email disimpan di memori proses. Dipakai test. Tidak terkirim. |
| `smtp` | Email dikirim ke server SMTP provider. Ini yang dipakai untuk produksi. |

`notification_logs.status = sent` hanya berarti proses pengiriman selesai tanpa exception. Status itu **bukan** bukti email masuk ke inbox penerima. Bukti sebenarnya adalah email yang diterima di inbox.

## Opsi A — Mailtrap Email Sending

Mailtrap Email **Testing** (sandbox) menangkap email dan tidak meneruskannya. Untuk pengiriman nyata gunakan produk **Email Sending**.

### 1. Verifikasi domain

1. Buka `https://mailtrap.io` → **Email Sending**.
2. Pilih **Sending Domains → Add Domain**.
3. Masukkan domain, misalnya `aplikasita.my.id`.
4. Tambahkan record DNS yang diberikan Mailtrap:
   - SPF
   - DKIM
   - DMARC
5. Tunggu sampai status domain **Verified**.

### 2. Ambil kredensial SMTP

Buka **Email Sending → SMTP/API → SMTP**, lalu buat API token.

```env
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=live.smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=api
MAIL_PASSWORD=API_TOKEN
MAIL_FROM_ADDRESS=no-reply@aplikasita.my.id
MAIL_FROM_NAME="LinyTix"
```

`MAIL_FROM_ADDRESS` wajib memakai domain yang sudah diverifikasi. Jangan memakai `example.com`.

## Opsi B — Gmail SMTP

Cocok untuk pengujian cepat, bukan untuk produksi bervolume besar.

1. Aktifkan verifikasi 2 langkah pada akun Google.
2. Buka **Google Account → Security → App Passwords**.
3. Buat App Password untuk aplikasi "Mail".
4. Gunakan 16 karakter App Password, bukan password akun.

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=akun@gmail.com
MAIL_PASSWORD=app-password-16-karakter
MAIL_FROM_ADDRESS=akun@gmail.com
MAIL_FROM_NAME="LinyTix"
```

Keterbatasan Gmail:

- Kuota harian terbatas.
- `MAIL_FROM_ADDRESS` sebaiknya sama dengan akun yang diautentikasi.
- Tidak cocok untuk email transaksional dalam jumlah besar.
- App Password bersifat rahasia. Rotasi bila pernah terekspos.

## Terapkan konfigurasi

Setelah `.env` diubah:

```bash
php artisan optimize:clear
```

Di produksi:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Verifikasi pengiriman

Kirim email uji:

```bash
php artisan ticketing:mail-test email-anda@gmail.com
```

Command akan menolak alamat tidak valid dan memberi peringatan bila `MAIL_MAILER` masih `log`/`array`.

Periksa:

1. Inbox penerima (termasuk folder Spam/Promotions).
2. `storage/logs/laravel.log` — tidak boleh bertambah baris `Subject: ...` baru.
3. `failed_jobs` kosong:

```bash
php artisan queue:failed
```

## Kirim ulang e-tiket

Lewat halaman admin order, atau manual:

```bash
php artisan tinker --execute="App\Jobs\SendTicketEmail::dispatchSync(App\Models\Order::where('order_number','ORD-...')->value('id'));"
```

`dispatchSync` mengirim langsung tanpa worker. Untuk produksi gunakan tombol admin agar tetap melalui queue.

## Worker dan scheduler

Email tiket dikirim melalui job. Pastikan worker berjalan:

```bash
php artisan queue:work --tries=3
```

Produksi memakai Supervisor/Horizon. Scheduler untuk expiry dan rekonsiliasi:

```cron
* * * * * cd /path/ke/project && php artisan schedule:run >> /dev/null 2>&1
```

Tanpa worker, `IssueTickets` dan `SendTicketEmail` akan tertahan di tabel `jobs`.

## Troubleshooting

| Gejala | Penyebab umum | Tindakan |
|---|---|---|
| `notification_logs` = `sent`, email tidak masuk | `MAIL_MAILER=log` | Ganti ke `smtp`, jalankan `optimize:clear` |
| `Connection could not be established` | Host/port/firewall salah | Cek `MAIL_HOST` dan `MAIL_PORT`; port 587/465 |
| `535 Authentication failed` | Password salah atau App Password belum dibuat | Buat ulang kredensial provider |
| Email masuk spam | SPF/DKIM/DMARC belum benar | Lengkapi DNS dan verifikasi domain |
| `jobs` menumpuk | Worker tidak berjalan | Jalankan `queue:work` via Supervisor |
| Email hanya masuk Mailtrap sandbox | Memakai host `sandbox.smtp.mailtrap.io` | Ganti ke `live.smtp.mailtrap.io` |

## Checklist produksi

- [ ] `MAIL_MAILER=smtp`
- [ ] `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD` terisi
- [ ] `MAIL_FROM_ADDRESS` memakai domain terverifikasi, bukan `example.com`
- [ ] SPF, DKIM, DMARC aktif
- [ ] `php artisan ticketing:mail-test <email>` berhasil
- [ ] Email e-tiket masuk inbox (bukan hanya spam)
- [ ] `queue:work` berjalan via Supervisor
- [ ] `schedule:run` berjalan via cron
- [ ] `php artisan ticketing:launch-check` lulus untuk bagian `MAIL_*`

Jangan menaruh kredensial atau token di dokumen ini.
