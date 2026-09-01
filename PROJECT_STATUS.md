# PHASE 16 — SUPERADMIN FULL ACCESS & MYSQL MIGRATION

**Pembaruan terakhir:** 1 September 2026
**Fase saat ini:** Phase 16 selesai
**Status:** **READY WITH CONDITIONS** untuk development MySQL lokal; belum merupakan persetujuan deployment production.

## A. Ringkasan Phase 16

Phase 16 memberi Superadmin akses global ke seluruh modul dan aksi SIPAK yang benar-benar tersedia, tanpa meniadakan state machine, ledger, transaksi, locking, validasi, audit trail, atau FK. Database development aktif telah berpindah dari SQLite ke MySQL 8.0.30 (`sipak`), sementara SQLite in-memory tetap menjadi konfigurasi test default dan MySQL menggunakan konfigurasi test terpisah (`sipak_testing`).

Tidak ada menu palsu untuk workflow Blueprint yang belum diimplementasikan. Approval Kasie, bulk sign-off Kepala UPTD, master alasan batal/rusak, dan UI audit khusus tetap di luar scope karena tidak memiliki route/workflow aktual.

## B. Scope dan Keputusan

- Sumber kebenaran yang diaudit: Blueprint SIPAK, route, Gate, action, controller, model, migration, test, navigation, konfigurasi database, dan schema MySQL aktual.
- Superadmin adalah administrator global, bukan Petugas Loket virtual. `loket_id` Superadmin tetap `NULL`.
- UI permission tidak menjadi otorisasi. Semua route mutasi tetap melewati middleware, Gate/FormRequest, dan action domain.
- SQLite `database/database.sqlite` tidak dihapus atau diubah; satu akun Superadmin lokal dipindahkan secara non-destruktif ke MySQL.
- Tidak ada dependency, perubahan arsitektur besar, atau workflow bisnis baru yang ditambahkan.

## C. Implementasi Superadmin

### Otorisasi global

`Gate::before()` pada `AppServiceProvider` memberikan allow global hanya untuk `UserRole::Superadmin`; Gate spesifik seluruh role lain tetap eksplisit. Helper konteks pada `User` dipakai action domain agar bypass tidak berhenti di controller:

- konteks Loket untuk accept allocation, draft BAP, cancellation, dan klarifikasi;
- pencipta/pembatal allocation;
- verifier Tahap 1/Tahap 2;
- penerimaan administratif Bendahara Barang.

Action tetap mengunci record, mengecek status saat transaksi, memakai `DB::transaction(..., attempts: 3)`, dan mencatat actor Superadmin pada audit log. Superadmin tidak dapat memaksa transisi state yang tidak sah.

### Cakupan aktual

Superadmin dapat membuka dan melakukan aksi yang tersedia pada Dashboard, User Management, inventaris SKPD, Box, Allocation, BAP, BAP batal/rusak, verifikasi Tahap 1 dan 2, klarifikasi/re-verifikasi, penerimaan administrasi, Buku Kendali, Laporan Pemakaian, PDF, dan Excel.

Pada form buat BAP, Superadmin memilih Loket eksplisit sebagai context request (`loket_id`). Context tersebut tidak dipersist ke akun, dan form tidak dapat dikirim sebelum Loket dipilih. Petugas Loket biasa tetap memakai Loket akun sendiri serta dilarang mengirim `loket_id` buatan klien.

## D. Integrasi UI dan Navigasi

- Navigation sekarang memakai route Wayfinder aktual untuk Inventaris SKPD, Box SKPD, dan Alokasi SKPD.
- Entry dengan `availability: planned` disembunyikan dari navigasi; tidak ada CTA untuk modul yang belum memiliki workflow/rute.
- BAP form memakai `Form` Inertia v3 dengan object Wayfinder langsung, bukan API `.form()` yang tidak tersedia pada generator saat ini.
- Wayfinder diregenerasi dan build Vite lulus.

Validasi browser visual, mobile, light/dark, keyboard, dan a11y tidak tersedia pada environment ini sehingga belum dapat diklaim PASS.

## E. Test Superadmin

`tests/Feature/SuperadminAccessTest.php` menutup direct HTTP dan action matrix berikut:

- Superadmin tanpa Loket memilih Loket, membuat Box dan Allocation, menerima serta membatalkan Allocation, membuat/mengubah/mengajukan BAP, mencatat pembatalan, menjalankan discrepancy–klarifikasi–re-verifikasi Tahap 1, Tahap 2, dan penerimaan administratif.
- User Management: create, update/nonaktif, dan reset password oleh Superadmin.
- Semua halaman/modul aktual dapat dibuka oleh Superadmin, termasuk output PDF/Excel.
- Audit receipt menyimpan `actor_id` Superadmin dan akun tetap tanpa Loket.
- Role non-Superadmin tetap ditolak dari mutasi lintas-kewenangan.

Hasil focused test pada MySQL: **2 test, 80 assertion, PASS**. Suite lengkap juga lulus di SQLite dan MySQL.

## F. Database MySQL dan Migrasi

### Konfigurasi dan schema

- `.env` development dan `.env.example` memakai `DB_CONNECTION=mysql`, host `127.0.0.1`, port `3306`, database `sipak`, dan charset/collation konfigurasi Laravel `utf8mb4`/`utf8mb4_unicode_ci`.
- Script bootstrap Composer tidak lagi membuat file SQLite secara paksa.
- `phpunit.xml` tetap SQLite in-memory. `phpunit.mysql.xml` menargetkan database khusus `sipak_testing` tanpa mengubah default CI.
- Semua 18 migration berstatus `Ran` pada MySQL. Nama index/FK panjang diperpendek pada tiga migration agar tetap di bawah batas identifier MySQL.
- Seluruh tabel aplikasi MySQL memakai InnoDB dan `utf8mb4_unicode_ci`.

### Data awal dan akses login

Sebelum migrasi, SQLite hanya memiliki satu user bisnis: Superadmin aktif tanpa Loket; tabel Loket, Box, Allocation, BAP, verification, clarification, dan audit log kosong. Akun tersebut dipindahkan ke MySQL dengan username/role/status yang sama dan hash password yang dibandingkan identik tanpa menampilkan rahasia. Hasil: `global_administrator=true`, `loket_id=NULL`, `is_active=true`.

Tidak ada password default baru yang dibuat atau diungkapkan. Login memakai kredensial lokal Superadmin yang telah ada sebelumnya; plaintext password tidak tersedia untuk diuji ulang secara otomatis. `DatabaseSeeder` tidak dijalankan dan saat ini hanya membuat `Test User`, bukan bootstrap Superadmin production.

### Integrity schema MySQL

- FK aktif untuk allocation, BAP, cancellation, verification, clarification response/resolution, receipt (`received_by`), dan audit actor.
- Check constraint aktif untuk range/quantity/status Box, Allocation, BAP, usage segment, dan alasan cancellation.
- Percobaan insert BAP orphan pada `sipak_testing` ditolak MySQL dengan error FK 1452 (`baps_loket_id_foreign`); query sisa row menghasilkan `0`.
- Perbaikan assertion portability menjaga test immutability ketat atas raw data tersimpan, bukan representasi in-memory SQLite. `DATE` MySQL (`Y-m-d`) dan agregat `SUM()` MySQL yang string kini dinormalisasi hanya pada test.

## G. Security, Integrity, dan Timezone

Tidak ada bypass integrity untuk Superadmin: allocation/BAP tetap ledger-derived, BAP submitted tetap immutable, clarification/re-verification tetap memakai attempt baru, dan receipt tidak mengubah sumber usage/cancellation/verification.

Konfigurasi aplikasi masih `app.timezone=UTC`; MySQL global dan session timezone adalah `SYSTEM`. `service_date` adalah cast `date`, dibentuk dan dirender sebagai `Y-m-d`, sehingga laporan periodik tidak melakukan konversi timestamp. Timestamp audit/workflow tetap memakai `now()` aplikasi.

**Technical decision required:** timezone operasional (misalnya Asia/Makassar) belum ditetapkan Blueprint. Jangan mengubah timezone konfigurasi atau melakukan normalisasi data tanpa keputusan cut-off hari pelayanan, dampak laporan, dan rencana migrasi. Pengujian lintas tengah malam/timezone belum dilakukan.

## H. Laporan dan Performance MySQL

Laporan Pemakaian, PDF, dan Excel tetap read-only terhadap BAP `completed`; suite MySQL membuktikan sumber tidak termutasi. `EXPLAIN` MySQL menunjukkan indeks tersedia dan digunakan untuk Allocation (`loket_id,status`), Verification (`stage,status,started_at`), dan Clarification (`bap_id,status`).

Untuk query report BAP, MySQL mengenali `baps_status_service_date_index`, tetapi database development tanpa data memilih indeks alternatif dengan prefix `status`. Ini membuktikan DDL/compatibility, bukan throughput production. Benchmark dengan volume data realistis dan uji concurrency paralel belum dilakukan.

## I. Temuan Audit dan Technical Debt

| ID | Prioritas | Status | Temuan / tindak lanjut |
| --- | --- | --- | --- |
| P2-01 | P2 | Open | `npm run types:check` masih gagal pada 12 pemanggilan `.form()` Wayfinder di halaman di luar perubahan Phase 16. Tidak ada error TypeScript baru dari implementasi ini. |
| P2-02 | P2 | Open | `npm run check` masih menemukan formatting pada 11 file pre-existing di luar scope. Jangan jalankan formatter global tanpa review. |
| P2-03 | P2 | Open | Browser/a11y/responsive/light-dark/print visual belum tervalidasi di environment ini. |
| P2-04 | P2 | Open | Uji race/concurrency paralel MySQL untuk lock inventory dan verification belum dilakukan; locking action dan suite serial telah diuji. |
| P2-05 | P2 | Open decision | Tetapkan timezone operasional sebelum perubahan konfigurasi UTC atau interpretasi timestamp laporan. |
| P2-06 | P2 | Open | `DatabaseSeeder` bukan bootstrap production yang aman karena hanya membuat Test User; provisioning akun awal harus dari migrasi backup atau manajemen user terotorisasi. |
| P3-01 | P3 | Open business gap | Approval Kasie, bulk sign-off Kepala UPTD, master alasan, dan UI audit belum memiliki workflow/rute aktual. |

## Matriks Quality Gate Phase 16

| Pemeriksaan | Hasil | Bukti |
| --- | --- | --- |
| `php artisan test --compact` | PASS | 158 test, 1.336 assertion (SQLite). |
| `vendor/bin/pest --configuration=phpunit.mysql.xml --compact` | PASS | 158 test, 1.336 assertion (MySQL). |
| Superadmin feature test pada MySQL | PASS | 2 test, 80 assertion. |
| MySQL migrations | PASS | 18/18 `Ran`; schema InnoDB, FK, check constraint, dan index diperiksa. |
| `vendor/bin/phpstan analyse` | PASS | 0 error. |
| `vendor/bin/pint --dirty --format agent` | PASS | Tidak ada pelanggaran PHP style pada perubahan. |
| `npm run build` | PASS | 2.363 modul ditransform; Wayfinder type generation berhasil. |
| `npm run types:check` | FAIL (pre-existing) | 12 error `.form()` Wayfinder di file di luar scope; tidak ada error baru Phase 16. |
| `npm run check` | FAIL (pre-existing) | 11 file formatting di luar scope; file BAP form yang diubah telah diformat terarah. |
| `composer validate --strict` | PASS | `composer.json` valid. |
| `composer audit --format=json` | PASS | 0 advisory, 0 abandoned package. |
| `npm audit --omit=dev --json` | PASS | 0 vulnerability production dependency. |
| `git diff --check` | PASS | Tidak ada whitespace error setelah final check. |
| Browser/a11y/parallel locking | NOT VERIFIED | Tool/browser dan harness concurrency paralel tidak tersedia. |

## J. Kesimpulan dan Langkah Berikutnya

**Phase 16 selesai untuk development MySQL lokal.** Superadmin memiliki akses global nyata ke seluruh workflow SIPAK yang tersedia, tetap teridentifikasi di audit log, dan tidak diberi Loket permanen. Migration MySQL, integrity schema, data Superadmin awal, full suite SQLite, dan full suite MySQL telah terbukti.

Sebelum deployment production: ganti kredensial MySQL lokal/root dengan secret terkelola dan least-privilege user, lakukan backup/restore rehearsal, tetapkan timezone operasional, selesaikan debt TypeScript/formatting agar CI hijau, dan uji browser serta concurrency pada lingkungan production-like. Tidak ada Phase 17 atau fitur bisnis baru yang dikerjakan dalam fase ini.
