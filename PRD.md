# PRD: Platform Tiket Event

| | |
|---|---|
| **Versi** | 1.27 |
| **Tanggal** | 13 November 2026 |
| **Status** | Draft untuk review |
| **Dokumen terkait** | `schema.sql`, `erd.mmd`, `BACKLOG.md` |
| **Referensi UX** | mortix.id (inspirasi alur dan tampilan, bukan acuan teknis) |

### Riwayat perubahan

| Versi | Perubahan |
|---|---|
| 1.0 | Draft awal |
| 1.1 | Diselaraskan dengan repo Laravel 12: stack, database MySQL, skema aktual, keputusan arsitektur, state machine, metrik, dan pemisahan skema aktual vs rencana perluasan |
| 1.2 | Tahap 0 dan 1 diimplementasikan: perluasan skema, role/policy, payment abstraction, CI, halaman publik event, filter, detail, dan halaman informasi |
| 1.3 | Tahap 2 diimplementasikan: registrasi/login/verifikasi email, login Google, lupa/reset password, akun, riwayat pesanan, tiket saya, dan akses guest via signed URL |
| 1.4 | Landing page `/` diubah dari katalog sewa alat menjadi beranda discovery event: hero + pencarian, kategori, event unggulan, event mendatang, cara beli, dan CTA akses guest |
| 1.5 | Tahap awal E3 diimplementasikan: pemilihan tiket, order pending dengan reservasi stok atomik, guest checkout, inisiasi pembayaran (FakeGateway), dan halaman status pembayaran; menambah `orders.idempotency_key` dan `orders.holder_names` |
| 1.6 | Webhook pembayaran idempoten, expiry order, rekonsiliasi pembayaran, dan late payment (termasuk refund otomatis + notifikasi) diimplementasikan |
| 1.7 | Penerbitan tiket idempoten setelah pembayaran, QR unik, dan pengiriman email e-tiket via queue dengan `notification_logs` |
| 1.8 | PDF e-tiket per order (event, tanggal, venue, jenis tiket, pemegang, kode, QR) tersedia untuk unduhan akun/guest/admin dan dilampirkan pada email; admin dapat melihat status email dan mengirim ulang; `notification_logs.order_id` ditambahkan |
| 1.9 | Panel refund admin: refund penuh order `paid` dengan alasan wajib, lifecycle `requested`/`processing`/`succeeded`/`failed`, retry refund gagal, pembatalan event dengan refund batch, notifikasi email, dan audit log; `refunds.error` ditambahkan |
| 1.10 | Polling status pembayaran setiap 5 detik (maksimal 15 menit) dengan endpoint JSON bertanda tangan, tombol cek status manual, dan tampilan status paid/expired |
| 1.11 | Dashboard organizer dan profil organizer: edit identitas, kontak, logo, rekening, slug unik dengan redirect 301 dari slug lama, verifikasi admin dengan catatan, audit log dengan masking rekening, serta halaman publik organizer; tabel `organizer_slug_redirects` ditambahkan |
| 1.12 | Kelola event organizer: draft dengan field wajib, upload poster/banner/galeri/lineup tervalidasi, edit/hapus draft, submit review dengan minimal satu tiket aktif, penguncian field penjualan setelah submit, edit non-kritis untuk event tayang, status event lengkap, serta moderasi approve/reject admin |
| 1.13 | Kelola jenis tiket: CRUD tipe tiket, validasi harga/kuota, batas total kuota terhadap kapasitas venue (peringatan + blokir submit), validasi jadwal jual dalam rentang event, larangan hapus tipe tiket yang sudah terjual, snapshot harga pada `order_items`, dan audit log perubahan harga/kuota |
| 1.14 | Laporan penjualan organizer: ringkasan tiket terjual, pendapatan order paid, refund terpisah, pendapatan bersih, grafik dan rincian harian per timezone event, filter tanggal/jenis tiket dengan default 30 hari terakhir, serta index `order_items(order_id, ticket_type_id)` untuk performa laporan 10.000 order |
| 1.15 | Penugasan staff event: admin dan organizer owner dapat menambah/menghapus staff beserta gate dari halaman kelola staff, role staff diberikan otomatis, audit log penugasan, halaman `/scan` dan `/scan/{event}` hanya untuk staff yang ditugaskan (atau admin), serta staff tidak dapat mengubah event/tiket; kolom `event_staff.gate` ditambahkan |
| 1.16 | Scan QR staff: kamera browser dengan fallback input manual, validasi server berdasarkan kode tiket/event/order paid/status tiket, update atomik `active` → `used`, `check_ins` untuk success/duplicate/invalid, tampilan hasil dan riwayat sesi, duplikat menampilkan waktu/gate scan pertama, serta rate limit 60 scan/menit |
| 1.17 | Manajemen pengguna & organizer admin: pencarian nama/email dengan filter role dan status, aktivasi/nonaktivasi akun dengan proteksi akun sendiri dan admin terakhir, pembatasan area user management hanya untuk admin, verifikasi organizer beserta statusnya, serta audit log untuk create/update/delete/aktivasi user dan verifikasi organizer |
| 1.18 | Moderasi event admin: antrean `pending_review` terbaru dulu, halaman detail event dengan poster/tiket/organizer, approve/reject dengan alasan wajib, pembatalan khusus event `published`/`sold_out` dengan refund batch untuk seluruh order lunas, email organizer untuk setiap keputusan, serta audit log untuk approve/reject/cancel |
| 1.19 | Master data admin: CRUD kategori, venue, FAQ, dan banner dengan validasi; slug kategori/venue unik (kolom `venues.slug` dan `venues.is_active` ditambahkan); data nonaktif disembunyikan dari halaman publik dan form organizer; cache master data publik (kategori, kota, FAQ, banner) diinvalidasi otomatis; setiap perubahan diaudit |
| 1.20 | Monitoring transaksi admin: daftar order dengan filter status/event/rentang tanggal dan pencarian nomor order/email, halaman detail berisi item/pembayaran/tiket/riwayat refund, refund penuh dari halaman detail, rekonsiliasi manual pembayaran pending/failed/expired, masking nomor identitas dan telepon, serta audit log untuk refund, rekonsiliasi, dan kirim ulang e-tiket |
| 1.21 | QA & launch: artefak load test k6 (public browse, checkout rush, scan) dan verifikasi stok; security review otomatis dengan perbaikan akses admin dashboard/settings, XSS JSON-LD, rate limit webhook, dan policy edit event; rate limit checkout/scan/webhook dapat dikonfigurasi; command `ticketing:launch-check`; skenario UAT end-to-end; script backup/restore drill; runbook backup, launch checklist, dan runbook insiden |
| 1.22 | Sinkronisasi dokumentasi QA/launch: audit dan penandaan AC Epik 0–3 berdasarkan bukti test/kode, koreksi status implementasi (E0-02, E0-04, E0-05, E0-07, E0-08), penyesuaian AC email guest (e-tiket setelah pembayaran), standarisasi label traceability, penambahan bukti drill backup lokal, serta indeks dokumen QA/ops. Tidak ada perubahan fitur |
| 1.23 | Perbaikan rollback migration legacy `rental_items` dan index `order_items` agar `migrate:rollback` lulus di MySQL/MariaDB dan SQLite; penambahan test webhook `failed` sebelum pembayaran, timeout + retry gateway, filter tanggal/kombinasi, dan urutan event unggulan. Suite menjadi 239 test / 1.540 assertion |
| 1.24 | Registrasi organizer self-service: halaman `/organizer/daftar` untuk customer terverifikasi, pembuatan organizer unverified + role `organizer`, verifikasi/penolakan admin dengan catatan wajib, status pengajuan di dashboard/profil, audit log, rate limit, dan unique index `organizers.owner_user_id`. Suite menjadi 253 test / 1.621 assertion |
| 1.25 | Panduan organizer: halaman publik `/panduan-organizer` berisi alur pendaftaran organizer dan pendaftaran event, section "Daftarkan event Anda di sini" di beranda, pengayaan halaman `/organizer/daftar`, checklist onboarding di dashboard organizer, dan FAQ organizer. Suite menjadi 258 test / 1.646 assertion |
| 1.26 | Konsistensi UI autentikasi: layout `x-layouts.auth` baru yang membungkus `x-layouts.public`, halaman login/register/lupa password/reset password/verifikasi email memakai desain, navigasi, dan footer yang sama dengan landing page, copy Bahasa Indonesia, serta toggle password yang aksesibel. Layout guest legacy dihapus. Suite menjadi 259 test / 1.652 assertion |
| 1.27 | Perbaikan CTA organizer untuk guest: tombol "Daftar sebagai Organizer" di beranda, panduan, dan footer langsung menuju halaman registrasi akun dengan konteks `intent=organizer`, bukan lagi melewati halaman login. Halaman registrasi menampilkan banner konteks organizer dan status setelah pendaftaran mengarahkan user untuk melanjutkan pengajuan profil. Suite menjadi 260 test / 1.661 assertion |

---

## 1. Ringkasan

Platform web untuk menjual tiket event dan konser musik secara online. Pengunjung dapat menjelajahi event, memilih jenis tiket, membayar, dan menerima e-tiket ber-QR. Organizer dapat membuat event, mengatur penjualan tiket, serta memantau penjualan dan kehadiran. Admin mengelola pengguna, moderasi event, master data, transaksi, dan refund.

Versi 1 dibangun sebagai **monolit Laravel 12** dengan **Blade + Bootstrap 5 + Tailwind 4** dan **MySQL/MariaDB**. Tahap 0 (fondasi dan skema), Tahap 1 (halaman publik event), serta Tahap 2 (auth, akun, dan akses guest) sudah diimplementasikan. Tahap 3 selesai: checkout, pembayaran, webhook, expiry, rekonsiliasi, late payment, penerbitan tiket ber-QR, PDF e-tiket, resend email admin, panel refund admin, dan polling status pembayaran sudah tersedia. Epik 4 selesai: registrasi organizer self-service, dashboard, profil organizer, pengelolaan event, jenis tiket, dan laporan penjualan organizer sudah tersedia. Epik 5 selesai: penugasan staff, gate, dan scan QR tiket dengan validasi server sudah tersedia. Epik 6 selesai: manajemen pengguna, status akun, verifikasi organizer, moderasi event, master data, serta monitoring transaksi dan refund sudah tersedia. Epik 7 selesai di sisi implementasi: artefak load test, security review beserta perbaikannya, skenario UAT otomatis, script backup/restore, launch-check, serta runbook operasional tersedia; eksekusi load test/UAT di staging, sign-off Product Owner, backup produksi, dan verifikasi domain/gateway/monitoring masih menunggu tim ops.

---

## 2. Latar Belakang & Masalah

- Penyelenggara event kecil dan menengah kesulitan menjual tiket online dengan pembayaran lokal yang mudah dan aman.
- Pembeli membutuhkan proses beli yang cepat, metode bayar lokal (QRIS, Virtual Account, e-wallet), dan tiket yang tidak mudah dipalsukan.
- Penjualan tiket rawan **overselling** dan antrean saat event populer dibuka.
- Pengecekan tiket di pintu masuk masih manual sehingga lambat dan rentan tiket ganda.
- Repo saat ini masih berupa template admin Laravel dengan modul konten legacy; fondasi ticketing baru tersedia di level model dan migration.

---

## 3. Tujuan & Metrik Keberhasilan

| Tujuan | Metrik | Definisi | Target 6 bulan |
|---|---|---|---|
| Pembelian mudah | Konversi halaman event → order terbayar | Order `paid` / unique visitor halaman detail event, attribution 30 hari | ≥ 3% |
| Checkout cepat | Waktu pilih tiket → pembayaran terkonfirmasi | Median, dari klik "Beli" sampai status `paid` | ≤ 3 menit |
| Tidak ada overselling | Tiket terjual melebihi kuota | Hard constraint, dihitung per `ticket_type` | 0 |
| Pembayaran andal | Tiket terbit setelah pembayaran sukses | Order `paid` yang tiketnya terbit ≤ 2 menit / total order `paid` | ≥ 99,5% |
| Check-in lancar | Waktu scan per tiket | p95, dari QR terbaca sampai hasil tampil | ≤ 3 detik |
| Adopsi organizer | Organizer aktif | Organizer dengan ≥ 1 event berbayar dalam 6 bulan | ≥ 10 (proposed) |
| Ketersediaan | Uptime bulanan | Diukur dari health check eksternal | ≥ 99,5% |

Metrik wajib memiliki baseline sebelum MVP diluncurkan. Jika belum ada analytics, baseline diambil dari 2 minggu pertama setelah rilis dan target ditinjau ulang.

**Non-goals versi 1:** pemilihan kursi (seat map), marketplace jual-beli ulang tiket, aplikasi mobile native, program loyalitas, multi-event cart, split payment, dan affiliate.

---

## 4. Pengguna & Peran

| Persona | Deskripsi | Kebutuhan utama |
|---|---|---|
| **Pengunjung / Pembeli** | Mencari dan membeli tiket, termasuk guest | Cari event, bayar mudah, tiket langsung diterima |
| **Organizer** | Promotor atau penyelenggara event | Kelola event, tiket, laporan penjualan, verifikasi |
| **Staff Lapangan** | Petugas scan di pintu masuk | Scan QR cepat, tahu tiket valid/duplikat |
| **Admin Platform** | Tim internal | Moderasi event, kelola pengguna, transaksi, refund, master data |

### 4.1 Model peran

Satu pengguna dapat memiliki lebih dari satu peran. Peran **tidak** disimpan sebagai satu kolom tunggal di `users`.

| Peran | Sumber | Hak akses utama |
|---|---|---|
| Guest | Tanpa login | Lihat event, checkout guest, akses tiket via signed link |
| Customer | User terdaftar | Semua hak guest + riwayat pesanan + tiket saya |
| Organizer Owner | `organizers.owner_user_id` | Kelola event miliknya, tiket, laporan |
| Field Staff | `event_staff` + role `staff` | Scan tiket untuk event yang ditugaskan, dengan gate per penugasan |
| Admin | role `admin` | Moderasi, pengguna, master data, transaksi, refund |

Role disimpan di tabel `roles` dan pivot `role_user` (lihat §10 dan `BACKLOG.md` Epik 0). Otorisasi memakai Laravel Policies/Gates, bukan pengecekan role di view.

---

## 5. Lingkup Fitur

Prioritas: **P0** = wajib MVP, **P1** = setelah MVP, **P2** = opsional.

### 5.1 Sisi Pembeli (Publik)

| ID | Fitur | Deskripsi | Prioritas |
|---|---|---|---|
| F-01 | Beranda event (`/events`) | Banner aktif, event unggulan, daftar kategori | P0 |
| F-02 | Daftar event (`/events`) | Kartu event: poster, judul, tanggal, lokasi, harga mulai | P0 |
| F-03 | Filter & pencarian | Kategori, kota, tanggal, rentang harga, kata kunci judul | P0 |
| F-04 | Detail event (`/events/{slug}`) | Deskripsi, lineup, galeri, lokasi/peta, S&K, jenis tiket & ketersediaan | P0 |
| F-05 | Registrasi & login | Email + password, login Google, verifikasi email, lupa password | P0 |
| F-06 | Guest checkout | Beli tanpa akun dengan nama, email, nomor HP | P0 |
| F-07 | Pilih tiket & checkout | Pilih jenis/jumlah tiket, data pembeli, data pemegang tiket bila wajib | P0 |
| F-08 | Voucher | Input kode promo, validasi, potongan harga | P1 |
| F-09 | Pembayaran | QRIS, Virtual Account, e-wallet; batas waktu bayar; webhook | P0 |
| F-10 | E-tiket | QR unik, email, halaman "Tiket Saya", unduh PDF | P0 |
| F-11 | Riwayat pesanan | Daftar order, status, detail pembayaran, akses guest | P0 |
| F-12 | Wishlist | Simpan event favorit | P2 |
| F-13 | Halaman informasi | FAQ, Tentang Kami, Kebijakan Privasi, S&K, Kebijakan Refund | P0 |
| F-14 | Hubungi kami | Formulir kontak tersimpan di database | P1 |
| F-15 | Notifikasi | Email konfirmasi, pengingat event; WhatsApp opsional | P1 |

### 5.2 Dashboard Organizer

| ID | Fitur | Deskripsi | Prioritas |
|---|---|---|---|
| O-01 | Profil organizer | Data penyelenggara, logo, kontak, rekening pencairan | P0 |
| O-02 | Kelola event | Buat/ubah draft, upload poster/banner/galeri/lineup, submit review | P0 |
| O-03 | Kelola jenis tiket | Nama, harga, kuota, jadwal jual, min/max per order | P0 |
| O-04 | Kelola voucher | Kode promo per event | P1 |
| O-05 | Laporan penjualan | Tiket terjual, pendapatan, grafik harian, per jenis tiket | P0 |
| O-06 | Daftar pembeli | Order dan pemegang tiket; ekspor CSV | P1 |
| O-07 | Kelola staff | Tunjuk petugas scan per event | P1 |
| O-08 | Pencairan dana | Pengajuan dan riwayat pencairan | P1 |

### 5.3 Check-in (Staff)

| ID | Fitur | Deskripsi | Prioritas |
|---|---|---|---|
| C-01 | Scan QR | Kamera browser; hasil valid, duplikat, tidak valid, event salah | P0 |
| C-02 | Cari manual | Cari tiket berdasarkan kode/nama bila QR rusak | P1 |
| C-03 | Statistik kehadiran | Jumlah check-in per event | P1 |

### 5.4 Panel Admin

| ID | Fitur | Deskripsi | Prioritas |
|---|---|---|---|
| A-01 | Manajemen pengguna & organizer | Verifikasi organizer, nonaktifkan akun, penugasan staff event minimal | P0 |
| A-02 | Moderasi event | Setujui/tolak event sebelum tayang, batalkan event | P0 |
| A-03 | Master data | Kategori, venue, FAQ, banner | P0 |
| A-04 | Monitoring transaksi | Lihat order/pembayaran dengan filter, detail order, refund penuh, dan rekonsiliasi manual | P0 |
| A-05 | Pesan kontak | Lihat dan tanggapi pesan | P1 |
| A-06 | Audit log | Riwayat aksi sensitif | P1 |

### 5.5 Definisi P0

P0 mencakup seluruh fitur berlabel P0 di atas **dan** Epik 0 di `BACKLOG.md`:

- perluasan skema (roles, moderasi event, refunds, webhook events, notification logs, index);
- otorisasi berbasis policy;
- abstraksi payment gateway;
- layout publik, organizer, staff, dan admin;
- observability dasar dan CI.

Tanpa Epik 0, fitur P0 tidak dapat diterima.

---

## 6. Alur Utama

### 6.1 Pembelian Tiket

1. Pembeli membuka detail event dan memilih jenis serta jumlah tiket.
2. Sistem memvalidasi jadwal jual, kuota tersisa, min/max per order, batas per identitas, dan status event.
3. Pembeli mengisi data pembeli. Guest mengisi nama, email, dan nomor HP. Jika event mewajibkan identitas, pembeli mengisi nama (dan nomor identitas bila diaktifkan) untuk setiap tiket.
4. Sistem membuat **order `pending`**, mengurangi stok secara atomik, dan menetapkan `expires_at` (default 15 menit).
5. Pembeli diarahkan ke pembayaran di payment gateway.
6. Gateway mengirim **webhook**. Jika sukses dan signature valid, order menjadi `paid`, tiket diterbitkan, dan email dikirim.
7. Jika waktu habis tanpa pembayaran, order menjadi `expired` dan stok dikembalikan.
8. Jika pembayaran sukses datang **setelah** order expired:
   - jika stok masih tersedia, order diaktifkan kembali dan tiket diterbitkan;
   - jika stok tidak tersedia, order masuk proses refund otomatis dan pembeli diberi notifikasi.

### 6.2 Check-in

1. Staff membuka `/scan/{event}` untuk event yang ditugaskan.
2. Staff memindai QR. Server mencocokkan `ticket_code`, memeriksa event, status tiket, dan status order.
3. Jika tiket `active`, sistem melakukan update atomik `status = used` dengan syarat `status = active`, lalu mencatat `check_ins` dengan `result = success`.
4. Jika tiket sudah `used`, sistem menampilkan peringatan duplikat beserta waktu dan gate scan pertama; `check_ins` dicatat dengan `result = duplicate`.
5. Jika tiket tidak ditemukan, bukan milik event tersebut, `cancelled`, atau `refunded`, sistem menampilkan `invalid` dan mencatat percobaan.
6. Scan ganda bersamaan hanya boleh menghasilkan satu `success`; sisanya `duplicate`.

### 6.3 Siklus Status Event

| Dari | Ke | Aktor | Catatan |
|---|---|---|---|
| `draft` | `pending_review` | Organizer | Semua field wajib dan minimal 1 jenis tiket harus valid |
| `pending_review` | `published` | Admin | Event tayang |
| `pending_review` | `rejected` | Admin | Wajib alasan |
| `rejected` | `draft` | Organizer | Perbaiki lalu submit ulang |
| `published` | `sold_out` | Sistem | Turunan saat seluruh tiket aktif habis |
| `sold_out` | `published` | Sistem | Jika kuota ditambah |
| `published` / `sold_out` | `ended` | Sistem | Setelah `end_at` |
| `published` / `sold_out` | `cancelled` | Admin | Wajib alasan; seluruh order lunas masuk refund batch |
| `published` | `postponed` | Admin | P2, belum masuk v1 |

`sold_out` adalah status turunan, bukan aksi manual. Perubahan status dicatat di `audit_logs`.

### 6.4 State Machine Order & Payment

| Status order | Arti | Transisi berikutnya |
|---|---|---|
| `pending` | Menunggu pembayaran | `paid`, `expired`, `cancelled` |
| `paid` | Pembayaran terkonfirmasi | `refunded` |
| `expired` | Melewati batas bayar | `paid` (late payment, jika stok tersedia) atau tetap `expired` |
| `cancelled` | Dibatalkan sebelum bayar | — |
| `refunded` | Dana dikembalikan | — |

| Status payment | Arti |
|---|---|
| `pending` | Menunggu pembayaran |
| `success` | Gateway mengonfirmasi sukses |
| `failed` | Gagal |
| `expired` | Melewati batas waktu gateway |
| `refunded` | Dana dikembalikan |

Aturan:

- Hanya webhook bertanda tangan valid yang boleh mengubah order menjadi `paid`.
- Redirect browser tidak pernah dianggap sebagai bukti pembayaran.
- Setiap webhook disimpan di `webhook_events` dengan unique key `(gateway, external_id)` untuk idempotensi.
- Perubahan status order dan penerbitan tiket harus idempoten.

### 6.5 Refund

1. Refund dapat berasal dari pembatalan event (massal) atau permintaan pembeli yang disetujui organizer/admin.
2. Admin membuat record `refunds` dengan alasan dan nominal.
3. Sistem memanggil refund API gateway (atau menandai refund manual jika gateway tidak mendukung).
4. Status refund: `requested` → `processing` → `succeeded` / `failed`.
5. Saat refund sukses: order menjadi `refunded`, tiket terkait menjadi `refunded`, dan email dikirim.
6. Refund harus idempoten dan tercatat di `audit_logs`.

### 6.6 Akses Guest

1. Guest menerima email berisi link bertanda tangan (signed URL) ke halaman pesanan dan tiket.
2. Link berlaku sampai 30 hari setelah event berakhir.
3. Guest dapat mencari pesanan dengan `order_number` + email pembeli; sistem mengirim link baru ke email tersebut.
4. Guest yang mendaftar dengan email yang sama dan terverifikasi dapat mengklaim order lamanya.

---

## 7. Aturan Bisnis

1. **Stok tidak boleh minus.** Pengurangan stok dilakukan dengan query atomik di dalam transaksi: `UPDATE ticket_types SET sold_count = sold_count + ? WHERE id = ? AND sold_count + ? <= quota`.
2. **Snapshot harga dan nama tipe tiket** disimpan di `order_items` agar perubahan harga/nama tidak memengaruhi order lama.
3. **Tiket hanya terbit setelah webhook sukses** yang tervalidasi signature. Bukan dari redirect, polling, atau aksi client.
4. **Order pending kedaluwarsa** dan stok dikembalikan dalam job terjadwal. Pengembalian stok harus idempoten.
5. **Late payment** mengikuti §6.1 langkah 8.
6. **Kode QR** adalah string acak ≥ 128 bit, tidak berurutan, dan tidak mengandung data pribadi. Validasi selalu di server.
7. **Voucher (P1)** dibatasi periode, kuota total, batas per pengguna, dan minimum pembelian. Untuk guest, batas per pengguna dihitung dari email/phone ternormalisasi.
8. **Pembatalan event** membuat semua order `paid` masuk antrean refund dan semua tiket menjadi `cancelled`.
9. **Total bayar** = subtotal − diskon + biaya layanan + biaya metode bayar + pajak. Semua nilai Rupiah bilangan bulat, tidak boleh negatif.
10. **Pajak**: harga tiket dianggap sudah termasuk PPN; biaya layanan dikenakan PPN sesuai konfigurasi. Perlakuan final dikonfirmasi ke konsultan pajak.
11. **Event yang mewajibkan identitas** harus mengumpulkan nama pemegang untuk setiap tiket.
12. **Moderasi event**: organizer tidak dapat mempublikasikan event tanpa persetujuan admin.
13. **Batas pembelian** berlaku per order dan per identitas (user/email/phone) untuk order `pending` + `paid`.
14. **Check-in atomik**: satu tiket hanya boleh sukses sekali. Percobaan berikutnya dicatat sebagai `duplicate`, bukan tiket baru.
15. **Audit log** wajib untuk aksi sensitif: verifikasi organizer, moderasi event, refund, perubahan harga/kuota, penonaktifan akun, dan perubahan status pembayaran manual.
16. **Data pribadi** (email, phone, nomor identitas) tidak boleh muncul di log aplikasi. Nomor identitas dimasking di UI admin.
17. **Email e-tiket** dikirim lewat queue dan memiliki retry. Kegagalan permanen harus terlihat di dashboard admin/log.
18. **Satu order hanya untuk satu event.** Multi-event cart bukan bagian v1.

---

## 8. Kebutuhan Non-Fungsional

| Area | Kebutuhan |
|---|---|
| **Performa** | LCP halaman daftar/detail event < 2 detik (p75, mobile 4G); query baca p95 < 300 ms; checkout p95 < 2 detik; kapasitas awal 500 concurrent users dan burst 50 order/menit; load test minimal 2× perkiraan puncak |
| **Keamanan** | OWASP ASVS Level 1; password Argon2id/bcrypt; CSRF, XSS, SQL injection protection; rate limit login 5/menit, scan 60/menit, webhook 120/menit; validasi signature webhook; admin 2FA; policy per route; validasi upload; secret di env; data kartu tidak disimpan |
| **Privasi** | Patuh UU PDP; consent saat checkout; data minimization; retensi order/tiket 5 tahun, pesan kontak 1 tahun, log aplikasi 90 hari; masking nomor identitas; notifikasi kebocoran ≤ 3×24 jam; tunjuk PIC data protection |
| **Ketersediaan** | Uptime 99,5%; RPO 24 jam; RTO 4 jam; backup harian; uji restore bulanan; health check eksternal |
| **Responsif** | Mobile-first; target utama 360–430 px; tabel admin boleh scroll horizontal; browser modern 2 versi terakhir |
| **SEO** | URL slug; meta title/description; Open Graph; structured data `Event`; sitemap; canonical; halaman 404 yang benar |
| **Aksesibilitas** | WCAG 2.1 AA untuk halaman publik dan checkout: kontras, label form, fokus keyboard, alt text |
| **Observabilitas** | Structured logging; error tracking (Sentry); metrik order/payment/webhook; alert webhook gagal > 5 menit, queue backlog > 5 menit, error rate 5xx > 1%, dan payment success rate turun |
| **Kepatuhan** | Pendaftaran PSE (Komdigi); S&K, Kebijakan Privasi, Kebijakan Refund; faktur/pajak sesuai kewajiban; konsultasi legal sebelum rilis |
| **Bahasa & waktu** | Bahasa Indonesia; simpan UTC; setiap event punya timezone (Asia/Jakarta, Asia/Makassar, Asia/Jayapura); tampilkan sesuai timezone event |
| **Email** | SPF, DKIM, DMARC; provider transaksional; bounce/complaint handling; retry 3× dengan backoff |
| **Operasional** | Staging environment; CI (Pint, Pest, build); queue worker + scheduler; runbook insiden; rollback migration |

---

## 9. Arsitektur & Teknologi

### 9.1 Stack aktual dan target

| Lapisan | Aktual di repo | Target produksi | Catatan |
|---|---|---|---|
| Framework | Laravel 12, PHP 8.2+ | Sama | Monolit; tidak memakai Next.js |
| UI | Blade, Bootstrap 5, Tailwind 4, Vite | Sama | Server-rendered; SEO baik |
| Database | MySQL/MariaDB | MySQL 8 / MariaDB 10.6+ | Bukan PostgreSQL |
| ORM | Eloquent | Sama | Query atomik memakai query builder di dalam transaksi |
| Queue | `database` | Redis + Laravel Horizon | Dev boleh database |
| Cache/session | `database` | Redis | Session tetap database bila lebih sederhana |
| Storage | Local public disk | S3-compatible | Poster, banner, galeri |
| Pembayaran | Belum ada | Midtrans utama, abstraksi gateway | Xendit dapat ditambahkan tanpa ubah domain |
| Email | Laravel Mail | Provider transaksional (Resend/SES) | Diputuskan sebelum MVP |
| Auth | Controller auth kustom + Socialite (Google), verifikasi email, reset password, rate limit | Sama + kebijakan password produksi | Login Google memakai `laravel/socialite`; QR tiket dirender inline dengan `bacon/bacon-qr-code` |
| Hosting | Lokal | VPS + Docker/Nginx + PHP-FPM | Worker dan scheduler terpisah |
| CI/CD | GitHub Actions (Pint, Pest, build) | GitHub Actions + deploy staging | Deploy staging belum ada |
| Observability | Job + scheduler; log dasar | Sentry + log + uptime monitor | Sentry/alert wajib sebelum launch |

### 9.2 Kondisi repo saat ini

- Repo berisi template admin Laravel dan modul konten legacy: `services`, `rental_items`, `collaborations`, `testimonials`, `site_settings`, `social_links`, `team_members`.
- Model dan migration ticketing sudah ada: `Event`, `Organizer`, `Venue`, `TicketType`, `Voucher`, `Order`, `OrderItem`, `Payment`, `Ticket`, `CheckIn`, `VoucherUsage`, `Banner`, `Faq`, `ContactMessage`, `AuditLog`.
- Halaman publik event sudah tersedia: `/events`, `/events/{slug}`, filter, dan halaman informasi.
- Auth, akun, dan akses guest sudah tersedia: `/login`, `/register`, `/forgot-password`, `/reset-password`, `/auth/google/*`, `/email/verify*`, `/akun`, `/akun/pesanan`, `/akun/tiket`, `/pesanan/cari`, dan signed URL `/pesanan/{order}`.
- Checkout dan pembayaran sudah tersedia: form pilih tiket, order pending dengan reservasi stok, guest checkout, inisiasi pembayaran, halaman status dengan polling otomatis, webhook idempoten, expiry otomatis, rekonsiliasi, late payment, penerbitan tiket ber-QR, PDF e-tiket, resend email, serta panel refund admin (refund penuh, retry, dan pembatalan event dengan refund batch). Epik 4 selesai: dashboard, profil, pengelolaan event, jenis tiket, dan laporan penjualan organizer sudah tersedia. Epik 5 selesai: penugasan staff, gate, dan scan QR tiket dengan validasi server sudah tersedia. Epik 6 selesai: manajemen pengguna, status akun, verifikasi organizer, moderasi event, master data, serta monitoring transaksi dan refund sudah tersedia. Epik 7 selesai di sisi implementasi: artefak load test, security review beserta perbaikannya, skenario UAT otomatis, script backup/restore, launch-check, serta runbook operasional tersedia; eksekusi load test/UAT di staging, sign-off Product Owner, backup produksi, dan verifikasi domain/gateway/monitoring masih menunggu tim ops.
- Skema aktual ada di `schema.sql`; ERD domain ada di `erd.mmd`.
- Perluasan skema P0 sudah diterapkan dan didokumentasikan di §10.3 serta `BACKLOG.md` Epik 0.

### 9.3 Aturan arsitektur

- Controller tipis; validasi di Form Request; otorisasi di Policy; logika bisnis di Service/Action.
- Job untuk pekerjaan lambat: kirim email, expire order, rekonsiliasi pembayaran, refund massal.
- Event/Listener Laravel untuk efek samping (mis. `OrderPaid` → terbitkan tiket → kirim email).
- Tidak ada `env()` di luar config; tidak ada query mentah kecuali operasi atomik stok/check-in.
- Semua route memakai named route dan middleware auth/role/policy.
- Payment gateway dibungkus interface: `createCharge`, `handleWebhook`, `refund`, `getStatus`.
- Dependency produksi yang dipakai: `laravel/socialite` untuk login Google dan `bacon/bacon-qr-code` untuk QR SVG. Paket PDF e-tiket dipilih saat Epik 3.

---

## 10. Model Data

### 10.1 Skema aktual

Database aktual memiliki **41 tabel** (di luar tabel `migrations`): 26 tabel domain ticketing, 8 tabel konten legacy, dan 7 tabel infrastruktur Laravel. Detail lengkap ada di `schema.sql`; relasi domain ada di `erd.mmd`.

| Kelompok | Tabel |
|---|---|
| Pengguna & organizer | `users`, `organizers` |
| Master | `categories`, `venues` |
| Event | `events`, `event_images`, `event_lineups` |
| Tiket & promo | `ticket_types`, `vouchers`, `voucher_usages` |
| Transaksi | `orders`, `order_items`, `payments` |
| Tiket terbit & check-in | `tickets`, `check_ins`, `event_staff` |
| Role & otorisasi | `roles`, `role_user` |
| Refund & integrasi | `refunds`, `webhook_events`, `notification_logs` |
| Konten & lain-lain | `faqs`, `contact_messages`, `banners`, `wishlists`, `audit_logs` |
| Konten legacy | `services`, `rental_items`, `collaborations`, `collaboration_photos`, `testimonials`, `site_settings`, `social_links`, `team_members` |
| Infrastruktur | `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens` |

### 10.2 Konvensi

- `users.id` = `BIGINT UNSIGNED`; entitas domain lain memakai `CHAR(36)` UUID.
- Uang = `BIGINT UNSIGNED` Rupiah, tanpa desimal.
- Waktu = `TIMESTAMP`, disimpan UTC; timezone event disimpan terpisah.
- Status = string yang divalidasi enum di aplikasi, bukan enum database.
- Tidak ada soft delete untuk order/tiket; pembatalan memakai status.
- Satu order hanya untuk satu event.

### 10.3 Perluasan skema P0 yang sudah diterapkan

Perluasan berikut **sudah ada** di migration, `schema.sql`, dan `erd.mmd`:

| Area | Perubahan |
|---|---|
| Pengguna & role | `users.phone`, `users.google_id` (unique nullable), `users.is_active`, `users.last_login_at`; tabel `roles`, `role_user` |
| Organizer | `logo_url`, `contact_email`, `contact_phone`, `bank_name`, `bank_account_no`, `bank_account_name`, `verified_at`, `verified_by`, `verification_note`; tabel `organizer_slug_redirects` untuk redirect 301 slug lama |
| Venue | `slug`, `latitude`, `longitude`, `maps_url`, `is_active` |
| Event | `short_desc`, `terms`, `poster_url`, `banner_url`, `doors_open_at`, `online_url`, `timezone`, `is_featured`, `require_identity`, `view_count`, `published_at`, `approved_by`, `approved_at`, `rejection_reason` |
| Ticket type | `description`, `min_per_order`, `max_per_order`, `sale_start_at`, `sale_end_at` |
| Voucher (P1) | `max_discount`, `min_purchase`, `usage_limit`, `per_user_limit`, `used_count`, `valid_from`, `valid_until` |
| Payment | `expires_at` |
| Refund | Tabel `refunds`: order, payment, amount, reason, status, gateway_ref, error, requested_by, processed_by, processed_at |
| Webhook | Tabel `webhook_events`: gateway, external_id, event_type, payload, signature_valid, status, processed_at, error; unique `(gateway, external_id)` |
| Notifikasi | Tabel `notification_logs`: channel, recipient, template, status, provider_ref, error, sent_at |
| Index | `orders(status, expires_at)`, `events(status, start_at)`, `ticket_types(event_id, is_active)`, `tickets(event_id, status)`, `tickets(order_id)`, `check_ins(event_id, scanned_at)`, `check_ins(ticket_id)`, `payments(order_id, status)`, `voucher_usages(voucher_id, user_id)`, `refunds(status)` |
| P1 | Tabel `payouts` untuk pencairan organizer |

Setiap perubahan skema berikutnya wajib memperbarui `schema.sql` dan `erd.mmd` pada PR yang sama.

---

## 11. Peta Halaman

| Rute | Halaman | Akses |
|---|---|---|
| `/` | Beranda discovery event (hero, pencarian, kategori, event unggulan) | Publik |
| `/events` | Beranda event, daftar + filter | Publik |
| `/events/{slug}` | Detail event | Publik |
| `/events/{slug}/checkout` | Form pilih tiket & data pembeli | Publik |
| `/checkout/{order}` | Status & instruksi pembayaran | Pemilik/signed URL |
| `/faq`, `/tentang-kami`, `/hubungi-kami`, `/privacy-policy`, `/terms`, `/refund-policy` | Informasi | Publik |
| `/pesanan/cari` | Cari pesanan guest | Publik |
| `/pesanan/{order}` | Detail pesanan guest (signed URL) | Signed URL |
| `/pesanan/{order}/pdf` | Unduh PDF e-tiket guest (signed URL) | Signed URL |
| `/login`, `/register` | Autentikasi | Publik |
| `/forgot-password`, `/reset-password/{token}` | Lupa/reset password | Publik |
| `/email/verify`, `/email/verify/{id}/{hash}` | Verifikasi email | Auth |
| `/checkout/{order}/pay` | Inisiasi pembayaran | Pemilik/signed |
| `/webhooks/payment/{gateway}` | Webhook gateway pembayaran | Gateway (signature) |
| `/checkout/{order}/status` | Polling status pembayaran | Pemilik/signed |
| `/akun/tiket` | Tiket saya | Customer |
| `/akun/pesanan` | Riwayat pesanan | Customer |
| `/akun/pesanan/{order}` | Detail pesanan | Customer |
| `/akun/pesanan/{order}/pdf` | Unduh PDF e-tiket pesanan | Customer |
| `/akun/tiket/{ticket}/pdf` | Unduh PDF e-tiket | Customer |
| `/organizer` | Dashboard organizer | Organizer |
| `/organizer/daftar` | Ajukan diri sebagai organizer (email terverifikasi) | Customer |
| `/panduan-organizer` | Panduan mendaftar organizer dan event | Publik |
| `/organizer/profil` | Profil & rekening | Organizer |
| `/organizers/{slug}` | Halaman publik organizer | Publik |
| `/organizer/events` | Daftar event | Organizer |
| `/organizer/events/create` | Buat event | Organizer |
| `/organizer/events/{event}/edit` | Edit event | Organizer (owner) |
| `/organizer/events/{event}/submit` | Ajukan review event | Organizer (owner) |
| `/organizer/events/{event}/tickets` | Kelola jenis tiket | Organizer (owner) |
| `/organizer/events/{event}/laporan` | Laporan penjualan | Organizer (owner) |
| `/organizer/events/{event}/staff` | Kelola staff event | Organizer (owner) |
| `/scan` | Daftar event untuk discan | Staff, admin |
| `/scan/{event}` | Scan QR (kamera/input manual) | Staff yang ditugaskan, admin |
| `/admin` | Dashboard admin | Admin |
| `/admin/users`, `/admin/users/{user}/status`, `/admin/organizers` | Pengguna, status akun, & organizer | Admin |
| `/admin/events`, `/admin/events/{event}`, `/admin/events/{event}/approve`, `/admin/events/{event}/reject`, `/admin/events/cancel` | Moderasi event | Admin |
| `/admin/events/{event}/staff` | Kelola staff event | Admin |
| `/admin/categories`, `/admin/venues`, `/admin/faqs`, `/admin/banners` | Master data | Admin |
| `/admin/orders`, `/admin/payments` | Monitoring transaksi | Admin |
| `/admin/pesanan`, `/admin/pesanan/{order}`, `/admin/pesanan/{order}/pdf` | Monitoring pesanan, detail order, & unduh e-tiket | Admin |
| `/admin/pesanan/{order}/kirim-tiket` | Kirim ulang email e-tiket | Admin |
| `/admin/pesanan/{order}/pembayaran/{payment}/rekonsiliasi` | Rekonsiliasi manual pembayaran | Admin |
| `/admin/refunds` | Refund | Admin |
| `/admin/payouts` | Pencairan | Admin (P1) |
| `/admin/contact-messages`, `/admin/audit-logs` | Kontak & audit | Admin (P1) |
| `404`, `419`, `429`, `500` | Error | — |

---

## 12. Rencana Rilis

| Fase | Cakupan | Perkiraan |
|---|---|---|
| **Fase 0** | Epik 0: perluasan skema, role/policy, abstraksi payment, layout, CI/staging, observability, legal docs, sandbox gateway, proses PSE | 1–2 minggu |
| **MVP** | Semua P0: publik, auth, guest checkout, checkout, pembayaran, e-tiket, riwayat, organizer dasar, scan QR, admin dasar, refund | 8–10 minggu setelah Fase 0 |
| **Fase 2** | Voucher, laporan lanjutan, ekspor CSV, staff management, cari manual, statistik check-in, kontak, audit log, WhatsApp, pencairan | 4–6 minggu |
| **Fase 3** | Wishlist, rekomendasi, seat map (jika tervalidasi), blog | Menyusul |

Asumsi tim: 2 backend Laravel, 1 frontend/design, QA part-time. Jika solo, estimasi MVP 2× lipat. Estimasi belum termasuk lead time kontrak payment gateway, verifikasi bisnis, dan pendaftaran PSE.

**Scope guard MVP:** tidak ada voucher, seat map, WhatsApp, payout, wishlist, atau multi-event cart. Fitur tersebut tidak boleh menyusup ke sprint MVP tanpa perubahan scope resmi.

---

## 13. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Lonjakan trafik saat penjualan dibuka | Situs lambat/down, overselling | Update stok atomik, rate limit, cache, load test 2× puncak |
| Webhook terlambat/gagal | Pembeli bayar tetapi tiket belum terbit | Idempotensi `webhook_events`, job rekonsiliasi, tombol cek status, alert |
| Pembayaran sukses setelah order expired | Stok sudah dilepas | Kebijakan late payment §6.1; auto-refund bila stok habis |
| Tiket palsu/digandakan | Kerugian organizer | QR acak 128-bit, validasi server, update atomik, audit scan |
| Pembatalan event | Refund massal | Tabel `refunds`, job batch, status jelas, komunikasi email |
| Kepatuhan PSE/UU PDP | Sanksi/pemblokiran | Pendaftaran PSE, legal review, kebijakan privasi/refund, DPO |
| Organizer palsu | Kerugian pembeli, reputasi | Verifikasi admin, moderasi event, penahanan dana (P1) |
| Schema drift | Dokumen dan kode tidak sinkron | `schema.sql`/ERD diperbarui di PR yang sama; CI check migration |
| Guest link bocor | Akses tiket orang lain | Signed URL berbatas waktu, rate limit lookup, email verifikasi |
| Email masuk spam | Tiket tidak diterima | SPF/DKIM/DMARC, provider transaksional, retry, resend |
| Queue backlog | Email/expiry terlambat | Horizon monitoring, alert backlog, worker terpisah |
| Lead time KYB payment gateway | MVP mundur | Mulai pendaftaran di Fase 0, siapkan fallback manual |

---

## 14. Keputusan & Pertanyaan Terbuka

### 14.1 Keputusan yang diusulkan (default MVP)

| ID | Keputusan |
|---|---|
| D1 | Monolit Laravel 12 + Blade + MySQL/MariaDB. Tidak memakai Next.js/Prisma/BullMQ di v1 |
| D2 | Midtrans sebagai gateway utama; platform menjadi merchant of record; Xendit dapat ditambahkan lewat abstraksi |
| D3 | Batas bayar default 15 menit dan disinkronkan dengan expiry gateway |
| D4 | Guest mengakses tiket lewat signed URL + email; lookup order dengan order number + email |
| D5 | Organizer mendaftar self-service, tetapi wajib diverifikasi admin sebelum event dapat tayang |
| D6 | Event harus melalui `pending_review` dan disetujui admin sebelum `published` |
| D7 | Refund default: event cancelled = 100%; permintaan pembeli maksimal H-7 dan harus disetujui; biaya platform/payment non-refundable kecuali event cancelled. Di P0 refund diproses admin; form pengajuan mandiri pembeli masuk P1 |
| D8 | WhatsApp tidak wajib di MVP; email adalah kanal utama |
| D9 | Seat map bukan bagian v1; dievaluasi di Fase 3 |
| D10 | Biaya layanan berupa persentase dengan batas minimum/maksimum, nilainya di config (bukan hardcode) |
| D11 | Harga tiket dianggap termasuk PPN; biaya layanan dikenakan PPN; konfirmasi ke konsultan pajak |
| D12 | Email provider dipilih sebelum MVP; wajib SPF/DKIM/DMARC |
| D13 | `/` menjadi beranda discovery event; katalog sewa alat tetap tersedia melalui `/layanan/{service:slug}`. Navigasi dan test lama diperbarui mengikuti keputusan ini |

### 14.2 Pertanyaan yang masih perlu konfirmasi bisnis

1. Nominal/biaya layanan final dan siapa yang menanggung biaya payment gateway.
2. Persetujuan legal atas kebijakan refund D7.
3. Target jumlah organizer aktif 6 bulan pertama.
4. Pilihan final payment gateway setelah kontrak dan verifikasi bisnis.
5. Pilihan provider email dan hosting produksi.

Pertanyaan ini tidak boleh menahan Epik 0; semuanya dapat dikonfigurasi dan diputuskan sebelum cut-off MVP.

---

## 15. Acceptance Criteria & Backlog

Acceptance criteria per fitur P0, task teknis, urutan sprint, dan Definition of Done ada di `BACKLOG.md`.

Ringkasan Definition of Done:

- Kode mengikuti konvensi Laravel repo (Form Request, Policy, Service, Job, Pest).
- Minimal satu test happy path dan satu failure path per acceptance criterion; race condition stok/check-in diuji.
- `vendor/bin/pint --dirty --format agent` bersih.
- Migration dan seeder dapat dijalankan dari database kosong.
- `schema.sql` dan `erd.mmd` diperbarui bila skema berubah.
- Tidak ada data pribadi di log.
- Observability dan error handling terpasang.
- Tidak ada bug critical/high; UAT disetujui product owner.

---

## 16. Lampiran

- `schema.sql`: skema aktual MySQL/MariaDB, sinkron dengan migration.
- `erd.mmd`: ERD domain ticketing, sinkron dengan `schema.sql`.
- `BACKLOG.md`: backlog P0, acceptance criteria, dan rencana eksekusi.
