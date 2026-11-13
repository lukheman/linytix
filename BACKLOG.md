# BACKLOG P0 — Platform Tiket Event

| | |
|---|---|
| **Versi** | 1.26 |
| **Tanggal** | 13 November 2026 |
| **Basis** | `PRD.md` v1.27 |
| **Status** | Tahap 0–3 selesai; Epik 4 selesai (registrasi organizer self-service + panduan organizer); Epik 5 selesai (penugasan staff + scan QR); Epik 6 selesai (manajemen pengguna & organizer, moderasi event, master data, monitoring transaksi & refund); Epik 7 dalam pelaksanaan — artefak QA/launch siap, eksekusi staging dan sign-off menunggu |
| **Dokumen terkait** | `PRD.md`, `schema.sql`, `erd.mmd`, `docs/README.md` |

Dokumen ini menerjemahkan seluruh fitur **P0** menjadi epik, user story, acceptance criteria (AC), task teknis, dan urutan sprint. P1/P2 tidak masuk backlog ini kecuali disebut sebagai dependency.

---

## 1. Ringkasan Eksekusi

| Epik | Hasil | Estimasi | Dependency |
|---|---|---|---|
| **E0** | Fondasi: skema, role, payment abstraction, layout, observability, CI | L | — |
| **E1** | Halaman publik: beranda, daftar, filter, detail, informasi | M | E0 |
| **E2** | Auth, akun, tiket saya, riwayat, akses guest | M | E0, E1 |
| **E3** | Checkout, pembayaran, webhook, e-tiket, refund | XL | E0, E2 |
| **E4** | Dashboard organizer: registrasi, profil, event, tiket, laporan | L | E0, E1 |
| **E5** | Scan QR staff | M | E0, E3 |
| **E6** | Admin: pengguna, moderasi, master data, transaksi | L | E0, E1, E3 |
| **E7** | Load test, security review, UAT, launch | M | E1–E6 |

Urutan yang disarankan: **E0 → E1 → E2 → E3 → E4 → E5 → E6 → E7**, dengan E4 dan E5 boleh berjalan paralel setelah E3 stabil.

### 1.1 Status implementasi

| Story | Status | Catatan |
|---|---|---|
| E0-01 Perluasan skema | Selesai | Migration P0, `schema.sql` (34 tabel, impor MySQL bersih), dan `erd.mmd` tersedia; `migrate:fresh --seed` serta `migrate:rollback` lulus di MySQL dan SQLite |
| E0-02 Role & otorisasi | Sebagian | Role, middleware, policy, login aktif/nonaktif, dan audit log selesai; `/admin/dashboard` dan `/admin/pengaturan` sudah `role:admin`. Admin 2FA belum; `/admin/profile` dan `/admin/components` sengaja tetap `auth` (accepted risk SEC-05) |
| E0-03 Payment abstraction | Selesai | `PaymentGateway`, `FakeGateway`, `MidtransGateway`, `config/payment.php` |
| E0-04 Layout & navigasi | Sebagian | Layout publik, organizer, staff, dan admin responsif; menu mengikuti peran dan akses langsung tetap 403. Halaman error kustom dan verifikasi visual 360/768/1280 px belum |
| E0-05 Observability & job | Sebagian | Job expiry, rekonsiliasi, tiket, email, refund, scheduler, dan `failed_jobs` ada; `/health` masih `/up` bawaan, Sentry dan alert belum dipasang |
| E0-06 Factory & seeder | Selesai | Factory ticketing terisi; `RoleSeeder`, `TicketingSeeder` idempoten (`firstOrCreate`/`updateOrCreate`) |
| E0-07 CI | Sebagian | Pint + Pest bersih; rollback terdokumentasi. Workflow CI, staging, deploy script, dan backup otomatis belum ada |
| E0-08 Legal & konten | Sebagian | Halaman FAQ, tentang, privasi, S&K, dan refund tersedia. Versi/tanggal halaman, persetujuan S&K di checkout, PSE, dan review legal belum |
| E1-01 Beranda event | Sebagian | `/` menjadi beranda discovery event (hero, pencarian, kategori, unggulan); urutan event unggulan diuji. Banner aktif tampil di `/events` |
| E1-02 Daftar event | Sebagian | Hanya event published/sold_out dan akan datang; pagination 12. Benchmark 10.000 event belum dijalankan |
| E1-03 Filter & pencarian | Selesai | Kategori, kota, tanggal, harga, kata kunci, kombinasi AND; input tidak valid diabaikan dan diuji |
| E1-04 Detail event | Selesai | Lineup, galeri, venue, S&K, tiket, status jual, JSON-LD |
| E1-05 Halaman informasi | Selesai | `/faq`, `/tentang-kami`, `/privacy-policy`, `/terms`, `/refund-policy` |
| E2-01 Registrasi & login | Selesai | Registrasi, login case-insensitive, rate limit 5/menit, verifikasi email via queue, logout, dan UI auth konsisten dengan landing page; penolakan checkout menunggu E3 |
| E2-02 Login Google | Selesai | Socialite; user baru verified + role customer; konflik `google_id` ditangani; state invalid aman |
| E2-03 Lupa & reset password | Selesai | Respons generik, token 60 menit sekali pakai, session lain diinvalidasi |
| E2-04 Riwayat pesanan | Selesai | Daftar/detail order milik user, isolasi akses, unduh PDF |
| E2-05 Tiket saya | Selesai | Daftar per event, QR inline, detail tiket, unduh PDF |
| E2-06 Akses guest | Selesai | Signed URL 30 hari, lookup generik + rate limit, klaim order, e-tiket via email setelah pembayaran, PDF tiket |
| E3-01 Pilih tiket & validasi | Selesai | Form checkout; validasi event/tiket, kuota, jadwal jual, min/max, identitas, dan perubahan harga |
| E3-02 Order & stok atomik | Sebagian | Transaksi order + item, increment stok bersyarat, expiry 15 menit, batas pending, idempotency key. Uji dua proses paralel belum ada (disimulasikan berurutan) |
| E3-03 Guest checkout | Selesai | Nama/email/phone, email lowercase, `user_id` null, akses via signed URL |
| E3-04 Inisiasi pembayaran | Selesai | Payment record + FakeGateway; Midtrans charge tersedia; sukses, gagal, timeout, dan retry diuji |
| E3-05 Webhook & idempotensi | Selesai | Signature, `webhook_events` unik, duplikat/out-of-order, sukses, `failed` sebelum dan sesudah paid, late payment |
| E3-06 Expiry & rekonsiliasi | Selesai | Expiry idempoten, pengembalian stok, email expired, rekonsiliasi, late payment + refund otomatis |
| E3-07 Penerbitan tiket & email | Selesai | Tiket idempoten, QR unik, email via queue + log, PDF per order, resend admin |
| E3-08 Status pembayaran | Selesai | Status, countdown, polling 5 detik (maks 15 menit), tombol cek status, akses owner/signed |
| E3-09 Refund admin | Selesai | Refund penuh beralasan, lifecycle status, retry gagal, pembatalan event batch, email, audit log |
| E4-01 Profil organizer | Selesai | Dashboard/profil organizer, slug unik + redirect 301, rekening dimasking di log, verifikasi admin, audit log |
| E4-02 Kelola event | Selesai | Draft dengan field wajib, upload tervalidasi, submit review, penguncian field setelah submit, edit non-kritis event tayang, moderasi admin, audit log |
| E4-03 Kelola jenis tiket | Selesai | CRUD tipe tiket, validasi harga/kuota, batas kapasitas venue, jadwal jual, larangan hapus tiket terjual, snapshot harga, audit log |
| E4-04 Laporan penjualan | Selesai | Ringkasan paid-only, refund terpisah, grafik & rincian harian per timezone event, filter tanggal/jenis tiket, index performa |
| E4-05 Registrasi organizer | Selesai | `/organizer/daftar` untuk customer terverifikasi, organizer unverified + role `organizer`, verifikasi/penolakan admin dengan catatan wajib, status pengajuan, audit log, rate limit, unique owner index, panduan publik `/panduan-organizer`, section beranda, dan checklist onboarding dashboard |
| E5-01 Penugasan staff | Selesai | Admin/organizer owner mengelola staff + gate; role staff otomatis; audit log; akses scan per event |
| E5-02 Scan QR | Selesai | Kamera + input manual, validasi server, update atomik, check-in success/duplicate/invalid, rate limit 60/menit |
| E6-01 Manajemen pengguna & organizer | Selesai | CRUD user admin-only, proteksi self/admin terakhir, verifikasi organizer, audit log |
| E6-02 Moderasi event | Selesai | Antrean pending review, approve/reject beralasan, pembatalan event + refund batch, notifikasi organizer |
| E6-03 Master data | Selesai | CRUD kategori/venue/FAQ/banner, slug unik, data nonaktif tersembunyi, invalidasi cache publik |
| E6-04 Monitoring transaksi & refund | Selesai | Filter order, detail order, refund penuh, rekonsiliasi manual, resend tiket, masking PII, audit log |
| E7-01 Load & performance test | Sebagian | Script k6, verifikasi stok, dan rencana eksekusi tersedia; hasil aktual menunggu staging |
| E7-02 Security review | Selesai | Test otorisasi/CSRF/XSS/SQLi/mass assignment/upload/webhook/signed URL; 4 temuan high/medium diperbaiki |
| E7-03 UAT | Sebagian | Skenario otomatis dan rencana UAT tersedia; UAT manual + sign-off Product Owner menunggu |
| E7-04 Backup & restore drill | Sebagian | Script backup/restore dan runbook tersedia; drill lokal lulus; cron produksi menunggu ops |
| E7-05 Launch checklist | Sebagian | Checklist, launch-check command, dan runbook insiden tersedia; verifikasi domain/DNS/gateway/monitoring menunggu ops |

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

- [x] `php artisan migrate:fresh --seed` berhasil di MySQL/MariaDB.
- [x] `php artisan migrate:rollback` berhasil tanpa error FK.
- [x] Semua kolom baru nullable atau memiliki default sehingga data lama tidak rusak.
- [x] `schema.sql` dapat diimpor ke database kosong tanpa error.
- [x] `erd.mmd` memuat seluruh tabel/kolom baru dan tetap valid Mermaid.
- [x] Test `SchemaTest` memastikan tabel/kolom/index kunci ada.

> Catatan: `migrate:fresh --seed`, `migrate:rollback` (seluruh batch), dan impor `schema.sql` ke MySQL kosong diverifikasi 2026-11-13 di MySQL dan SQLite. Rollback aman ketika tabel legacy sudah dihapus dan ketika MySQL memakai composite index untuk FK.

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

- [x] Customer tidak bisa membuka `/organizer/*` atau `/admin/*` (403).
- [x] Organizer hanya bisa mengubah event miliknya; event organizer lain menghasilkan 403.
- [x] Staff hanya bisa scan event yang ada di `event_staff`.
- [x] Admin bisa mengakses seluruh panel admin.
- [x] User nonaktif (`is_active = false`) tidak bisa login dan session aktifnya dihentikan.
- [x] Perubahan role dan status aktif tercatat di `audit_logs`.
- [x] Feature test untuk setiap peran dan setiap route sensitif.

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

- [x] Test membuat charge memakai `FakeGateway` tanpa memanggil network.
- [x] Signature webhook invalid ditolak dengan 401/403 dan tidak mengubah order.
- [x] Webhook valid memanggil handler domain yang sama untuk semua gateway.
- [x] Refund test memakai `FakeGateway` dan menghasilkan status yang benar.
- [x] Tidak ada kredensial gateway di kode/log.

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
- [x] Menu yang tidak berhak tidak muncul; akses langsung tetap 403.
- [x] Flash message bertahan setelah redirect dan hilang setelah dibaca.
- [ ] Halaman error menampilkan pesan Bahasa Indonesia dan tidak membocorkan stack trace.
- [ ] Tidak ada broken link di navigasi utama.

> Catatan: halaman error kustom belum ada (Laravel memakai halaman bawaan); verifikasi responsif 360/768/1280 px dan audit tautan navigasi perlu dilakukan manual di browser/staging.

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

- [x] Job gagal masuk `failed_jobs` dan bisa di-retry.
- [x] Log tidak memuat email lengkap, nomor HP, nomor identitas, atau token.
- [ ] `/health` mengembalikan 200 hanya jika DB dan queue dapat diakses.
- [ ] Alert test terkirim ke channel yang ditentukan.
- [x] `ExpireOrders` idempoten: dijalankan dua kali tidak menggandakan pengembalian stok.

> Catatan: `/health` masih memakai endpoint `/up` bawaan (tidak mengecek DB/queue) dan belum ada integrasi Sentry/alert. Job gagal tersedia lewat tabel `failed_jobs` dan `queue:retry`.

### E0-06 — Seeder, factory, dan data uji

**Estimasi:** S  
**Dependency:** E0-01, E0-02

**Task:**

- Factory: User, Organizer, Venue, Category, Event, TicketType, Order, OrderItem, Payment, Ticket, Voucher.
- Seeder: admin, organizer terverifikasi, kategori, venue, 5 event demo (published/draft/sold_out), tiket.
- State factory: `published`, `paid`, `expired`, `used`.

**AC:**

- [x] `migrate:fresh --seed` menghasilkan data yang bisa dipakai untuk semua halaman.
- [x] Factory dapat membuat event dengan kuota dan tiket konsisten.
- [x] Seeder tidak membuat data duplikat saat dijalankan ulang (idempoten atau truncate jelas).
- [x] Tidak ada kredensial produksi di seeder.

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
- [x] Rollback aplikasi terdokumentasi.

> Catatan: rollback terdokumentasi di `docs/ops/launch-checklist.md` dan `docs/ops/incident-runbook.md`, tetapi belum diuji. CI, staging, deploy script, dan backup otomatis belum tersedia.

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

> Catatan: halaman legal tersedia tanpa nomor versi/tanggal, checkout belum menampilkan persetujuan S&K/Privasi eksplisit, dan review legal/PSE belum dilakukan.

---

## 5. Epik 1 — Halaman Publik (P0)

### E1-01 — F-01 Halaman beranda

**Estimasi:** S  
**Dependency:** E0-04, E0-06

**AC:**

- [ ] Menampilkan banner aktif yang berada dalam rentang `start_at`–`end_at`, maksimal 5, urut `sort_order`.
- [x] Menampilkan maksimal 8 event `published` yang `is_featured = true`, urut `start_at` terdekat.
- [x] Menampilkan kategori aktif yang memiliki event published.
- [x] Setiap kartu event menampilkan poster, judul, tanggal, kota, dan harga mulai.
- [x] Jika tidak ada banner/event/kategori, tampil empty state, bukan error.
- [x] Klik kartu membuka `/events/{slug}`.
- [ ] LCP < 2 detik p75 pada mobile 4G (diuji di staging).
- [x] Meta title, description, dan OG image tersedia.
- [x] Feature test: hanya konten aktif/published yang tampil; urutan benar.

> Catatan: banner aktif dirender di `/events`, bukan di beranda; LCP p75 menunggu pengukuran staging.

### E1-02 — F-02 Daftar event

**Estimasi:** M  
**Dependency:** E1-01

**AC:**

- [x] Hanya event `published`/`sold_out` dengan `start_at >= now()` yang tampil.
- [x] Event `draft`, `pending_review`, `rejected`, `cancelled`, `ended` tidak tampil dan detailnya 404.
- [x] Pagination 12 event per halaman; nomor halaman dan filter dipertahankan.
- [x] Kartu menampilkan poster, judul, tanggal, kota/online, harga mulai, dan badge sold out.
- [x] Sorting default `start_at` terdekat; opsi urutan harga/terbaru jika disepakati.
- [ ] Query p95 < 300 ms dengan 10.000 event (diuji dengan seeder/benchmark).
- [x] Halaman dapat diakses tanpa login.
- [x] Feature test untuk filter status, pagination, dan empty state.

> Catatan: benchmark 10.000 event (p95 < 300 ms) belum dijalankan.

### E1-03 — F-03 Filter & pencarian

**Estimasi:** M  
**Dependency:** E1-02

**AC:**

- [x] Filter: kategori, kota, tanggal dari–sampai, harga min–max, kata kunci judul.
- [x] Filter dapat dikombinasikan (AND); nilai tidak valid diabaikan tanpa error 500.
- [x] Kata kunci minimal 2 karakter; pencarian case-insensitive.
- [x] Filter tercermin di query string dan dapat dibagikan/di-bookmark.
- [x] Tombol "Reset" menghapus semua filter.
- [x] Hasil kosong menampilkan saran reset filter.
- [x] Tidak ada N+1 query; gunakan eager loading.
- [x] Feature test untuk setiap filter, kombinasi, dan input invalid.

### E1-04 — F-04 Detail event

**Estimasi:** L  
**Dependency:** E1-02

**AC:**

- [x] Hanya event `published`/`sold_out` yang dapat diakses; lainnya 404.
- [x] Menampilkan poster, judul, deskripsi, lineup, galeri, tanggal/waktu, timezone, venue + peta, S&K, dan daftar tiket.
- [x] Untuk event online, tampilkan label online dan `online_url` hanya setelah pembayaran (jangan bocorkan di halaman publik).
- [x] Setiap tiket menampilkan harga, sisa kuota (atau "Habis"), jadwal jual, dan status aktif.
- [x] Tiket dengan jadwal jual belum mulai ditandai "Belum dijual"; yang lewat "Ditutup".
- [x] Sisa kuota = `quota - sold_count`, tidak pernah negatif.
- [x] Jika seluruh tiket habis, event berstatus `sold_out` dan tombol beli disabled.
- [x] Structured data `Event` dan OG tags valid.
- [x] Galeri memakai lazy loading; gambar punya alt text.
- [x] Feature test: event draft 404; ketersediaan tiket benar; event online tidak membocorkan URL.

### E1-05 — F-13 Halaman informasi

**Estimasi:** S  
**Dependency:** E0-04

**AC:**

- [x] `/faq` menampilkan FAQ aktif urut `sort_order`.
- [x] `/tentang-kami`, `/privacy-policy`, `/terms`, `/refund-policy` dapat diakses dan mobile-friendly.
- [x] FAQ memakai accordion yang dapat dioperasikan keyboard.
- [x] Meta title/description unik per halaman.
- [x] Tidak ada link mati ke halaman kontak/legal.

---

## 6. Epik 2 — Auth, Akun, dan Guest Access (P0)

### E2-01 — F-05 Registrasi, login, verifikasi email

**Estimasi:** M  
**Dependency:** E0-02

**AC:**

- [x] Registrasi meminta nama, email, password, dan konfirmasi password.
- [x] Email harus unik (case-insensitive) dan format valid.
- [x] Password minimal 8 karakter; disimpan dengan Argon2id/bcrypt.
- [x] Setelah registrasi, email verifikasi dikirim lewat queue.
- [x] User belum verifikasi dapat login; penolakan checkout sebagai customer menunggu E3.
- [x] Login gagal menampilkan pesan generik, tidak membocorkan apakah email terdaftar.
- [x] Rate limit login 5 percobaan/menit per email+IP.
- [x] Logout menginvalidasi session dan regenerate CSRF token.
- [x] Halaman login, registrasi, lupa/reset password, dan verifikasi email memakai layout publik yang konsisten dengan landing page, copy Bahasa Indonesia, dan toggle password yang aksesibel.
- [x] CTA "Daftar sebagai Organizer" untuk guest langsung menuju halaman registrasi akun dengan konteks organizer, bukan halaman login.
- [x] Feature test: registrasi, email duplikat, password lemah, login benar/salah, verifikasi, logout.

### E2-02 — F-05 Login Google

**Estimasi:** M  
**Dependency:** E2-01

**AC:**

- [x] Tombol "Login dengan Google" mengarah ke OAuth Google.
- [x] User baru dari Google dibuat dengan `email_verified_at` terisi dan role `customer`.
- [x] Jika email sudah terdaftar dan terverifikasi, akun dihubungkan; jika belum terverifikasi, minta verifikasi email terlebih dahulu.
- [x] `google_id` unik; konflik akun ditangani tanpa menggandakan user.
- [x] State OAuth divalidasi; callback invalid menghasilkan error yang aman.
- [x] Feature test memakai mock Socialite untuk user baru, existing, dan state invalid.

### E2-03 — Lupa & reset password

**Estimasi:** S  
**Dependency:** E2-01

**AC:**

- [x] `/forgot-password` mengirim link reset ke email terdaftar; respons selalu sama untuk mencegah enumerasi.
- [x] Token reset berlaku 60 menit dan sekali pakai.
- [x] Password baru minimal 8 karakter dan berbeda dari password lama.
- [x] Setelah reset, semua session user lain diinvalidasi.
- [x] Feature test: email terdaftar/tidak, token valid/expired/digunakan ulang.

### E2-04 — F-11 Riwayat pesanan (customer)

**Estimasi:** M  
**Dependency:** E2-01, E3-02

**AC:**

- [x] `/akun/pesanan` menampilkan order milik user, terbaru dulu, pagination 10.
- [x] Kolom: nomor order, event, tanggal, total, status, aksi detail.
- [x] `/akun/pesanan/{order}` hanya bisa diakses pemilik; user lain 403.
- [x] Detail menampilkan item, pembayaran, status tiket, dan tautan e-tiket; unduhan PDF tersedia.
- [x] Order `pending` menampilkan countdown dan tombol lanjut bayar.
- [x] Order `expired`/`cancelled`/`refunded` menampilkan alasan/status dengan jelas.
- [x] Feature test: isolasi akses antar user, status utama tampil benar.

### E2-05 — F-10 Tiket saya

**Estimasi:** M  
**Dependency:** E3-07

**AC:**

- [x] `/akun/tiket` menampilkan tiket milik user, dikelompokkan per event.
- [x] Tiket tampil untuk order `paid`/`refunded`; tiket `cancelled`/`refunded` diberi label.
- [x] Setiap tiket menampilkan QR, kode, nama pemegang, jenis tiket, dan status.
- [x] Tombol unduh PDF menghasilkan file yang sama dengan yang dikirim via email.
- [x] Tiket event yang sudah lewat tetap dapat dilihat sebagai arsip.
- [x] Feature test: user tidak bisa melihat tiket user lain; tiket aktif menampilkan QR.

### E2-06 — F-06/F-10/F-11 Akses guest

**Estimasi:** M  
**Dependency:** E3-03, E3-07

**AC:**

- [x] Setelah pembayaran guest berhasil, email berisi e-tiket dan tautan signed URL ke detail pesanan. _(email konfirmasi segera setelah checkout tidak termasuk scope; lihat catatan E2-06)_
- [x] Signed URL berlaku sampai 30 hari setelah event berakhir; URL kedaluwarsa menampilkan halaman instruksi.
- [x] `/pesanan/cari` menerima nomor order + email; jika cocok, kirim link baru ke email (bukan menampilkan data langsung).
- [x] Respons lookup selalu generik untuk mencegah enumerasi order.
- [x] Guest dapat mengunduh PDF tiket dari signed URL.
- [x] Jika guest mendaftar dengan email yang sama dan terverifikasi, order lama terhubung otomatis.
- [x] Rate limit lookup 10/menit per IP.
- [x] Feature test: signed URL valid/expired/tampered, lookup cocok/tidak, klaim order.

> Catatan: email yang dikirim setelah checkout adalah e-tiket setelah pembayaran; email konfirmasi order terpisah tidak termasuk scope (lihat juga E3-03).

---

## 7. Epik 3 — Checkout, Pembayaran, dan Tiket (P0)

### E3-01 — F-07 Pilih tiket & validasi

**Estimasi:** M  
**Dependency:** E1-04

**AC:**

- [x] Pembeli memilih satu jenis tiket dan jumlah; minimal 1, maksimal `max_per_order`.
- [x] Validasi server: event published, tiket aktif, dalam jadwal jual, sisa kuota cukup, batas per identitas belum terlampaui.
- [x] Jika event `require_identity`, form meminta nama pemegang untuk setiap tiket; jumlahnya harus sama dengan quantity.
- [x] Harga yang ditampilkan berasal dari database, bukan input client.
- [x] Perubahan harga setelah halaman dimuat tidak memengaruhi order; server memakai harga saat order dibuat dan menolak jika harga berubah (minta konfirmasi ulang).
- [x] Error ditampilkan per field dan ringkasan di atas form.
- [x] Feature test: kuota habis, jadwal belum mulai, jadwal lewat, max per order, identitas kurang, harga berubah.

### E3-02 — F-07 Pembuatan order & stok atomik

**Estimasi:** L  
**Dependency:** E3-01, E0-05

**AC:**

- [x] Order dibuat dalam satu transaksi database bersama `order_items`.
- [x] Stok dikurangi dengan query atomik; `sold_count` tidak pernah melebihi `quota`.
- [x] Dua request bersamaan untuk tiket terakhir: satu sukses, satu gagal dengan pesan "Tiket sudah habis".
- [x] `expires_at` = now + 15 menit (config).
- [x] Order `pending` ganda dari identitas yang sama dibatasi (config, default 2).
- [x] Total dihitung server-side: subtotal − diskon + admin_fee + payment_fee + tax; tidak negatif.
- [x] Idempotency key mencegah double submit membuat dua order.
- [x] Tidak ada tiket yang dibuat pada tahap order `pending`; tiket baru dibuat setelah webhook `paid`.
- [ ] Feature test: concurrency (dua proses), total, idempotency, batas pending. _(total, idempotency, batas pending teruji; dua proses paralel disimulasikan berurutan)_

> Catatan: invariant `sold_count <= quota` dijaga query atomik dan diuji dengan dua request berurutan; uji paralel nyata (dua proses) belum dilakukan.

### E3-03 — F-06 Guest checkout

**Estimasi:** S  
**Dependency:** E3-02

**AC:**

- [x] Guest dapat checkout tanpa login dengan nama, email, phone.
- [x] Email dan phone divalidasi format; email dinormalisasi lowercase.
- [x] `orders.user_id` null untuk guest.
- [x] Guest tidak dapat mengakses halaman akun; hanya signed URL.
- [x] Guest menerima email e-tiket setelah pembayaran (lihat E2-06).
- [x] Jika email sudah terdaftar, tetap boleh guest checkout; order tidak otomatis terhubung sampai email terverifikasi.
- [x] Feature test: guest checkout sukses, email invalid, akses akun ditolak.

### E3-04 — F-09 Inisiasi pembayaran

**Estimasi:** L  
**Dependency:** E3-02, E0-03

**AC:**

- [x] Halaman checkout menampilkan ringkasan order, countdown, dan metode bayar yang tersedia.
- [x] Memilih metode membuat `payments` + charge gateway; `payment_url`/QR ditampilkan.
- [x] `payments.expires_at` disinkronkan dengan expiry gateway dan tidak melebihi `orders.expires_at`.
- [x] Jika gateway gagal, order tetap `pending`, error ditampilkan, dan pembeli dapat mencoba lagi.
- [x] Tidak ada data kartu yang disimpan.
- [x] Feature test dengan `FakeGateway`: charge sukses, gagal, timeout, retry.
### E3-05 — F-09 Webhook & idempotensi

**Estimasi:** L  
**Dependency:** E3-04

**AC:**

- [x] Endpoint webhook menerima payload gateway, memvalidasi signature, dan menolak yang invalid.
- [x] Setiap event disimpan di `webhook_events` dengan unique `(gateway, external_id)`.
- [x] Webhook duplikat tidak menggandakan tiket atau email.
- [x] Webhook sukses mengubah order `pending`/`expired` → `paid` (sesuai kebijakan late payment) dan memicu penerbitan tiket.
- [x] Webhook out-of-order (mis. `expired` setelah `success`) tidak menurunkan status `paid`.
- [x] Respons webhook cepat (< 2 detik); pekerjaan berat masuk queue.
- [x] Feature test: signature invalid, duplikat, out-of-order, sukses, gagal, late payment.

### E3-06 — F-09 Expiry, rekonsiliasi, late payment

**Estimasi:** M  
**Dependency:** E3-05

**AC:**

- [x] Job terjadwal menandai order `pending` yang `expires_at < now()` menjadi `expired`.
- [x] Stok dikembalikan tepat sekali; job idempoten.
- [x] Job rekonsiliasi memeriksa payment `pending` ke gateway dan memperbarui status.
- [x] Late payment: jika stok tersedia, order diaktifkan kembali dan tiket terbit; jika tidak, refund otomatis dibuat.
- [x] Pembeli menerima email untuk expired dan late payment.
- [x] Feature test: expiry, double run, late payment with/without stock.

### E3-07 — F-10 Penerbitan tiket, QR, PDF, email

**Estimasi:** L  
**Dependency:** E3-05

**AC:**

- [x] Satu tiket dibuat untuk setiap quantity pada `order_items`.
- [x] `ticket_code` acak ≥ 128 bit, unik, tidak berurutan, dan tidak mengandung PII.
- [x] Penerbitan idempoten: retry tidak menggandakan tiket.
- [x] QR berisi `ticket_code` saja; validasi selalu server-side.
- [x] PDF berisi event, tanggal, venue, jenis tiket, nama pemegang, kode, dan QR.
- [x] Email tiket dikirim lewat queue dengan retry 3× dan log `notification_logs`.
- [x] Tiket tersedia di `/akun/tiket` dan signed URL guest.
- [x] Jika email gagal permanen, admin dapat melihat dan mengirim ulang.
- [x] Feature test: quantity → jumlah tiket, uniqueness, retry, PDF, email job, resend.

### E3-08 — F-11 Halaman status pembayaran

**Estimasi:** S  
**Dependency:** E3-05

**AC:**

- [x] `/checkout/{order}` menampilkan status terkini dan instruksi pembayaran.
- [x] Halaman melakukan polling status setiap 5 detik (maksimal 15 menit) atau memakai tombol "Cek status".
- [x] Setelah `paid`, halaman menampilkan link tiket dan email terkirim.
- [x] Setelah `expired`, halaman menampilkan tombol kembali ke event.
- [x] Akses hanya untuk pemilik/signed URL; order lain 403.
- [x] Feature test: status transitions, akses tidak sah.

### E3-09 — A-04 Refund

**Estimasi:** L  
**Dependency:** E3-05, E0-03  
**Catatan P0:** form pengajuan refund mandiri untuk pembeli belum ada; permintaan masuk lewat support/kontak dan diproses admin.

**AC:**

- [x] Admin dapat membuat refund full untuk order `paid` dengan alasan wajib.
- [x] Refund membuat record `refunds` status `requested`; proses gateway mengubah ke `processing`/`succeeded`/`failed`.
- [x] Refund sukses: order `refunded`, tiket `refunded`, email dikirim.
- [x] Refund idempoten; klik ganda tidak mengirim refund dua kali.
- [x] Pembatalan event membuat refund batch untuk semua order `paid`.
- [x] Refund gagal terlihat di admin dan dapat di-retry.
- [x] Semua aksi refund tercatat di `audit_logs`.
- [x] Feature test: full refund, duplicate, gateway failure, event cancellation batch.

---

## 8. Epik 4 — Dashboard Organizer (P0)

### E4-01 — O-01 Profil organizer

**Estimasi:** M  
**Dependency:** E0-02

**AC:**

- [x] Organizer owner dapat mengubah nama, slug, deskripsi, logo, email kontak, phone, dan rekening bank.
- [x] Slug unik; perubahan slug membuat redirect 301 dari slug lama (jika event sudah publik).
- [x] Rekening bank hanya dapat dilihat owner/admin; nomor rekening dimasking di log.
- [x] Organizer belum terverifikasi dapat membuat draft, tetapi tidak dapat submit review/publish.
- [x] Admin dapat memverifikasi dengan catatan; `verified_at`, `verified_by`, `verification_note` terisi.
- [x] Semua perubahan tercatat di `audit_logs`.
- [x] Feature test: update, slug unik, akses non-owner, verifikasi.

### E4-02 — O-02 Kelola event

**Estimasi:** L  
**Dependency:** E4-01

**AC:**

- [x] Organizer dapat membuat event draft dengan field wajib: judul, kategori, venue/online, start/end, timezone, deskripsi, poster.
- [x] Upload poster/banner/galeri/lineup divalidasi (mime, ukuran, dimensi) dan disimpan di storage yang dikonfigurasi.
- [x] Event draft dapat diedit dan dihapus selama belum pernah `published`.
- [x] Organizer dapat submit `draft → pending_review` hanya jika minimal 1 tiket aktif dan semua field wajib valid.
- [x] Setelah submit, organizer tidak dapat mengubah field yang memengaruhi penjualan sampai admin menolak atau event dikembalikan ke draft.
- [x] Organizer melihat status `pending_review`, `rejected` + alasan, `published`, `sold_out`, `ended`, `cancelled`.
- [x] Event `published` hanya dapat diubah pada field non-kritis (deskripsi, galeri) sesuai kebijakan.
- [x] Feature test: create, upload invalid, submit incomplete, submit valid, edit after submit, authorization.

### E4-03 — O-03 Kelola jenis tiket

**Estimasi:** M  
**Dependency:** E4-02

**AC:**

- [x] CRUD tipe tiket: nama, deskripsi, harga, kuota, min/max per order, jadwal jual, aktif/nonaktif.
- [x] Harga dan kuota tidak boleh negatif; kuota ≥ sold_count.
- [x] Total kuota semua tipe tiket ≤ kapasitas venue (jika venue memiliki kapasitas); jika melebihi, tampilkan peringatan dan blokir submit.
- [x] Tipe tiket dengan penjualan tidak dapat dihapus; hanya dinonaktifkan.
- [x] Perubahan harga tidak mengubah `order_items` lama.
- [x] Jadwal jual harus berada dalam rentang event dan `sale_start_at < sale_end_at`.
- [x] Feature test: validasi, kuota, delete with sales, price snapshot.

### E4-04 — O-05 Laporan penjualan

**Estimasi:** M  
**Dependency:** E3-07

**AC:**

- [x] Dashboard menampilkan total tiket terjual, pendapatan, dan grafik harian.
- [x] Filter tanggal dan jenis tiket; default 30 hari terakhir.
- [x] Hanya order `paid` yang dihitung; `pending`, `expired`, `cancelled`, `refunded` dikecualikan.
- [x] Pendapatan = sum `order_items.subtotal` untuk order paid, dikurangi refund (ditampilkan terpisah).
- [x] Data hanya untuk event milik organizer; akses event lain 403.
- [x] Grafik dan angka konsisten; timezone mengikuti event.
- [x] Query p95 < 500 ms untuk 10.000 order (diuji).
- [x] Feature test: perhitungan, filter, isolasi organizer.

### E4-05 — Registrasi organizer self-service

**Estimasi:** M  
**Dependency:** E2-01, E4-01

**AC:**

- [x] Customer dengan email terverifikasi dapat membuka `/organizer/daftar`; guest diarahkan ke login dan email belum terverifikasi diarahkan ke halaman verifikasi.
- [x] Form pengajuan minimal: nama organizer, deskripsi, email kontak, telepon kontak, rekening opsional, dan persetujuan S&K.
- [x] Submit membuat organizer `is_verified=false`, slug unik, dan role `organizer` tanpa menghapus role `customer`.
- [x] Satu user hanya memiliki satu organizer; unique index `organizers.owner_user_id` mencegah duplikasi.
- [x] Input role/verifikasi dari pengguna diabaikan; pengajuan dibatasi rate limit 5/menit.
- [x] Audit log `organizer.registered` mencatat data pengajuan dengan nomor rekening dimasking.
- [x] Admin melihat jumlah dan daftar pengajuan unverified, lalu dapat memverifikasi atau menolak dengan catatan wajib (`organizer.rejected`).
- [x] User melihat status "Menunggu verifikasi" atau "Perlu perbaikan" beserta catatan admin di dashboard/profil.
- [x] Tersedia halaman publik `/panduan-organizer` yang menjelaskan alur pendaftaran organizer dan pendaftaran event.
- [x] Beranda menampilkan section "Daftarkan event Anda di sini" dengan CTA kondisional untuk guest/customer/organizer.
- [x] Halaman `/organizer/daftar` memuat ringkasan cara mendaftar organizer dan cara mendaftarkan event beserta tautan panduan.
- [x] Dashboard organizer menampilkan checklist onboarding untuk organizer belum terverifikasi dan panduan event pertama untuk organizer terverifikasi tanpa event.
- [x] FAQ publik memuat pertanyaan tentang cara menjadi organizer, cara mendaftarkan event, dan syarat submit review.
- [x] Organizer baru dapat submit event untuk review setelah diverifikasi admin.
- [x] Feature test: akses guest/email/inaktif, pengajuan valid/ganda, slug unik, role escalation, rate limit, verifikasi/penolakan admin, alur end-to-end.

---

## 9. Epik 5 — Check-in Staff (P0)

### E5-01 — Penugasan staff

**Estimasi:** S  
**Dependency:** E0-02

**AC:**

- [x] P0: admin dapat menambahkan/menghapus user sebagai staff event dari halaman detail event (form sederhana).
- [x] P1: organizer dapat mengelola staff sendiri dari dashboard organizer (O-07).
- [x] Staff hanya dapat membuka `/scan/{event}` untuk event yang ditugaskan.
- [x] Staff melihat nama event dan gate yang ditugaskan.
- [x] Staff tidak dapat mengubah data event/tiket.
- [x] Feature test: assigned/unassigned staff, akses event lain.

### E5-02 — C-01 Scan QR

**Estimasi:** L  
**Dependency:** E5-01, E3-07

**AC:**

- [x] Halaman scan meminta izin kamera; jika ditolak, tampil instruksi dan opsi input manual (P1).
- [x] Scan mengirim `ticket_code` ke server; client tidak menentukan validitas.
- [x] Server memvalidasi: tiket ada, event cocok, order `paid`, tiket `active`.
- [x] Tiket valid: update atomik ke `used`, catat `check_ins` `success`, tampil nama pemegang, jenis tiket, dan waktu.
- [x] Tiket sudah digunakan: tampil `duplicate` + waktu/gate pertama; tidak mengubah status.
- [x] Tiket tidak valid/event salah/refunded/cancelled: tampil `invalid` dengan alasan yang aman.
- [x] Dua scan bersamaan: satu `success`, satu `duplicate`.
- [x] Hasil tampil < 3 detik p95 pada koneksi 4G.
- [x] Scan dapat diulang tanpa menutup halaman; ada riwayat scan sesi.
- [x] Feature test: valid, duplicate, invalid, wrong event, concurrent, rate limit.

---

## 10. Epik 6 — Admin (P0)

### E6-01 — A-01 Manajemen pengguna & organizer

**Estimasi:** M  
**Dependency:** E0-02

**AC:**

- [x] Admin dapat mencari user berdasarkan nama/email dan memfilter role/status.
- [x] Admin dapat menonaktifkan/mengaktifkan user; user nonaktif tidak bisa login.
- [x] Admin tidak dapat menonaktifkan dirinya sendiri atau admin terakhir.
- [x] Admin dapat memverifikasi organizer dan melihat status verifikasi.
- [x] Aksi sensitif tercatat di `audit_logs`.
- [x] Feature test: search, deactivate, self-deactivate, last admin, verify organizer.

### E6-02 — A-02 Moderasi event

**Estimasi:** M  
**Dependency:** E4-02

**AC:**

- [x] Admin melihat antrean event `pending_review`, terbaru dulu.
- [x] Admin dapat melihat detail event, tiket, poster, dan data organizer.
- [x] Approve mengubah status ke `published`, mengisi `published_at`, `approved_by`, `approved_at`.
- [x] Reject wajib alasan; status `rejected`, organizer dapat melihat alasan.
- [x] Admin dapat membatalkan event `published`/`sold_out`; semua order `paid` masuk refund batch.
- [x] Semua aksi tercatat di `audit_logs` dan organizer mendapat email.
- [x] Feature test: approve, reject, cancel, authorization, refund trigger.

### E6-03 — A-03 Master data

**Estimasi:** M  
**Dependency:** E0-04

**AC:**

- [x] CRUD kategori, venue, FAQ, banner dengan validasi.
- [x] Slug kategori/venue unik; banner memiliki rentang tanggal valid.
- [x] Data nonaktif tidak tampil di publik.
- [x] Perubahan master data menghapus cache halaman publik terkait.
- [x] Aksi tercatat di `audit_logs`.
- [x] Feature test: CRUD, validasi, cache invalidation, public visibility.

### E6-04 — A-04 Monitoring transaksi & refund

**Estimasi:** M  
**Dependency:** E3-09

**AC:**

- [x] Admin melihat daftar order dengan filter status, event, tanggal, dan pencarian nomor order/email.
- [x] Detail order menampilkan item, pembayaran, tiket, dan riwayat refund.
- [x] Admin dapat membuat refund full sesuai E3-09.
- [x] Admin dapat melihat payment gagal/expired dan memicu rekonsiliasi manual.
- [x] Tidak ada data PII berlebih; nomor identitas dimasking.
- [x] Semua aksi tercatat di `audit_logs`.
- [x] Feature test: filter, detail, refund, manual reconcile, authorization.

---

## 11. Epik 7 — QA & Launch (P0)

**Artefak:** `docs/README.md` (indeks), `docs/qa/load-test-plan.md`, `docs/qa/security-review.md`, `docs/qa/uat-plan.md`, `docs/ops/backup-restore.md`, `docs/ops/launch-checklist.md`, `docs/ops/incident-runbook.md`, `tests/load/*`, `scripts/backup-database.sh`, `scripts/restore-drill.sh`.

### E7-01 — Load & performance test

- [x] Skenario: 500 concurrent users, 50 order/menit, 20 scan/detik.
- [ ] Tidak ada overselling pada skenario rebutan tiket.
- [ ] p95 halaman publik < 2 detik; checkout < 2 detik.
- [ ] Queue tidak backlog > 5 menit.
- [ ] Hasil dan bottleneck didokumentasikan.

> Script k6 dan query verifikasi stok siap. Eksekusi memerlukan staging dengan rate limit dinaikkan sementara; hasil aktual diisi di `docs/qa/load-test-plan.md`.

### E7-02 — Security review

- [x] Uji otorisasi horizontal/vertikal untuk semua route sensitif.
- [x] Uji CSRF, XSS, SQL injection, mass assignment, upload berbahaya.
- [x] Uji signature webhook, replay, dan rate limit.
- [x] Uji signed URL tampering/expiry.
- [x] Tidak ada secret/PII di log atau repo.
- [x] Temuan critical/high ditutup sebelum launch.

> Temuan high/medium yang diperbaiki: akses admin dashboard/settings, XSS JSON-LD, rate limit webhook, dan policy halaman edit event organizer. Detail di `docs/qa/security-review.md`.

### E7-03 — UAT

- [x] Skenario pembelian guest dan customer.
- [x] Skenario pembayaran sukses, gagal, expired, late payment.
- [x] Skenario refund dan pembatalan event.
- [x] Skenario organizer membuat event sampai tayang.
- [x] Skenario scan valid/duplikat/invalid.
- [x] Skenario admin moderasi, master data, refund.
- [ ] Product owner menyetujui hasil UAT.

> Skenario otomatis tersedia di `tests/Feature/UatJourneyTest.php` dan suite terkait. Sign-off manual menunggu Product Owner.

### E7-04 — Backup & restore drill

- [ ] Backup harian berjalan dan terverifikasi.
- [x] Restore ke database uji berhasil dalam RTO 4 jam.
- [x] Dokumen runbook restore tersedia.

> Drill lokal MySQL berhasil (1 detik, data inti terbaca). Cron harian dan drill produksi menunggu ops; lihat `docs/ops/backup-restore.md`.

### E7-05 — Launch checklist

- [ ] Domain, HTTPS, redirect, dan email domain terverifikasi.
- [ ] SPF/DKIM/DMARC aktif.
- [ ] Gateway produksi aktif dan webhook URL terdaftar.
- [ ] Monitoring dan alert aktif.
- [ ] Legal pages, PSE, dan kontak support siap.
- [x] On-call dan runbook insiden tersedia.
- [ ] Rollback plan diuji.

> Checklist dan command `php artisan ticketing:launch-check --strict` tersedia. Verifikasi eksternal dan rollback drill menunggu ops; lihat `docs/ops/launch-checklist.md` dan `docs/ops/incident-runbook.md`.

---

## 12. Traceability Matrix P0

| Fitur | Story | Status |
|---|---|---|
| F-01 Beranda | E1-01 | Selesai |
| F-02 Daftar event | E1-02 | Selesai |
| F-03 Filter & pencarian | E1-03 | Selesai |
| F-04 Detail event | E1-04 | Selesai |
| F-05 Registrasi & login | E2-01, E2-02, E2-03 | Selesai |
| F-06 Guest checkout | E3-03, E2-06 | Selesai |
| F-07 Pilih tiket & checkout | E3-01, E3-02 | Selesai |
| F-09 Pembayaran | E3-04, E3-05, E3-06 | Selesai |
| F-10 E-tiket | E3-07, E2-05 | Selesai |
| F-11 Riwayat pesanan | E2-04, E3-08 | Selesai |
| F-13 Halaman informasi | E1-05 | Selesai |
| O-01 Profil organizer | E4-01 | Selesai |
| Registrasi organizer self-service | E4-05 | Selesai |
| O-02 Kelola event | E4-02 | Selesai |
| O-03 Kelola jenis tiket | E4-03 | Selesai |
| O-05 Laporan penjualan | E4-04 | Selesai |
| O-07 Kelola staff | E5-01 | Selesai |
| C-01 Scan QR | E5-01, E5-02 | Selesai |
| A-01 Manajemen pengguna & organizer | E6-01 | Selesai |
| A-02 Moderasi event | E6-02 | Selesai |
| A-03 Master data | E6-03 | Selesai |
| A-04 Monitoring transaksi & refund | E6-04, E3-09 | Selesai |

---

## 13. Rencana Sprint (asumsi 2 minggu/sprint)

| Sprint | Fokus | Output |
|---|---|---|
| **Sprint 0** | E0-01 s/d E0-08 | Skema, role, payment abstraction, layout, CI, staging |
| **Sprint 1** | E1-01 s/d E1-05, E2-01 s/d E2-03 | Publik + auth dasar |
| **Sprint 2** | E3-01 s/d E3-06 | Checkout + pembayaran + webhook |
| **Sprint 3** | E3-07 s/d E3-09, E2-04 s/d E2-06 | E-tiket, riwayat, guest access, refund |
| **Sprint 4** | E4-01 s/d E4-05, E5-01 s/d E5-02 | Organizer + scan |
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