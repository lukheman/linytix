# BACKLOG P0 — Platform Tiket Event

| | |
|---|---|
| **Versi** | 1.1 |
| **Tanggal** | 10 November 2026 |
| **Basis** | `PRD.md` v1.2 |
| **Status** | Tahap 0 dan 1 selesai; tahap berikutnya belum dimulai |
| **Dokumen terkait** | `PRD.md`, `schema.sql`, `erd.mmd` |

Dokumen ini menerjemahkan seluruh fitur **P0** menjadi epik, user story, acceptance criteria (AC), task teknis, dan urutan sprint. P1/P2 tidak masuk backlog ini kecuali disebut sebagai dependency.

---

## 1. Ringkasan Eksekusi

| Epik | Hasil | Estimasi | Dependency |
|---|---|---|---|
| **E0** | Fondasi: skema, role, payment abstraction, layout, observability, CI | L | — |
| **E1** | Halaman publik: beranda, daftar, filter, detail, informasi | M | E0 |
| **E2** | Auth, akun, tiket saya, riwayat, akses guest | M | E0, E1 |
| **E3** | Checkout, pembayaran, webhook, e-tiket, refund | XL | E0, E2 |
| **E4** | Dashboard organizer: profil, event, tiket, laporan | L | E0, E1 |
| **E5** | Scan QR staff | M | E0, E3 |
| **E6** | Admin: pengguna, moderasi, master data, transaksi | L | E0, E1, E3 |
| **E7** | Load test, security review, UAT, launch | M | E1–E6 |

Urutan yang disarankan: **E0 → E1 → E2 → E3 → E4 → E5 → E6 → E7**, dengan E4 dan E5 boleh berjalan paralel setelah E3 stabil.

### 1.1 Status implementasi

| Story | Status | Catatan |
|---|---|---|
| E0-01 Perluasan skema | Selesai | 5 migration baru; `schema.sql` 41 tabel; `erd.mmd` diperbarui |
| E0-02 Role & otorisasi | Sebagian | Role, middleware, policy, login aktif/nonaktif selesai. Route `/admin` lama masih memakai `auth` agar situs legacy tidak rusak; penerapan `role:admin` menunggu migrasi panel admin |
| E0-03 Payment abstraction | Selesai | `PaymentGateway`, `FakeGateway`, `MidtransGateway`, `config/payment.php` |
| E0-04 Layout & navigasi | Selesai | Layout publik diperluas untuk event dan SEO; layout organizer/staff penuh menyusul |
| E0-05 Observability & job | Sebagian | Job expiry, rekonsiliasi, tiket, email, refund dan scheduler ada; Sentry/alert belum dipasang |
| E0-06 Factory & seeder | Selesai | Factory ticketing terisi; `RoleSeeder`, `TicketingSeeder` |
| E0-07 CI | Sebagian | Workflow Flux dibersihkan; Pint + Pest berjalan. Staging/deploy belum ada |
| E0-08 Legal & konten | Sebagian | Halaman FAQ, tentang, privasi, S&K, refund tersedia; review legal belum |
| E1-01 Beranda event | Selesai | `/events` menampilkan banner, event unggulan, kategori |
| E1-02 Daftar event | Selesai | Hanya event published/sold_out dan akan datang; pagination 12 |
| E1-03 Filter & pencarian | Selesai | Kategori, kota, tanggal, harga, kata kunci; input tidak valid diabaikan |
| E1-04 Detail event | Selesai | Lineup, galeri, venue, S&K, tiket, status jual, JSON-LD |
| E1-05 Halaman informasi | Selesai | `/faq`, `/tentang-kami`, `/privacy-policy`, `/terms`, `/refund-policy` |

---

## 2. Definition of Ready & Done

### 2.1 Definition of Ready

- User story dan AC disetujui.
- Desain UI/wireframe tersedia untuk halaman yang berubah.
- Dampak skema jelas; migration dirancang bila perlu.
- Dependency eksternal (gateway sandbox, email provider) tersedia.
- Data uji/factory tersedia.
- Tidak ada pertanyaan bisnis yang memblokir implementasi.

### 2.2 Definition of Done

- Kode mengikuti konvensi repo: Form Request, Policy, Service/Action, Job, Pest.
- Minimal satu test happy path dan satu failure path per AC; race condition stok/check-in diuji.
- `vendor/bin/pint --dirty --format agent` bersih.
- `php artisan test --compact` untuk test terkait lulus.
- Migration + seeder berjalan dari database kosong; rollback berjalan.
- `schema.sql` dan `erd.mmd` diperbarui bila skema berubah.
- Tidak ada PII di log; error handling dan pesan pengguna jelas.
- Observability: log/error terkirim, metrik/alerts ditambahkan bila relevan.
- Code review selesai; tidak ada bug critical/high.
- Diverifikasi di staging.

---

## 3. Kebutuhan Lintas Epik

Berlaku untuk semua story:

- Semua route memakai middleware `auth`, role, dan Policy. Tidak ada pengecekan otorisasi hanya di view.
- Semua input divalidasi di Form Request; pesan error Bahasa Indonesia.
- Semua nominal uang `BIGINT UNSIGNED` Rupiah, tanpa float.
- Semua waktu disimpan UTC; tampilan memakai timezone event.
- Rate limit: login 5/menit, lookup order 10/menit, scan 60/menit, webhook 120/menit.
- Aksi sensitif menulis `audit_logs`: verifikasi organizer, moderasi event, refund, ubah harga/kuota, nonaktifkan akun, ubah status manual.
- Idempotency untuk order creation, webhook, ticket issuance, refund, dan email retry.
- Semua halaman publik punya meta title/description, canonical, dan OG image.
- Form dan halaman checkout lolos keyboard navigation dan kontras WCAG 2.1 AA.
- Tidak ada `env()` di luar config; tidak ada query mentah selain operasi atomik yang didokumentasikan.

---

## 4. Epik 0 — Fondasi & Skema (P0)

### E0-01 — Perluasan skema P0

**Estimasi:** M  
**Dependency:** —

**Task:**

- Migration `extend_users_and_roles`: `users.phone`, `google_id` (unique nullable), `is_active`, `last_login_at`; tabel `roles`, `role_user`.
- Migration `extend_organizers_and_venues`: logo, kontak, rekening, `verified_at`, `verified_by`, `verification_note`; venue `latitude`, `longitude`, `maps_url`.
- Migration `extend_events_and_ticket_types`: `short_desc`, `terms`, `poster_url`, `banner_url`, `doors_open_at`, `online_url`, `timezone`, `is_featured`, `require_identity`, `view_count`, `published_at`, `approved_by`, `approved_at`, `rejection_reason`; ticket type `description`, `min_per_order`, `max_per_order`, `sale_start_at`, `sale_end_at`.
- Migration `extend_vouchers_and_payments`: limit voucher (P1, boleh disiapkan), `payments.expires_at`.
- Migration `create_refunds_webhook_events_notification_logs`.
- Migration `add_ticketing_indexes`: index komposit sesuai PRD §10.3.
- Update model `$fillable`/`$casts` untuk kolom baru.
- Regenerate `schema.sql` dan `erd.mmd`.

**AC:**

- [ ] `php artisan migrate:fresh --seed` berhasil di MySQL/MariaDB.
- [ ] `php artisan migrate:rollback` berhasil tanpa error FK.
- [ ] Semua kolom baru nullable atau memiliki default sehingga data lama tidak rusak.
- [ ] `schema.sql` dapat diimpor ke database kosong tanpa error.
- [ ] `erd.mmd` memuat seluruh tabel/kolom baru dan tetap valid Mermaid.
- [ ] Test `SchemaTest` memastikan tabel/kolom/index kunci ada.

### E0-02 — Role & otorisasi

**Estimasi:** M  
**Dependency:** E0-01

**Task:**

- Seeder role: `customer`, `organizer`, `staff`, `admin`.
- Assign role saat registrasi (`customer`).
- Policy: `EventPolicy`, `OrderPolicy`, `TicketPolicy`, `RefundPolicy`, `PayoutPolicy` (P1).
- Middleware/guard untuk organizer, staff, admin.
- Admin 2FA (TOTP) untuk akun admin.

**AC:**

- [ ] Customer tidak bisa membuka `/organizer/*` atau `/admin/*` (403).
- [ ] Organizer hanya bisa mengubah event miliknya; event organizer lain menghasilkan 403.
- [ ] Staff hanya bisa scan event yang ada di `event_staff`.
- [ ] Admin bisa mengakses seluruh panel admin.
- [ ] User nonaktif (`is_active = false`) tidak bisa login dan session aktifnya dihentikan.
- [ ] Perubahan role dan status aktif tercatat di `audit_logs`.
- [ ] Feature test untuk setiap peran dan setiap route sensitif.

### E0-03 — Abstraksi payment gateway

**Estimasi:** M  
**Dependency:** E0-01

**Task:**

- Interface `PaymentGateway`: `createCharge(Order, method)`, `handleWebhook(Request)`, `refund(Refund)`, `getStatus(Payment)`.
- Implementasi `MidtransGateway` untuk sandbox.
- Implementasi `FakeGateway` untuk test (tanpa network).
- Config `config/payment.php`; kredensial dari env.
- Mapping status gateway → status internal.

**AC:**

- [ ] Test membuat charge memakai `FakeGateway` tanpa memanggil network.
- [ ] Signature webhook invalid ditolak dengan 401/403 dan tidak mengubah order.
- [ ] Webhook valid memanggil handler domain yang sama untuk semua gateway.
- [ ] Refund test memakai `FakeGateway` dan menghasilkan status yang benar.
- [ ] Tidak ada kredensial gateway di kode/log.

### E0-04 — Layout & navigasi

**Estimasi:** M  
**Dependency:** E0-01

**Task:**

- Layout publik, organizer, staff, admin yang responsif.
- Navigasi sesuai peran; tampilkan menu aktif.
- Komponen flash message/toast, empty state, pagination, modal konfirmasi.
- Halaman error 404, 419, 429, 500.

**AC:**

- [ ] Layout tampil benar di 360 px, 768 px, dan 1280 px.
- [ ] Menu yang tidak berhak tidak muncul; akses langsung tetap 403.
- [ ] Flash message bertahan setelah redirect dan hilang setelah dibaca.
- [ ] Halaman error menampilkan pesan Bahasa Indonesia dan tidak membocorkan stack trace.
- [ ] Tidak ada broken link di navigasi utama.

### E0-05 — Observability, queue, dan scheduler

**Estimasi:** M  
**Dependency:** E0-01

**Task:**

- Structured logging dengan request ID; redaksi PII.
- Sentry/error tracking.
- Job: `ExpireOrders`, `ReconcilePayments`, `SendOrderEmail`, `IssueTickets`, `ProcessRefund`.
- Scheduler untuk expiry dan rekonsiliasi.
- Health endpoint `/health`.
- Alert: webhook gagal, queue backlog, error rate, payment failure.

**AC:**

- [ ] Job gagal masuk `failed_jobs` dan bisa di-retry.
- [ ] Log tidak memuat email lengkap, nomor HP, nomor identitas, atau token.
- [ ] `/health` mengembalikan 200 hanya jika DB dan queue dapat diakses.
- [ ] Alert test terkirim ke channel yang ditentukan.
- [ ] `ExpireOrders` idempoten: dijalankan dua kali tidak menggandakan pengembalian stok.

### E0-06 — Seeder, factory, dan data uji

**Estimasi:** S  
**Dependency:** E0-01, E0-02

**Task:**

- Factory: User, Organizer, Venue, Category, Event, TicketType, Order, OrderItem, Payment, Ticket, Voucher.
- Seeder: admin, organizer terverifikasi, kategori, venue, 5 event demo (published/draft/sold_out), tiket.
- State factory: `published`, `paid`, `expired`, `used`.

**AC:**

- [ ] `migrate:fresh --seed` menghasilkan data yang bisa dipakai untuk semua halaman.
- [ ] Factory dapat membuat event dengan kuota dan tiket konsisten.
- [ ] Seeder tidak membuat data duplikat saat dijalankan ulang (idempoten atau truncate jelas).
- [ ] Tidak ada kredensial produksi di seeder.

### E0-07 — CI, staging, dan deployment

**Estimasi:** S  
**Dependency:** E0-01

**Task:**

- GitHub Actions: Pint, Pest, `npm run build`.
- Environment staging dengan database terpisah.
- Deploy script: migrate, cache config/route/view, restart queue.
- Backup otomatis harian.

**AC:**

- [ ] PR gagal jika Pint atau test gagal.
- [ ] Staging dapat di-deploy dari branch utama.
- [ ] Deploy tidak menghapus data; migration berjalan sebelum traffic dialihkan.
- [ ] Backup staging berhasil di-restore ke database uji.
- [ ] Rollback aplikasi terdokumentasi.

### E0-08 — Legal & konten wajib

**Estimasi:** S  
**Dependency:** —

**Task:**

- Draft S&K, Kebijakan Privasi, Kebijakan Refund.
- Halaman FAQ awal.
- Proses pendaftaran PSE.
- Kontak PIC data protection.

**AC:**

- [ ] Empat halaman legal dapat diakses dan memiliki versi/tanggal.
- [ ] Checkout menampilkan persetujuan S&K dan Kebijakan Privasi.
- [ ] Kebijakan Refund sesuai keputusan D7 di PRD.
- [ ] Tidak ada klaim hukum yang belum disetujui legal.

---

## 5. Epik 1 — Halaman Publik (P0)

### E1-01 — F-01 Halaman beranda

**Estimasi:** S  
**Dependency:** E0-04, E0-06

**AC:**

- [ ] Menampilkan banner aktif yang berada dalam rentang `start_at`–`end_at`, maksimal 5, urut `sort_order`.
- [ ] Menampilkan maksimal 8 event `published` yang `is_featured = true`, urut `start_at` terdekat.
- [ ] Menampilkan kategori aktif yang memiliki event published.
- [ ] Setiap kartu event menampilkan poster, judul, tanggal, kota, dan harga mulai.
- [ ] Jika tidak ada banner/event/kategori, tampil empty state, bukan error.
- [ ] Klik kartu membuka `/events/{slug}`.
- [ ] LCP < 2 detik p75 pada mobile 4G (diuji di staging).
- [ ] Meta title, description, dan OG image tersedia.
- [ ] Feature test: hanya konten aktif/published yang tampil; urutan benar.

### E1-02 — F-02 Daftar event

**Estimasi:** M  
**Dependency:** E1-01

**AC:**

- [ ] Hanya event `published`/`sold_out` dengan `start_at >= now()` yang tampil.
- [ ] Event `draft`, `pending_review`, `rejected`, `cancelled`, `ended` tidak tampil dan detailnya 404.
- [ ] Pagination 12 event per halaman; nomor halaman dan filter dipertahankan.
- [ ] Kartu menampilkan poster, judul, tanggal, kota/online, harga mulai, dan badge sold out.
- [ ] Sorting default `start_at` terdekat; opsi urutan harga/terbaru jika disepakati.
- [ ] Query p95 < 300 ms dengan 10.000 event (diuji dengan seeder/benchmark).
- [ ] Halaman dapat diakses tanpa login.
- [ ] Feature test untuk filter status, pagination, dan empty state.

### E1-03 — F-03 Filter & pencarian

**Estimasi:** M  
**Dependency:** E1-02

**AC:**

- [ ] Filter: kategori, kota, tanggal dari–sampai, harga min–max, kata kunci judul.
- [ ] Filter dapat dikombinasikan (AND); nilai tidak valid diabaikan tanpa error 500.
- [ ] Kata kunci minimal 2 karakter; pencarian case-insensitive.
- [ ] Filter tercermin di query string dan dapat dibagikan/di-bookmark.
- [ ] Tombol "Reset" menghapus semua filter.
- [ ] Hasil kosong menampilkan saran reset filter.
- [ ] Tidak ada N+1 query; gunakan eager loading.
- [ ] Feature test untuk setiap filter, kombinasi, dan input invalid.

### E1-04 — F-04 Detail event

**Estimasi:** L  
**Dependency:** E1-02

**AC:**

- [ ] Hanya event `published`/`sold_out` yang dapat diakses; lainnya 404.
- [ ] Menampilkan poster, judul, deskripsi, lineup, galeri, tanggal/waktu, timezone, venue + peta, S&K, dan daftar tiket.
- [ ] Untuk event online, tampilkan label online dan `online_url` hanya setelah pembayaran (jangan bocorkan di halaman publik).
- [ ] Setiap tiket menampilkan harga, sisa kuota (atau "Habis"), jadwal jual, dan status aktif.
- [ ] Tiket dengan jadwal jual belum mulai ditandai "Belum dijual"; yang lewat "Ditutup".
- [ ] Sisa kuota = `quota - sold_count`, tidak pernah negatif.
- [ ] Jika seluruh tiket habis, event berstatus `sold_out` dan tombol beli disabled.
- [ ] Structured data `Event` dan OG tags valid.
- [ ] Galeri memakai lazy loading; gambar punya alt text.
- [ ] Feature test: event draft 404; ketersediaan tiket benar; event online tidak membocorkan URL.

### E1-05 — F-13 Halaman informasi

**Estimasi:** S  
**Dependency:** E0-04

**AC:**

- [ ] `/faq` menampilkan FAQ aktif urut `sort_order`.
- [ ] `/tentang-kami`, `/privacy-policy`, `/terms`, `/refund-policy` dapat diakses dan mobile-friendly.
- [ ] FAQ memakai accordion yang dapat dioperasikan keyboard.
- [ ] Meta title/description unik per halaman.
- [ ] Tidak ada link mati ke halaman kontak/legal.

---

## 6. Epik 2 — Auth, Akun, dan Guest Access (P0)

### E2-01 — F-05 Registrasi, login, verifikasi email

**Estimasi:** M  
**Dependency:** E0-02

**AC:**

- [ ] Registrasi meminta nama, email, password, dan konfirmasi password.
- [ ] Email harus unik (case-insensitive) dan format valid.
- [ ] Password minimal 8 karakter; disimpan dengan Argon2id/bcrypt.
- [ ] Setelah registrasi, email verifikasi dikirim lewat queue.
- [ ] User belum verifikasi dapat login tetapi tidak dapat checkout sebagai customer sampai verifikasi (guest checkout tetap boleh).
- [ ] Login gagal menampilkan pesan generik, tidak membocorkan apakah email terdaftar.
- [ ] Rate limit login 5 percobaan/menit per email+IP.
- [ ] Logout menginvalidasi session dan regenerate CSRF token.
- [ ] Feature test: registrasi, email duplikat, password lemah, login benar/salah, verifikasi, logout.

### E2-02 — F-05 Login Google

**Estimasi:** M  
**Dependency:** E2-01

**AC:**

- [ ] Tombol "Login dengan Google" mengarah ke OAuth Google.
- [ ] User baru dari Google dibuat dengan `email_verified_at` terisi dan role `customer`.
- [ ] Jika email sudah terdaftar dan terverifikasi, akun dihubungkan; jika belum terverifikasi, minta verifikasi email terlebih dahulu.
- [ ] `google_id` unik; konflik akun ditangani tanpa menggandakan user.
- [ ] State OAuth divalidasi; callback invalid menghasilkan error yang aman.
- [ ] Feature test memakai mock Socialite untuk user baru, existing, dan state invalid.

### E2-03 — Lupa & reset password

**Estimasi:** S  
**Dependency:** E2-01

**AC:**

- [ ] `/forgot-password` mengirim link reset ke email terdaftar; respons selalu sama untuk mencegah enumerasi.
- [ ] Token reset berlaku 60 menit dan sekali pakai.
- [ ] Password baru minimal 8 karakter dan berbeda dari password lama.
- [ ] Setelah reset, semua session user lain diinvalidasi.
- [ ] Feature test: email terdaftar/tidak, token valid/expired/digunakan ulang.

### E2-04 — F-11 Riwayat pesanan (customer)

**Estimasi:** M  
**Dependency:** E2-01, E3-02

**AC:**

- [ ] `/akun/pesanan` menampilkan order milik user, terbaru dulu, pagination 10.
- [ ] Kolom: nomor order, event, tanggal, total, status, aksi detail.
- [ ] `/akun/pesanan/{order}` hanya bisa diakses pemilik; user lain 403.
- [ ] Detail menampilkan item, pembayaran, status tiket, dan tombol unduh tiket bila `paid`.
- [ ] Order `pending` menampilkan countdown dan tombol lanjut bayar.
- [ ] Order `expired`/`cancelled`/`refunded` menampilkan alasan/status dengan jelas.
- [ ] Feature test: isolasi akses antar user, semua status tampil benar.

### E2-05 — F-10 Tiket saya

**Estimasi:** M  
**Dependency:** E3-07

**AC:**

- [ ] `/akun/tiket` menampilkan tiket milik user, dikelompokkan per event.
- [ ] Tiket hanya tampil untuk order `paid`; tiket `cancelled`/`refunded` diberi label.
- [ ] Setiap tiket menampilkan QR, kode, nama pemegang, jenis tiket, dan status.
- [ ] Tombol unduh PDF menghasilkan file yang sama dengan yang dikirim via email.
- [ ] Tiket event yang sudah lewat tetap dapat dilihat sebagai arsip.
- [ ] Feature test: user tidak bisa melihat tiket user lain; tiket refunded tidak bisa diunduh sebagai tiket aktif.

### E2-06 — F-06/F-10/F-11 Akses guest

**Estimasi:** M  
**Dependency:** E3-03, E3-07

**AC:**

- [ ] Setelah checkout guest, email berisi signed URL ke detail pesanan dan tiket.
- [ ] Signed URL berlaku sampai 30 hari setelah event berakhir; URL kedaluwarsa menampilkan halaman instruksi.
- [ ] `/pesanan/cari` menerima nomor order + email; jika cocok, kirim link baru ke email (bukan menampilkan data langsung).
- [ ] Respons lookup selalu generik untuk mencegah enumerasi order.
- [ ] Guest dapat mengunduh PDF tiket dari signed URL.
- [ ] Jika guest mendaftar dengan email yang sama dan terverifikasi, order lama terhubung otomatis.
- [ ] Rate limit lookup 10/menit per IP.
- [ ] Feature test: signed URL valid/expired/tampered, lookup cocok/tidak, klaim order.

---

## 7. Epik 3 — Checkout, Pembayaran, dan Tiket (P0)

### E3-01 — F-07 Pilih tiket & validasi

**Estimasi:** M  
**Dependency:** E1-04

**AC:**

- [ ] Pembeli memilih satu jenis tiket dan jumlah; minimal 1, maksimal `max_per_order`.
- [ ] Validasi server: event published, tiket aktif, dalam jadwal jual, sisa kuota cukup, batas per identitas belum terlampaui.
- [ ] Jika event `require_identity`, form meminta nama pemegang untuk setiap tiket; jumlahnya harus sama dengan quantity.
- [ ] Harga yang ditampilkan berasal dari database, bukan input client.
- [ ] Perubahan harga setelah halaman dimuat tidak memengaruhi order; server memakai harga saat order dibuat dan menolak jika harga berubah (minta konfirmasi ulang).
- [ ] Error ditampilkan per field dan ringkasan di atas form.
- [ ] Feature test: kuota habis, jadwal belum mulai, jadwal lewat, max per order, identitas kurang, harga berubah.

### E3-02 — F-07 Pembuatan order & stok atomik

**Estimasi:** L  
**Dependency:** E3-01, E0-05

**AC:**

- [ ] Order dibuat dalam satu transaksi database bersama `order_items`.
- [ ] Stok dikurangi dengan query atomik; `sold_count` tidak pernah melebihi `quota`.
- [ ] Dua request bersamaan untuk tiket terakhir: satu sukses, satu gagal dengan pesan "Tiket sudah habis".
- [ ] `expires_at` = now + 15 menit (config).
- [ ] Order `pending` ganda dari identitas yang sama dibatasi (config, default 2).
- [ ] Total dihitung server-side: subtotal − diskon + admin_fee + payment_fee + tax; tidak negatif.
- [ ] Idempotency key mencegah double submit membuat dua order.
- [ ] Tidak ada tiket yang dibuat pada tahap order `pending`; tiket baru dibuat setelah webhook `paid`.
- [ ] Feature test: concurrency (dua proses), total, idempotency, batas pending.

### E3-03 — F-06 Guest checkout

**Estimasi:** S  
**Dependency:** E3-02

**AC:**

- [ ] Guest dapat checkout tanpa login dengan nama, email, phone.
- [ ] Email dan phone divalidasi format; email dinormalisasi lowercase.
- [ ] `orders.user_id` null untuk guest.
- [ ] Guest tidak dapat mengakses halaman akun; hanya signed URL.
- [ ] Guest menerima email order dan tiket.
- [ ] Jika email sudah terdaftar, tetap boleh guest checkout; order tidak otomatis terhubung sampai email terverifikasi.
- [ ] Feature test: guest checkout sukses, email invalid, akses akun ditolak.

### E3-04 — F-09 Inisiasi pembayaran

**Estimasi:** L  
**Dependency:** E3-02, E0-03

**AC:**

- [ ] Halaman checkout menampilkan ringkasan order, countdown, dan metode bayar yang tersedia.
- [ ] Memilih metode membuat `payments` + charge gateway; `payment_url`/QR ditampilkan.
- [ ] `payments.expires_at` disinkronkan dengan expiry gateway dan tidak melebihi `orders.expires_at`.
- [ ] Jika gateway gagal, order tetap `pending`, error ditampilkan, dan pembeli dapat mencoba lagi.
- [ ] Tidak ada data kartu yang disimpan.
- [ ] Feature test dengan `FakeGateway`: charge sukses, gagal, timeout, retry.

### E3-05 — F-09 Webhook & idempotensi

**Estimasi:** L  
**Dependency:** E3-04

**AC:**

- [ ] Endpoint webhook menerima payload gateway, memvalidasi signature, dan menolak yang invalid.
- [ ] Setiap event disimpan di `webhook_events` dengan unique `(gateway, external_id)`.
- [ ] Webhook duplikat tidak menggandakan tiket atau email.
- [ ] Webhook sukses mengubah order `pending`/`expired` → `paid` (sesuai kebijakan late payment) dan memicu penerbitan tiket.
- [ ] Webhook out-of-order (mis. `expired` setelah `success`) tidak menurunkan status `paid`.
- [ ] Respons webhook cepat (< 2 detik); pekerjaan berat masuk queue.
- [ ] Feature test: signature invalid, duplikat, out-of-order, sukses, gagal, late payment.

### E3-06 — F-09 Expiry, rekonsiliasi, late payment

**Estimasi:** M  
**Dependency:** E3-05

**AC:**

- [ ] Job terjadwal menandai order `pending` yang `expires_at < now()` menjadi `expired`.
- [ ] Stok dikembalikan tepat sekali; job idempoten.
- [ ] Job rekonsiliasi memeriksa payment `pending` ke gateway dan memperbarui status.
- [ ] Late payment: jika stok tersedia, order diaktifkan kembali dan tiket terbit; jika tidak, refund otomatis dibuat.
- [ ] Pembeli menerima email untuk expired dan late payment.
- [ ] Feature test: expiry, double run, late payment with/without stock.

### E3-07 — F-10 Penerbitan tiket, QR, PDF, email

**Estimasi:** L  
**Dependency:** E3-05

**AC:**

- [ ] Satu tiket dibuat untuk setiap quantity pada `order_items`.
- [ ] `ticket_code` acak ≥ 128 bit, unik, tidak berurutan, dan tidak mengandung PII.
- [ ] Penerbitan idempoten: retry tidak menggandakan tiket.
- [ ] QR berisi `ticket_code` saja; validasi selalu server-side.
- [ ] PDF berisi event, tanggal, venue, jenis tiket, nama pemegang, kode, dan QR.
- [ ] Email tiket dikirim lewat queue dengan retry 3× dan log `notification_logs`.
- [ ] Tiket tersedia di `/akun/tiket` dan signed URL guest.
- [ ] Jika email gagal permanen, admin dapat melihat dan mengirim ulang.
- [ ] Feature test: quantity → jumlah tiket, uniqueness, retry, PDF, email job, resend.

### E3-08 — F-11 Halaman status pembayaran

**Estimasi:** S  
**Dependency:** E3-05

**AC:**

- [ ] `/checkout/{order}` menampilkan status terkini dan instruksi pembayaran.
- [ ] Halaman melakukan polling status setiap 5 detik (maksimal 15 menit) atau memakai tombol "Cek status".
- [ ] Setelah `paid`, halaman menampilkan link tiket dan email terkirim.
- [ ] Setelah `expired`, halaman menampilkan tombol kembali ke event.
- [ ] Akses hanya untuk pemilik/signed URL; order lain 403.
- [ ] Feature test: status transitions, akses tidak sah.

### E3-09 — A-04 Refund

**Estimasi:** L  
**Dependency:** E3-05, E0-03  
**Catatan P0:** form pengajuan refund mandiri untuk pembeli belum ada; permintaan masuk lewat support/kontak dan diproses admin.

**AC:**

- [ ] Admin dapat membuat refund full untuk order `paid` dengan alasan wajib.
- [ ] Refund membuat record `refunds` status `requested`; proses gateway mengubah ke `processing`/`succeeded`/`failed`.
- [ ] Refund sukses: order `refunded`, tiket `refunded`, email dikirim.
- [ ] Refund idempoten; klik ganda tidak mengirim refund dua kali.
- [ ] Pembatalan event membuat refund batch untuk semua order `paid`.
- [ ] Refund gagal terlihat di admin dan dapat di-retry.
- [ ] Semua aksi refund tercatat di `audit_logs`.
- [ ] Feature test: full refund, duplicate, gateway failure, event cancellation batch.

---

## 8. Epik 4 — Dashboard Organizer (P0)

### E4-01 — O-01 Profil organizer

**Estimasi:** M  
**Dependency:** E0-02

**AC:**

- [ ] Organizer owner dapat mengubah nama, slug, deskripsi, logo, email kontak, phone, dan rekening bank.
- [ ] Slug unik; perubahan slug membuat redirect 301 dari slug lama (jika event sudah publik).
- [ ] Rekening bank hanya dapat dilihat owner/admin; nomor rekening dimasking di log.
- [ ] Organizer belum terverifikasi dapat membuat draft, tetapi tidak dapat submit review/publish.
- [ ] Admin dapat memverifikasi dengan catatan; `verified_at`, `verified_by`, `verification_note` terisi.
- [ ] Semua perubahan tercatat di `audit_logs`.
- [ ] Feature test: update, slug unik, akses non-owner, verifikasi.

### E4-02 — O-02 Kelola event

**Estimasi:** L  
**Dependency:** E4-01

**AC:**

- [ ] Organizer dapat membuat event draft dengan field wajib: judul, kategori, venue/online, start/end, timezone, deskripsi, poster.
- [ ] Upload poster/banner/galeri/lineup divalidasi (mime, ukuran, dimensi) dan disimpan di storage yang dikonfigurasi.
- [ ] Event draft dapat diedit dan dihapus selama belum pernah `published`.
- [ ] Organizer dapat submit `draft → pending_review` hanya jika minimal 1 tiket aktif dan semua field wajib valid.
- [ ] Setelah submit, organizer tidak dapat mengubah field yang memengaruhi penjualan sampai admin menolak atau event dikembalikan ke draft.
- [ ] Organizer melihat status `pending_review`, `rejected` + alasan, `published`, `sold_out`, `ended`, `cancelled`.
- [ ] Event `published` hanya dapat diubah pada field non-kritis (deskripsi, galeri) sesuai kebijakan.
- [ ] Feature test: create, upload invalid, submit incomplete, submit valid, edit after submit, authorization.

### E4-03 — O-03 Kelola jenis tiket

**Estimasi:** M  
**Dependency:** E4-02

**AC:**

- [ ] CRUD tipe tiket: nama, deskripsi, harga, kuota, min/max per order, jadwal jual, aktif/nonaktif.
- [ ] Harga dan kuota tidak boleh negatif; kuota ≥ sold_count.
- [ ] Total kuota semua tipe tiket ≤ kapasitas venue (jika venue memiliki kapasitas); jika melebihi, tampilkan peringatan dan blokir submit.
- [ ] Tipe tiket dengan penjualan tidak dapat dihapus; hanya dinonaktifkan.
- [ ] Perubahan harga tidak mengubah `order_items` lama.
- [ ] Jadwal jual harus berada dalam rentang event dan `sale_start_at < sale_end_at`.
- [ ] Feature test: validasi, kuota, delete with sales, price snapshot.

### E4-04 — O-05 Laporan penjualan

**Estimasi:** M  
**Dependency:** E3-07

**AC:**

- [ ] Dashboard menampilkan total tiket terjual, pendapatan, dan grafik harian.
- [ ] Filter tanggal dan jenis tiket; default 30 hari terakhir.
- [ ] Hanya order `paid` yang dihitung; `pending`, `expired`, `cancelled`, `refunded` dikecualikan.
- [ ] Pendapatan = sum `order_items.subtotal` untuk order paid, dikurangi refund (ditampilkan terpisah).
- [ ] Data hanya untuk event milik organizer; akses event lain 403.
- [ ] Grafik dan angka konsisten; timezone mengikuti event.
- [ ] Query p95 < 500 ms untuk 10.000 order (diuji).
- [ ] Feature test: perhitungan, filter, isolasi organizer.

---

## 9. Epik 5 — Check-in Staff (P0)

### E5-01 — Penugasan staff

**Estimasi:** S  
**Dependency:** E0-02

**AC:**

- [ ] P0: admin dapat menambahkan/menghapus user sebagai staff event dari halaman detail event (form sederhana).
- [ ] P1: organizer dapat mengelola staff sendiri dari dashboard organizer (O-07).
- [ ] Staff hanya dapat membuka `/scan/{event}` untuk event yang ditugaskan.
- [ ] Staff melihat nama event dan gate yang ditugaskan.
- [ ] Staff tidak dapat mengubah data event/tiket.
- [ ] Feature test: assigned/unassigned staff, akses event lain.

### E5-02 — C-01 Scan QR

**Estimasi:** L  
**Dependency:** E5-01, E3-07

**AC:**

- [ ] Halaman scan meminta izin kamera; jika ditolak, tampil instruksi dan opsi input manual (P1).
- [ ] Scan mengirim `ticket_code` ke server; client tidak menentukan validitas.
- [ ] Server memvalidasi: tiket ada, event cocok, order `paid`, tiket `active`.
- [ ] Tiket valid: update atomik ke `used`, catat `check_ins` `success`, tampil nama pemegang, jenis tiket, dan waktu.
- [ ] Tiket sudah digunakan: tampil `duplicate` + waktu/gate pertama; tidak mengubah status.
- [ ] Tiket tidak valid/event salah/refunded/cancelled: tampil `invalid` dengan alasan yang aman.
- [ ] Dua scan bersamaan: satu `success`, satu `duplicate`.
- [ ] Hasil tampil < 3 detik p95 pada koneksi 4G.
- [ ] Scan dapat diulang tanpa menutup halaman; ada riwayat scan sesi.
- [ ] Feature test: valid, duplicate, invalid, wrong event, concurrent, rate limit.

---

## 10. Epik 6 — Admin (P0)

### E6-01 — A-01 Manajemen pengguna & organizer

**Estimasi:** M  
**Dependency:** E0-02

**AC:**

- [ ] Admin dapat mencari user berdasarkan nama/email dan memfilter role/status.
- [ ] Admin dapat menonaktifkan/mengaktifkan user; user nonaktif tidak bisa login.
- [ ] Admin tidak dapat menonaktifkan dirinya sendiri atau admin terakhir.
- [ ] Admin dapat memverifikasi organizer dan melihat status verifikasi.
- [ ] Aksi sensitif tercatat di `audit_logs`.
- [ ] Feature test: search, deactivate, self-deactivate, last admin, verify organizer.

### E6-02 — A-02 Moderasi event

**Estimasi:** M  
**Dependency:** E4-02

**AC:**

- [ ] Admin melihat antrean event `pending_review`, terbaru dulu.
- [ ] Admin dapat melihat detail event, tiket, poster, dan data organizer.
- [ ] Approve mengubah status ke `published`, mengisi `published_at`, `approved_by`, `approved_at`.
- [ ] Reject wajib alasan; status `rejected`, organizer dapat melihat alasan.
- [ ] Admin dapat membatalkan event `published`/`sold_out`; semua order `paid` masuk refund batch.
- [ ] Semua aksi tercatat di `audit_logs` dan organizer mendapat email.
- [ ] Feature test: approve, reject, cancel, authorization, refund trigger.

### E6-03 — A-03 Master data

**Estimasi:** M  
**Dependency:** E0-04

**AC:**

- [ ] CRUD kategori, venue, FAQ, banner dengan validasi.
- [ ] Slug kategori/venue unik; banner memiliki rentang tanggal valid.
- [ ] Data nonaktif tidak tampil di publik.
- [ ] Perubahan master data menghapus cache halaman publik terkait.
- [ ] Aksi tercatat di `audit_logs`.
- [ ] Feature test: CRUD, validasi, cache invalidation, public visibility.

### E6-04 — A-04 Monitoring transaksi & refund

**Estimasi:** M  
**Dependency:** E3-09

**AC:**

- [ ] Admin melihat daftar order dengan filter status, event, tanggal, dan pencarian nomor order/email.
- [ ] Detail order menampilkan item, pembayaran, tiket, dan riwayat refund.
- [ ] Admin dapat membuat refund full sesuai E3-09.
- [ ] Admin dapat melihat payment gagal/expired dan memicu rekonsiliasi manual.
- [ ] Tidak ada data PII berlebih; nomor identitas dimasking.
- [ ] Semua aksi tercatat di `audit_logs`.
- [ ] Feature test: filter, detail, refund, manual reconcile, authorization.

---

## 11. Epik 7 — QA & Launch (P0)

### E7-01 — Load & performance test

- [ ] Skenario: 500 concurrent users, 50 order/menit, 20 scan/detik.
- [ ] Tidak ada overselling pada skenario rebutan tiket.
- [ ] p95 halaman publik < 2 detik; checkout < 2 detik.
- [ ] Queue tidak backlog > 5 menit.
- [ ] Hasil dan bottleneck didokumentasikan.

### E7-02 — Security review

- [ ] Uji otorisasi horizontal/vertikal untuk semua route sensitif.
- [ ] Uji CSRF, XSS, SQL injection, mass assignment, upload berbahaya.
- [ ] Uji signature webhook, replay, dan rate limit.
- [ ] Uji signed URL tampering/expiry.
- [ ] Tidak ada secret/PII di log atau repo.
- [ ] Temuan critical/high ditutup sebelum launch.

### E7-03 — UAT

- [ ] Skenario pembelian guest dan customer.
- [ ] Skenario pembayaran sukses, gagal, expired, late payment.
- [ ] Skenario refund dan pembatalan event.
- [ ] Skenario organizer membuat event sampai tayang.
- [ ] Skenario scan valid/duplikat/invalid.
- [ ] Skenario admin moderasi, master data, refund.
- [ ] Product owner menyetujui hasil UAT.

### E7-04 — Backup & restore drill

- [ ] Backup harian berjalan dan terverifikasi.
- [ ] Restore ke database uji berhasil dalam RTO 4 jam.
- [ ] Dokumen runbook restore tersedia.

### E7-05 — Launch checklist

- [ ] Domain, HTTPS, redirect, dan email domain terverifikasi.
- [ ] SPF/DKIM/DMARC aktif.
- [ ] Gateway produksi aktif dan webhook URL terdaftar.
- [ ] Monitoring dan alert aktif.
- [ ] Legal pages, PSE, dan kontak support siap.
- [ ] On-call dan runbook insiden tersedia.
- [ ] Rollback plan diuji.

---

## 12. Traceability Matrix P0

| Fitur | Story | Status |
|---|---|---|
| F-01 Beranda | E1-01 | Done |
| F-02 Daftar event | E1-02 | Done |
| F-03 Filter & pencarian | E1-03 | Done |
| F-04 Detail event | E1-04 | Done |
| F-05 Registrasi & login | E2-01, E2-02, E2-03 | Todo |
| F-06 Guest checkout | E3-03, E2-06 | Todo |
| F-07 Pilih tiket & checkout | E3-01, E3-02 | Todo |
| F-09 Pembayaran | E3-04, E3-05, E3-06 | Todo |
| F-10 E-tiket | E3-07, E2-05 | Todo |
| F-11 Riwayat pesanan | E2-04, E3-08 | Todo |
| F-13 Halaman informasi | E1-05 | Done |
| O-01 Profil organizer | E4-01 | Todo |
| O-02 Kelola event | E4-02 | Todo |
| O-03 Kelola jenis tiket | E4-03 | Todo |
| O-05 Laporan penjualan | E4-04 | Todo |
| C-01 Scan QR | E5-01, E5-02 | Todo |
| A-01 Manajemen pengguna & organizer | E6-01 | Todo |
| A-02 Moderasi event | E6-02 | Todo |
| A-03 Master data | E6-03 | Todo |
| A-04 Monitoring transaksi & refund | E6-04, E3-09 | Todo |

---

## 13. Rencana Sprint (asumsi 2 minggu/sprint)

| Sprint | Fokus | Output |
|---|---|---|
| **Sprint 0** | E0-01 s/d E0-08 | Skema, role, payment abstraction, layout, CI, staging |
| **Sprint 1** | E1-01 s/d E1-05, E2-01 s/d E2-03 | Publik + auth dasar |
| **Sprint 2** | E3-01 s/d E3-06 | Checkout + pembayaran + webhook |
| **Sprint 3** | E3-07 s/d E3-09, E2-04 s/d E2-06 | E-tiket, riwayat, guest access, refund |
| **Sprint 4** | E4-01 s/d E4-04, E5-01 s/d E5-02 | Organizer + scan |
| **Sprint 5** | E6-01 s/d E6-04, E7-01 s/d E7-05 | Admin + hardening + UAT |

Jika tim hanya 1–2 orang, gabungkan sprint dan perpanjang MVP menjadi 14–16 minggu. Jika payment gateway belum siap di Sprint 2, gunakan `FakeGateway` untuk development dan jangan menunda E3-01/E3-02.

---

## 14. Di Luar Backlog P0

Fitur berikut **tidak boleh** dikerjakan dalam sprint MVP tanpa perubahan scope:

- F-08 voucher, O-04 kelola voucher.
- O-06 ekspor CSV, O-07 kelola staff UI, O-08 pencairan.
- C-02 cari manual, C-03 statistik kehadiran.
- A-05 pesan kontak, A-06 audit log UI.
- F-12 wishlist, F-14 kontak, F-15 WhatsApp.
- Seat map, resale, multi-event cart, loyalty.

Yang boleh disiapkan di P0 hanya **skema dan interface**, bukan UI/alur penuh.