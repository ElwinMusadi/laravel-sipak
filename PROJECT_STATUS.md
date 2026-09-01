# SIPAK — STATUS PROYEK

**Pembaruan terakhir:** 1 September 2026
**Fase saat ini:** PHASE 15 — FINAL AUDIT, SECURITY HARDENING, REGRESSION & RELEASE READINESS
**Status akhir:** **READY WITH CONDITIONS** untuk codebase lokal; **belum boleh diperlakukan sebagai deployment production**.

## Ringkasan Eksekutif

Phase 15 mengaudit implementasi aktual SIPAK terhadap AI PRODUCT BLUEPRINT SIPAK, status Phase 14, source code, rute, skema, test, konfigurasi, dan quality gate. Tidak ditemukan P0. Tiga P1 deterministik sudah diperbaiki: 2FA yang tidak dikehendaki, SSR parsial, dan batas numerator `0000000`.

Codebase lulus seluruh test PHP, PHPStan, Pint, build Vite, validasi Composer, audit dependensi, serta type-check TypeScript yang dijalankan serial setelah Wayfinder selesai digenerasi. Release masih bersyarat karena CI gagal pada formatting issue di luar scope, environment lokal masih development, MySQL target belum diuji, serta validasi browser/a11y belum tersedia.

## Ruang Lingkup dan Metodologi Audit

Audit mencakup Blueprint, status fase terdahulu, riwayat commit Phase 08–14, migrations, enum, model, request, Gate, action, controller, rute, Wayfinder, halaman React, komponen shadcn/Radix, test, dependensi, konfigurasi, workflow CI, dan database SQLite lokal.

Validasi dilakukan dengan inspeksi source dan schema, `route:list`, `migrate:status`, `db:show`, `EXPLAIN QUERY PLAN`, full test suite, build, formatter, static analysis, dependency audit, serta pemeriksaan konfigurasi tanpa menampilkan rahasia. Tidak ada klaim browser, screen reader, MySQL, atau deployment yang tidak benar-benar dijalankan.

## Baseline Implementasi Aktual

- Laravel 13.29.0, PHP 8.3.23, Inertia Laravel 3.3.1, React/Inertia 3.7.0, Wayfinder 0.1.21, Fortify 1.39.0, Pest 4.7.8, Tailwind 4.3.3, dan Vite 8.2.2.
- Database lokal adalah SQLite 3.40.0 dengan 24 tabel; migrasi 18/18 berstatus `Ran`.
- Riwayat implementasi terakhir adalah Phase 08–14: verifikasi dua tahap, klarifikasi/re-verifikasi immutable, penerimaan Bendahara Barang, Buku Kendali, Laporan Pemakaian, PDF, XLSX, dan print.
- Konfigurasi lokal aktif adalah `APP_ENV=local`, `APP_DEBUG=true`, SQLite, queue/cache/session database, dan mailer `log`.

## Status Kebutuhan Blueprint

| Area Blueprint                                  | Status implementasi aktual       | Catatan                                                                                  |
| ----------------------------------------------- | -------------------------------- | ---------------------------------------------------------------------------------------- |
| Username-only login, user aktif, RBAC           | Sesuai                           | Login dibatasi username, inactive user ditolak, rute dan Gate diuji.                     |
| Box, Allocation, ledger-derived inventory       | Sesuai                           | Mutasi memakai transaksi, lock inventaris, range/overlap/sekuens diuji.                  |
| BAP draft, submit, usage segment, batal/rusak   | Sesuai                           | Sumber data immutable setelah submit; cancellation tidak mengurangi ledger pemakaian.    |
| Verifikasi Tahap 1 dan Tahap 2                  | Sesuai implementasi              | Pelaksana aktual adalah Petugas Penetapan lalu Petugas Verifikasi.                       |
| Klarifikasi dan re-verifikasi                   | Sesuai                           | History attempt, respons, resolusi, dan reopen dipertahankan.                            |
| Penerimaan Bendahara Barang                     | Sesuai implementasi              | `verified_phase_2` menjadi prasyarat sebelum `completed`.                                |
| Buku Kendali, laporan, PDF/XLSX/print           | Sesuai implementasi              | Seluruhnya read-only dari BAP `completed`.                                               |
| Persetujuan Kasie dan bulk sign-off Kepala UPTD | Belum ada                        | Enum peran ada, tetapi workflow approval/bulk sign-off belum diimplementasikan.          |
| Master alasan batal/rusak                       | Belum ada                        | Alasan masih enum `Batal`/`Rusak`, bukan master data.                                    |
| Audit log                                       | Sebagian                         | Audit domain tersedia dan dipakai action, tetapi belum ada modul UI/report audit khusus. |
| Notifikasi operasional                          | Belum diverifikasi sebagai fitur | Tidak diklaim tersedia.                                                                  |

Perbedaan Blueprint yang belum diwujudkan tidak ditambal otomatis karena memerlukan keputusan proses bisnis dan otoritas operasional.

## Role dan Manajemen Pengguna

`UserRole` memuat Superadmin, Petugas Loket, Petugas Penetapan, Kasie Penetapan, Petugas Verifikasi, Kasie Verifikasi, Bendahara Barang, dan Kepala UPTD. Manajemen user hanya dapat diakses Superadmin; penugasan Loket, aktivasi/nonaktif, reset password, audit, dan pembatasan akses sesudah akun dinonaktifkan tercakup feature test.

Server tetap menjadi sumber otorisasi. Visibility `auth.permissions` hanya presentasi dan tidak menggantikan Gate, middleware, atau `FormRequest::authorize()`.

## Authentication dan Security Hardening

### Perbaikan P1 yang diterapkan

- Fortify 2FA dinonaktifkan dengan `features => []`; limiter dan view 2FA dihapus.
- Halaman, komponen, hook, request, serta rute Wayfinder 2FA yang tidak lagi dipakai dihapus.
- Endpoint `GET /two-factor-challenge` dan `POST /user/two-factor-authentication` sekarang diuji menghasilkan `404`.
- Passkey tetap paket/migrasi dormant tanpa rute atau integrasi aplikasi aktif. Dependensi tidak dihapus karena perubahan dependensi tidak termasuk mandat fase ini.
- `inertia.ssr.enabled` diubah menjadi `false`. Bundle `bootstrap/ssr/ssr.mjs` dan proses SSR tidak tersedia, sehingga konfigurasi sebelumnya merupakan SSR parsial yang berisiko menimbulkan asumsi deployment keliru.

### Hasil audit autentikasi

- Registrasi publik, reset password berbasis email, dan verifikasi email tidak tersedia.
- Login memakai username case-normalized, password hash, rate limit lima percobaan, dan akun aktif.
- Password update tetap dilindungi password confirmation.
- Tidak ada rute 2FA atau passkey yang terdaftar setelah hardening.
- Environment lokal tidak boleh dipakai sebagai environment production karena `APP_DEBUG=true`. Nilai production wajib disediakan melalui environment deployment, bukan dengan mengubah `.env` lokal pengembangan.

## Workflow BAP, Verifikasi, Klarifikasi, dan Finalisasi

Alur aktual adalah `draft → submitted → verifikasi tahap 1 → verifikasi tahap 2 → verified_phase_2 → completed`, dengan cabang klarifikasi dan attempt re-verifikasi baru. `completed` hanya dapat dibuat oleh penerimaan administratif Bendahara Barang setelah kedua verification record lulus dan tidak ada klarifikasi aktif.

Action memuat transaksi dengan `attempts: 3`, `lockForUpdate()`, revalidasi state di dalam transaksi, dan audit domain. BAP submitted, usage segment, cancellation, checklist/discrepancy, klarifikasi, serta receipt tidak dimutasi diam-diam pada tahap verifikasi atau klarifikasi.

### Temuan P2: detail verifikasi di luar antrean

`showForStage()` hanya memeriksa Gate role tahap. Berbeda dari halaman indeks yang memfilter `queueBapStatuses()`, endpoint detail belum secara eksplisit menolak BAP yang berada di luar antrean tahap. Mutasi tetap aman karena action start/complete melakukan revalidasi state, tetapi role verifier dapat membaca detail BAP di luar antrean bila mengetahui ID.

Keputusan yang dibutuhkan: apakah riwayat lintas-status memang boleh dibaca seluruh verifier. Jika tidak, tambahkan scope stage/status di endpoint detail dan test `404`/`403` untuk BAP di luar antrean. Tidak diperbaiki otomatis karena aturan akses riwayat belum diputuskan oleh Blueprint.

## Inventory, Nomeratur, dan Integritas Data

Box, Allocation, BAP, usage segment, dan cancellation tetap ledger-derived. Lock inventaris ID 1, validasi overlap/sekuens, serta transaksi menjaga mutasi concurrency pada jalur aplikasi. Laporan tidak menciptakan stok mutable atau duplicate ledger.

### Perbaikan P1 batas numerator

Sebelumnya request dan action menerima `0000000`. Semua jalur tulis kini menetapkan minimum `0000001`:

- registrasi Box;
- alokasi Box ke Loket;
- pembuatan BAP; dan
- pembaruan draft BAP.

Validasi HTTP memberi pesan `Nomeratur awal minimal 0000001.` dan action tetap menegakkan aturan bila dipanggil di luar controller. Test endpoint dan test domain action mencakup batas nol. Tampilan tujuh digit tetap dipertahankan.

### Catatan P2

Request Box dan Allocation masih mengharuskan `numerator_end > numerator_start`, sehingga range satu set tidak dapat dibuat melalui HTTP walaupun action/domain dapat memahami range inklusif. Kebutuhan bisnis “satu set” belum cukup eksplisit untuk mengubah kontrak ini otomatis.

## Laporan, Buku Kendali, dan Output

Buku Kendali dan Laporan Pemakaian memakai BAP `completed` sebagai scope read-only. Aggregate menghindari double count dengan tidak menggabungkan BAP langsung ke usage segment/cancellation. PDF, XLSX, dan print tidak membuat snapshot, tabel laporan, atau mutasi sumber. Nomeratur Excel diformat tujuh digit.

Audit SQLite membuktikan query laporan berdasarkan status dan tanggal memakai `baps_status_service_date_index`. Database lokal tidak memiliki data BAP nyata, sehingga hasil ini membuktikan rencana query, bukan throughput produksi.

## Route, Action, dan Wayfinder

Rute mutasi inventory/BAP/verification menggunakan auth, active-user enforcement, Gate atau `FormRequest::authorize()`, dan action domain. Test mencakup denial role, cross-Loket, state invalid, duplicate completion, serta client-supplied field yang dilarang.

Wayfinder digenerasi ulang setelah perubahan konfigurasi Fortify. Tidak ada lagi direktori atau ekspor `resources/js/routes/two-factor`; build menghasilkan kembali varian `.form()` yang diperlukan halaman Inertia.

## Database, Migration, dan Audit Trail

Migrasi lokal seluruhnya selesai. Schema memuat constraint/index untuk kebutuhan yang diuji, termasuk indeks BAP `status, service_date`, uniqueness cancellation, serta uniqueness BAP per Loket/tanggal. Database target MySQL tidak tersedia pada audit ini; kompatibilitas DDL MySQL, check constraint, dan locking production belum dapat diklaim.

`AuditLog` bersifat polymorphic dengan actor, event, old values, dan new values. Action inventory, BAP, verification, klarifikasi, serta penerimaan administratif mencatat event domain. Tidak ada pembuktian UI audit, retensi, backup, observability, atau prosedur pemulihan production.

## UI/UX dan Aksesibilitas

Build TypeScript/Vite lulus. Komponen aktif menggunakan shadcn/Radix dan tidak ada rute 2FA tersisa. Masih terdapat token warna Tailwind hard-coded pada beberapa komponen/auth badge; ini technical debt P3 untuk konsistensi tema, bukan bukti kegagalan fungsi.

Browser Pest dan browser automation tidak tersedia dalam environment audit. Karena itu responsif desktop/mobile, dark mode aktual, keyboard navigation, screen reader, print preview fisik, dan accessibility scan berstatus **BELUM DIVERIFIKASI**, bukan PASS.

## Quality Gate dan Release Verification

| Pemeriksaan                                    | Hasil                       | Bukti / catatan                                                                       |
| ---------------------------------------------- | --------------------------- | ------------------------------------------------------------------------------------- |
| `php artisan test --compact`                   | PASS                        | 162 test, 1.274 assertion.                                                            |
| Test terdampak hardening                       | PASS                        | 41 test, 216 assertion.                                                               |
| `vendor/bin/phpstan analyse`                   | PASS                        | 0 error.                                                                              |
| `vendor/bin/pint --test`                       | PASS                        | Tidak ada pelanggaran PHP style.                                                      |
| `composer validate --strict`                   | PASS                        | `composer.json` valid.                                                                |
| `composer audit --format=json`                 | PASS                        | 0 advisory, 0 abandoned package.                                                      |
| `npm audit --omit=dev --json`                  | PASS                        | 0 vulnerability production dependency.                                                |
| `npm run types:check` serial setelah build     | PASS                        | TypeScript tanpa error.                                                               |
| `npm run build`                                | PASS                        | Vite dan generation Wayfinder berhasil.                                               |
| `git diff --check`                             | PASS                        | Tidak ada whitespace error.                                                           |
| `php artisan config:cache` lalu `config:clear` | PASS                        | Konfigurasi dapat dicache dan dipulihkan.                                             |
| `npm run check`                                | FAIL                        | Formatting issue pada 11 source file di luar scope setelah status file ini diformat.  |
| `composer ci:check`                            | FAIL                        | Berhenti pada `npm run check`; workflow CI memang memanggil script ini.               |
| SSR server health check                        | TIDAK BERLAKU / tidak jalan | Server SSR tidak berjalan, dan konfigurasi aplikasi sekarang secara sengaja disabled. |
| MySQL migration/query/locking                  | BELUM DIVERIFIKASI          | Hanya SQLite lokal yang diuji.                                                        |
| Browser/a11y                                   | BELUM DIVERIFIKASI          | Tidak ada Browser Pest/browser automation.                                            |

Catatan: type-check yang dijalankan paralel dengan build sempat membaca keluaran Wayfinder antargenerasi tanpa `.form()`. Pemeriksaan serial setelah build lulus; hasil paralel tersebut bukan defect source.

## Temuan Audit dan Prioritas

| ID    | Prioritas            | Status            | Temuan dan tindak lanjut                                                                                                                                                                                                                                            |
| ----- | -------------------- | ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| P1-01 | P1                   | Fixed             | 2FA Fortify aktif padahal tidak digunakan. Dinonaktifkan, route/UI/support code dihapus, dan 404 diuji.                                                                                                                                                             |
| P1-02 | P1                   | Fixed             | SSR enabled tanpa bundle/proses SSR. Diubah menjadi disabled dan dikunci test konfigurasi.                                                                                                                                                                          |
| P1-03 | P1                   | Fixed             | `0000000` diterima sebagai numerator. Minimum `0000001` ditegakkan pada request dan action.                                                                                                                                                                         |
| P1-04 | P1 release condition | Open              | Environment lokal memakai `APP_ENV=local` dan `APP_DEBUG=true`; jangan deploy konfigurasi ini. Provisioning production wajib menetapkan `APP_ENV=production`, `APP_DEBUG=false`, key/secret, mail, queue, cache, session, log, HTTPS, dan trusted proxy yang tepat. |
| P2-01 | P2                   | Open              | CI gagal karena 11 formatting issue existing di luar scope. Jalankan formatter terarah/review pada file tersebut sebelum merge/release.                                                                                                                             |
| P2-02 | P2                   | Open              | MySQL target belum menjalankan migration, test, EXPLAIN, atau uji transaction locking.                                                                                                                                                                              |
| P2-03 | P2                   | Open decision     | Endpoint detail verifikasi belum menegakkan queue status seperti halaman indeks. Putuskan kebijakan akses history lalu tambah scope/test bila perlu.                                                                                                                |
| P2-04 | P2                   | Open decision     | Range Box/Allocation satu set ditolak HTTP karena end harus lebih besar dari start.                                                                                                                                                                                 |
| P2-05 | P2                   | Open business gap | Approval Kasie, bulk sign-off Kepala UPTD, master alasan batal/rusak, dan UI audit belum memiliki kontrak bisnis yang dapat diimplementasikan aman.                                                                                                                 |
| P3-01 | P3                   | Open              | Hard-coded color utilities tersisa pada beberapa komponen; konsolidasikan dengan token tema ketika ada fase UI.                                                                                                                                                     |
| P3-02 | P3                   | Open              | Node lokal 22.17.0 berada di bawah engine Vite Plus `^22.18.0`; build lulus, tetapi upgrade Node diperlukan untuk baseline developer yang deterministik.                                                                                                            |

Tidak ada P0 ditemukan.

## Perubahan yang Diterapkan pada Phase 15

- Menonaktifkan dan menghapus permukaan 2FA aktif tanpa menghapus migrasi/data historis atau dependency secara spekulatif.
- Menonaktifkan SSR yang belum benar-benar dideploy.
- Memperketat batas numerator menjadi `0000001–9999999` pada empat request dan empat action domain.
- Menambah regression test hardening 2FA, SSR disabled, HTTP boundary numerator, dan action boundary numerator.
- Meregenerasi Wayfinder dan memverifikasi tidak ada rute 2FA tersisa.

## Technical Debt dan Keputusan yang Dibutuhkan

1. Putuskan apakah Kasie approval dan bulk sign-off Kepala UPTD wajib sebelum mengubah workflow yang sudah stabil.
2. Putuskan apakah verifier boleh melihat detail BAP di luar antrean masing-masing.
3. Putuskan apakah range satu set valid untuk Box dan Allocation.
4. Tetapkan timezone operasional. Konfigurasi aplikasi masih UTC; jangan mengubahnya tanpa keputusan cut-off periode dan migrasi dampak data.
5. Jika audit export, dokumen resmi, snapshot inventory, atau notifikasi diperlukan, tetapkan actor, event, retention, format, approval, dan otoritas bisnis terlebih dahulu.

## Checklist Deployment Wajib

- [ ] Sediakan environment production terpisah dengan `APP_DEBUG=false`, `APP_ENV=production`, `APP_KEY` baru/terkelola, HTTPS, `APP_URL`, mail, queue, cache, session, logging, dan secret yang benar.
- [ ] Uji backup, restore, rotasi log, monitoring, alert, dan kebijakan retensi di lingkungan production-like.
- [ ] Provision MySQL target; jalankan migration pada salinan aman, full test, EXPLAIN query laporan, serta uji locking/concurrency inventory dan verification.
- [ ] Selesaikan formatting issue agar `npm run check` dan `composer ci:check` lulus.
- [ ] Upgrade Node lokal ke minimal 22.18.0 atau gunakan environment terkelola yang memenuhi engine Vite Plus.
- [ ] Jalankan validasi browser manual/otomatis: desktop/mobile, light/dark, keyboard, focus/error state, a11y, dan print preview.
- [ ] Tinjau P2-03 dan P2-04 bersama pemilik proses bisnis.

## Rekomendasi dan Status Readiness

**READY WITH CONDITIONS.** Tidak ada P0, dan P1 pada codebase telah diperbaiki. Namun status ini hanya menyatakan codebase lokal telah melewati regression/security gate yang tersedia. Ia bukan persetujuan deploy production.

Release/merge sebaiknya ditahan sampai formatting CI diselesaikan. Deployment production wajib menunggu checklist environment dan validasi MySQL/backup/observability/browser di atas. Tidak ada Phase 16 atau fitur bisnis baru yang dikerjakan dalam audit ini.
