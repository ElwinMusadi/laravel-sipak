# PHASE 17 — MASTER LOKET, CRUD DOMAIN, DAN DEVELOPMENT SEED DATA

**Pembaruan terakhir:** 1 September 2026
**Fase saat ini:** Phase 17 selesai
**Status:** **READY FOR DEVELOPMENT** pada MySQL lokal; bukan persetujuan deployment production.

## A. Ringkasan

Phase 17 menambahkan lifecycle aman untuk Master Loket, metadata Box SKPD, dan draft BAP tanpa mengubah prinsip SIPAK yang ledger-derived. Riwayat alokasi, BAP submitted/verification/clarification/receipt/completed, cancellation, usage segment, serta audit yang sudah ada tetap dipertahankan.

Master Loket kini memiliki `code` unik, `name`, `description`, dan `is_active`. Status inactive menghentikan assignment Petugas Loket, alokasi baru, dan BAP baru, tetapi tidak menyembunyikan atau memutus riwayat yang sudah ada. Superadmin tetap administrator global tanpa Loket permanen.

## B. GAP Analysis dan Keputusan Implementasi

| Area               | Kondisi sebelum Phase 17                                                                               | Keputusan dan hasil Phase 17                                                                                                                                                                                          |
| ------------------ | ------------------------------------------------------------------------------------------------------ | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Master Loket       | Hanya memiliki nama; belum ada lifecycle, code, status, audit, atau UI khusus.                         | Ditambahkan CRUD Superadmin, pencarian/filter, detail, `code` unik, nama non-unik, status, audit, tabel desktop, dan kartu mobile.                                                                                    |
| Loket inactive     | Belum ada penjagaan state.                                                                             | Assignment Petugas Loket menolak Loket inactive; action alokasi dan BAP mengunci serta mengecek status Loket di dalam transaksi. Riwayat tetap dapat dibaca.                                                          |
| Box SKPD           | Register, list, dan detail tersedia, tetapi metadata tidak dapat dikelola setelah register.            | Update hanya mengizinkan nomor/referensi, lokasi penyimpanan pusat, dan tanggal penerimaan. Range dan total tidak dikirim/diubah oleh endpoint. Hapus hanya bila belum memiliki alokasi.                              |
| Draft BAP          | Create/read/update/submit tersedia; tidak ada penghapusan aman.                                        | Hapus hanya untuk draft yang dimiliki actor berwenang dan tanpa cancellation, verification, atau clarification. Usage segment dibersihkan secara terkunci, status allocation dihitung ulang, audit tombstone dicatat. |
| BAP batal/rusak    | Model, action, dan form nested BAP tersedia, tetapi menu riwayat tidak memberi jalur input yang jelas. | Ditambahkan entry point menu `Catat batal/rusak` dengan pemilih draft BAP. Tetap memakai `BapCancellation` yang ada; tidak ada model dokumen paralel atau edit/delete riwayat.                                        |
| Identitas pengguna | Login sudah username + password; belum ada NIP.                                                        | `users.nip` (18 digit, unik) ditambahkan secara migrasi aman; manajemen user mewajibkan NIP. `email` tetap nullable legacy/framework dan bukan credential.                                                            |
| Seed development   | `DatabaseSeeder` membuat Test User generik.                                                            | Seeder idempoten membuat empat Loket dan tujuh akun development hanya pada environment `local`/`testing`; tidak membuat Box, allocation, BAP, cancellation, atau data transaksi lain.                                 |

## C. Matriks Fungsi Phase 17

| Fungsi                 | Otorisasi dan lifecycle                                                                                            | Bukti implementasi                                                                         |
| ---------------------- | ------------------------------------------------------------------------------------------------------------------ | ------------------------------------------------------------------------------------------ |
| Master Loket           | Hanya Superadmin; `Gate::before` tetap global hanya untuk Superadmin.                                              | `LoketController`, `CreateLoket`, `UpdateLoket`, `DeleteLoket`, route resource `lokets.*`. |
| Nonaktifkan Loket      | Ditolak bila masih ada user yang ditugaskan; action memakai lock inventaris dan row Loket.                         | `UpdateLoket`; request user hanya menerima Loket aktif.                                    |
| Hapus Loket            | Ditolak bila ada user, allocation, atau BAP; penghapusan yang benar-benar belum dipakai menyimpan audit tombstone. | `DeleteLoket`.                                                                             |
| Alokasi/BAP pada Loket | Action mengunci Loket setelah lock inventaris dan menolak Loket inactive.                                          | `CreateSkpdAllocation`, `CreateBap`.                                                       |
| Metadata Box           | Bendahara Barang/Superadmin melalui Gate existing; metadata terbatas, range immutable.                             | `UpdateSkpdBox`, `DeleteSkpdBox`, `skpd.boxes.edit/update/destroy`.                        |
| Hapus Box              | Hanya tanpa allocation; Box bersejarah tidak dapat dihapus.                                                        | `DeleteSkpdBox`.                                                                           |
| Hapus draft BAP        | Hanya draft milik Petugas Loket pembuat atau Superadmin; server menolak record bersejarah.                         | `DeleteDraftBap`, `baps.destroy`, dialog konfirmasi.                                       |
| Batal/rusak            | Hanya draft BAP dalam kewenangan actor; histori tetap immutable.                                                   | Entry `bap-cancellations.create` menuju route nested cancellation yang sudah ada.          |
| NIP                    | 18 digit dan unik untuk create/update user; tidak mengubah Fortify username/password.                              | migration `users.nip`, request user management, UI dan audit.                              |
| Seed akun              | Idempoten, lokal/testing saja, password development ter-hash.                                                      | `DevelopmentUserSeeder`, dipanggil `DatabaseSeeder`.                                       |

## D. Data Development

Seeder development membuat Loket aktif berikut tanpa truncate atau penghapusan data:

| Code              | Nama                  |
| ----------------- | --------------------- |
| `SAMSAT-KANTOR`   | SAMSAT Kantor         |
| `SAMSAT-KELILING` | SAMSAT Keliling       |
| `SAMSAT-CORNER`   | SAMSAT Corner         |
| `MPP`             | Mall Pelayanan Publik |

Tujuh akun development menggunakan password `password` yang di-hash dan login tetap memakai username:

| Nama                | Username         | Role               | NIP                  | Loket         |
| ------------------- | ---------------- | ------------------ | -------------------- | ------------- |
| Elwin Bessiesura    | `elwinmusadi16`  | Superadmin         | `199707162025061002` | —             |
| Yununs Asamani      | `yunus.asamani`  | Bendahara Barang   | `197907302009011003` | —             |
| Simson Sae          | `simson.sae`     | Petugas Loket      | `197709032007011010` | SAMSAT Kantor |
| Lily Toelle         | `lily.toelle`    | Petugas Penetapan  | `197012281993092001` | —             |
| Jevon Wila Huky     | `jevon.wilahuky` | Petugas Verifikasi | `200408152025211001` | —             |
| Skolastika G. Maing | `nena.maing`     | Kasie Penetapan    | `198804212011012006` | —             |
| Jonny Alfreth Do'o  | `jonny.alfreth`  | Kasie Verifikasi   | `197106152007011039` | —             |

Seeder tidak berjalan di environment selain `local` dan `testing`. Password tersebut hanya untuk development dan harus diganti/di-provision terpisah sebelum production.

## E. MySQL, Migrasi, dan Integritas

- Migration `2026_09_01_085531_add_lifecycle_fields_to_lokets_table` telah diterapkan pada MySQL development: `lokets.code` non-null dan unik, `description` nullable, `is_active` default aktif. Backfill code numerik hanya berlaku bila terdapat Loket lama.
- Migration `2026_09_01_085532_add_nip_to_users_table` telah diterapkan: `users.nip` nullable untuk keamanan data lama namun unik; request manajemen user mewajibkan nilai 18 digit untuk data baru/diubah.
- Verifikasi schema MySQL membuktikan index unik `lokets_code_unique` dan `users_nip_unique` aktif.
- Verifikasi data MySQL membuktikan tujuh username/NIP/role aktif dan empat Loket seed tersedia; Simson Sae ditempatkan pada `SAMSAT-KANTOR`.
- Tidak ada perubahan pada credential Fortify: username + password tetap satu-satunya mekanisme login aplikasi.

## F. Security dan Integritas Domain

- Visibility navigasi memakai `auth.permissions` hanya untuk presentasi; middleware, Gate, FormRequest, action, transaksi, dan lock tetap memegang otorisasi server.
- Mutasi Loket/Box/BAP yang menyentuh inventaris memakai `DB::transaction(..., attempts: 3)` dan lock inventaris yang berlaku. Tidak ada mutable stock atau ledger baru.
- Create/update pengguna yang menugaskan Petugas Loket ikut mengunci inventaris dan row Loket aktif, sehingga terurut terhadap penonaktifan Loket.
- Penghapusan Loket/Box yang tidak memiliki referensi diperbolehkan hanya sebagai cleanup master data awal dan tetap meninggalkan audit tombstone. Semua entitas yang memiliki user, allocation, BAP, atau usage history ditolak.
- Submitted BAP serta BAP pada tahap verification, clarification, receipt, dan `completed` tidak dapat dihapus. Catatan batal/rusak juga tetap tidak menyediakan edit/delete.
- Laporan, Buku Kendali, verification, clarification, receipt, dan status final BAP tidak dimodifikasi oleh Phase 17.

## G. Quality Gate Phase 17

| Pemeriksaan                                                   | Hasil        | Bukti                                                                                                                                  |
| ------------------------------------------------------------- | ------------ | -------------------------------------------------------------------------------------------------------------------------------------- |
| Focused Phase 17 (SQLite)                                     | PASS         | 5 test, 140 assertion.                                                                                                                 |
| Regresi user/inventory/BAP/cancellation terkait (SQLite)      | PASS         | 40 test, 441 assertion.                                                                                                                |
| Full suite SQLite                                             | PASS         | 163 test, 1.476 assertion.                                                                                                             |
| Full suite MySQL                                              | PASS         | 163 test, 1.476 assertion dengan `phpunit.mysql.xml`.                                                                                  |
| MySQL migration development                                   | PASS         | Dua migration Phase 17 selesai diterapkan.                                                                                             |
| MySQL seed development                                        | PASS         | Seeder `DevelopmentUserSeeder` selesai; schema dan data inti diverifikasi read-only.                                                   |
| `vendor/bin/phpstan analyse --no-progress`                    | PASS         | 0 error.                                                                                                                               |
| `vendor/bin/pint --dirty --format agent`                      | PASS         | Formatter PHP dijalankan pada perubahan.                                                                                               |
| `php artisan wayfinder:generate --with-form --no-interaction` | PASS         | Actions dan routes TypeScript diregenerasi.                                                                                            |
| `npm run types:check`                                         | PASS         | Deklarasi shared Inertia diperbaiki; tidak ada error TypeScript.                                                                       |
| `npm run build`                                               | PASS         | 3.317 modul ditransform dan build produksi selesai.                                                                                    |
| `npx vp check --fix` (berkas Phase 17)                        | PASS         | Format dan lint 20 berkas dalam scope lulus.                                                                                           |
| `npm run check` global                                        | NOT CLEAN    | Formatting masih ditemukan pada berkas pre-existing/di luar scope; formatter global tidak dijalankan untuk menjaga perubahan pengguna. |
| Browser/a11y/responsive/keyboard/print                        | NOT VERIFIED | Tidak ada browser harness pada environment ini.                                                                                        |
| Uji concurrency paralel MySQL                                 | NOT VERIFIED | Locking action dan suite serial lulus; harness paralel belum dijalankan.                                                               |

## H. Open Questions dan Batas Phase Berikutnya

| ID       | Status                      | Keputusan yang masih diperlukan                                                                                                                                                        |
| -------- | --------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| OQ-17-01 | Open business gap           | Blueprint masih mencantumkan Approval Kasie, bulk sign-off Kepala UPTD, master alasan batal/rusak, dan UI audit khusus tanpa workflow/rute implementasi. Tidak diinvent oleh Phase 17. |
| OQ-17-02 | Technical decision required | Timezone operasional dan cut-off hari pelayanan belum ditetapkan; konfigurasi aplikasi tetap UTC.                                                                                      |
| OQ-17-03 | Validation pending          | Visual browser untuk desktop/mobile, keyboard, light/dark, print, dan a11y belum dilakukan.                                                                                            |
| OQ-17-04 | Validation pending          | Uji race/concurrency paralel MySQL belum tersedia.                                                                                                                                     |

**Phase 17 selesai.** Implementasi berhenti pada Master Loket, lifecycle Box/draft BAP, entry BAP batal/rusak, NIP, dan seed development. Tidak ada pekerjaan Phase 18 yang dimulai.
