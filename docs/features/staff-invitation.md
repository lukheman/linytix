# Undangan Staff Event

Fitur ini memungkinkan organizer dan admin menambahkan staff event lewat email,
tanpa mengharuskan staff mendaftar terlebih dahulu.

## Perilaku

| Kondisi email | Tindakan sistem |
|---|---|
| Sudah punya akun aktif | Langsung ditugaskan ke `event_staff`, role `staff` diberikan, lalu email pemberitahuan dikirim |
| Sudah punya akun nonaktif | Ditolak |
| Email pemilik organizer | Ditolak |
| Sudah ditugaskan dengan gate yang sama | Tidak ada perubahan dan tidak ada email ulang |
| Sudah ditugaskan dengan gate berbeda | Gate diperbarui dan email pemberitahuan dikirim |
| Belum terdaftar | Dibuat undangan berbatas waktu dan email undangan dikirim |
| Sudah punya undangan pending | Ditolak; gunakan tombol **Kirim Ulang** |

## Alur staff baru

1. Organizer membuka **Events → Kelola Staff**.
2. Mengisi email dan gate, lalu klik **Kirim Undangan**.
3. Staff menerima email berisi link undangan.
4. Link membuka halaman undangan:
   - email belum terdaftar → tombol **Buat akun & terima undangan**;
   - email sudah terdaftar → tombol **Masuk untuk menerima**.
5. Setelah akun dibuat, staff otomatis ditugaskan dan diarahkan ke `/scan`.

## Keamanan

- Token mentah hanya dikirim lewat email; database menyimpan SHA-256 (`token_hash`).
- Token berlaku 7 hari dan hanya dapat dipakai satu kali.
- Kirim ulang merotasi token sehingga link lama langsung tidak berlaku.
- Pembatalan mengisi `revoked_at` dan membuat token tidak berlaku.
- Penerimaan hanya boleh dilakukan oleh akun dengan email yang sama.
- Event berstatus `cancelled` tidak dapat diundang.
- Halaman publik dibatasi throttle; aksi terima juga dibatasi.

## Konfigurasi

```env
TICKETING_STAFF_INVITATION_TTL_DAYS=7
TICKETING_STAFF_INVITATION_RESEND_COOLDOWN=60
```

Email memakai konfigurasi `MAIL_*` yang sama dengan e-tiket. Pastikan queue worker
berjalan karena notifikasi dikirim melalui queue:

```bash
php artisan queue:work --tries=1
```

## Audit

| Action | Kapan |
|---|---|
| `event.staff.invited` | Undangan baru dibuat |
| `event.staff.invitation_resent` | Undangan dikirim ulang |
| `event.staff.invitation_revoked` | Undangan dibatalkan |
| `event.staff.invitation_accepted` | Undangan diterima |
| `event.staff.assigned` | Penugasan staff dibuat atau gate diperbarui |
| `event.staff.removed` | Penugasan staff dihapus |

## Batasan

- Satu email hanya boleh memiliki satu undangan pending per event.
- Undangan kedaluwarsa tidak dapat dikirim ulang; organizer perlu membuat undangan baru.
- Staff yang sudah menerima undangan tetapi kemudian dihapus dari event harus diundang ulang.
- Role `staff` tidak dicabut saat penugasan dihapus karena staff mungkin bertugas di event lain.

## Test

Cakupan otomasi ada di `tests/Feature/StaffInvitationTest.php`:

- undangan email baru dan email terdaftar;
- penolakan akun nonaktif, pemilik organizer, dan undangan ganda;
- rotasi token, cooldown kirim ulang, dan pembatalan;
- halaman undangan valid, kedaluwarsa, dan token tidak dikenal;
- penerimaan oleh email yang cocok/berbeda dan token sekali pakai;
- registrasi lewat undangan, email yang dipaksa sesuai undangan, dan akses `/scan`;
- undangan dari halaman admin serta daftar undangan pending.
