# SIPAK — STATUS PROYEK

## Fase Saat Ini

PHASE 04 — DOMAIN & DATABASE FOUNDATION

## Status Fase

Selesai dan tervalidasi pada scope Phase 04.

## Tanggal

2026-08-30

## Tujuan

Membangun fondasi domain dan database SIPAK untuk inventaris Box SKPD, alokasi digital ke Loket, pemakaian harian melalui BAP, BAP Batal/Rusak, dan audit trail tanpa membangun halaman bisnis atau workflow verifikasi lengkap.

## Ringkasan

SIPAK kini memakai model inventaris berbasis ledger rentang nomeratur. Satu nomeratur merepresentasikan satu set SKPD berisi lima lembar tindisan, sehingga lima warna tidak dimodelkan sebagai lima record. Box menyimpan sumber stok pusat; allocation menyimpan hak pakai administratif per rentang; BAP menyimpan penggunaan harian; dan `bap_usage_segments` menghubungkan pemakaian BAP dengan satu atau lebih allocation yang berurutan.

Tidak ada perubahan pada autentikasi username + password, role existing, route, UI React/Inertia, dependency, atau konfigurasi database. Development tetap menggunakan SQLite dan target deployment tetap MySQL.

## Domain Model

- `User` dan `UserRole` tetap menjadi identitas dan role sistem Phase 03; role adalah enum, bukan CRUD tabel role.
- `Loket` adalah pemegang administratif allocation dan pemilik BAP.
- `SkpdBox` adalah sumber inventaris pusat yang memiliki satu range nomeratur dan lokasi fisik pusat.
- `SkpdAllocation` adalah ledger distribusi dan digital handover; entitas ini menggantikan tabel Distribution terpisah karena range, penerimaan, dan lifecycle berada pada satu transaksi yang sama.
- `Bap` adalah satu dokumen pemakaian untuk satu Loket dan satu tanggal pelayanan.
- `BapUsageSegment` adalah ledger internal untuk memetakan range BAP ke allocation. Ini diperlukan ketika satu BAP melintasi batas dua allocation yang kontigu.
- `BapCancellation` merepresentasikan BAP Batal/Rusak sebagai record per nomeratur, bukan BAP kedua.
- Nomeratur tidak memiliki tabel individual; ia direpresentasikan oleh range Box, Allocation, BAP, dan segment pemakaian.
- Verification dan Clarification belum memiliki tabel karena workflow tersebut berada di phase berikutnya.

## Entity yang Diimplementasikan

- `skpd_boxes`
- `skpd_allocations`
- `baps`
- `bap_usage_segments`
- `bap_cancellations`
- `skpd_inventory_locks` sebagai satu technical lock row untuk serialisasi transaksi domain

## Relationship

```text
User ──< SkpdBox (created_by)
User ──< SkpdAllocation (created_by, accepted_by)
Loket ──< SkpdAllocation >── SkpdBox
Loket ──< Bap
Bap ──< BapUsageSegment >── SkpdAllocation
Bap ──< BapCancellation
AuditLog ── morphTo ── SkpdBox | SkpdAllocation | Bap
```

## Business Rules

- Setiap nomeratur disimpan sebagai integer 0–9.999.999 dan ditampilkan sebagai 7 digit dengan zero-padding; nomor tidak reset tahunan atau berdasarkan jenis.
- Register Box menerima range valid, nomor box unik, tidak overlap, dan harus melanjutkan nomeratur box sebelumnya tanpa loncatan.
- Ukuran Box normal adalah 2.000 set, tetapi schema tidak memaksakan ukuran tersebut agar penerimaan resmi dengan range berbeda tetap dapat dicatat.
- Satu Box hanya boleh memiliki allocation aktif untuk satu Loket. Range parsial berikutnya hanya boleh ke Loket yang sama.
- Allocation harus berada di dalam range Box, tidak boleh overlap, dan baru menjadi persediaan administratif aktif Loket setelah status `accepted`.
- BAP hanya dapat dibuat oleh user yang terhubung ke Loket pemilik BAP. Satu Loket hanya memiliki satu BAP per tanggal pelayanan.
- Nomeratur awal BAP pertama adalah awal allocation accepted pertama; BAP berikutnya wajib dimulai dari akhir BAP sebelumnya + 1. Hari tanpa pelayanan tidak memerlukan BAP dan tidak memutus urutan.
- Range BAP harus tertutup penuh oleh allocation accepted Loket; celah atau pemakaian di luar allocation ditolak.
- `total_usage` selalu dihitung `numerator_end - numerator_start + 1`. `online_usage_count` harus 0 sampai total tersebut dan tidak menambah total.
- Nomeratur batal/rusak wajib berada dalam range BAP, unik global, tetap masuk `total_usage`, dan tidak dapat digunakan ulang.
- BAP dan cancellation tidak dapat diubah melalui action setelah BAP diajukan.

## Nomeratur Strategy

Dipilih OPTION B: range/ledger, bukan satu row untuk setiap nomeratur.

- Lebih hemat storage dan tetap efisien untuk Box normal 2.000 set maupun stok jangka panjang.
- Uniqueness langsung dijaga untuk `box_number`, batas BAP, dan nomeratur cancellation; overlap interval dijaga oleh query transaksi yang terkunci.
- `bap_usage_segments` mempertahankan traceability dari BAP ke allocation tanpa membuat jutaan record nomeratur individual.
- Pembatalan bersifat sparse, sehingga hanya nomor batal/rusak yang membutuhkan record individual.

## Inventory Strategy

Dipilih strategi hybrid ledger-derived.

- Tidak ada kolom `stock` mutable.
- Stok tersedia pusat dihitung dari total Box dikurangi allocation pending dan allocation administratif aktif.
- Stok fisik pusat dihitung dari total Box dikurangi allocation `accepted`/`completed`; allocation pending tetap fisik di pusat tetapi telah direservasi.
- Kepemilikan administratif adalah `SkpdAllocation.loket_id` setelah accepted, sedangkan lokasi fisik sisa yang belum dialokasikan berasal dari `SkpdBox.central_storage_location`.
- Pemakaian dihitung dari segment BAP. Sisa allocation adalah quantity allocation dikurangi segment pemakaian.

## Allocation Strategy

Allocation menyatukan distribution dan digital handover:

- `pending`: range direservasi tetapi belum menjadi stok aktif Loket.
- `accepted`: Loket tujuan menerima handover digital.
- `completed`: seluruh range allocation telah digunakan dalam BAP.
- `cancelled`: hanya allocation pending yang dapat dibatalkan oleh pembuatnya; range dapat dialokasikan kembali ke Loket yang sama sesuai aturan ownership.

## State Model

- Box memakai status terhitung, bukan kolom mutable: `available`, `partially_allocated`, `fully_allocated`, dan `depleted`. Penerimaan Box dicatat sebagai event audit, bukan state yang mudah tersinkronisasi secara salah.
- Allocation: `pending → accepted → completed`, atau `pending → cancelled`.
- BAP: `draft → submitted`; enum `waiting_verification` sudah disiapkan untuk Phase 05. Workflow verifikasi/approval lanjutan tidak diimplementasikan pada fase ini.

## Database Strategy

- Schema memakai tipe integer unsigned untuk nomeratur dan quantity, date/datetime untuk waktu bisnis, `utf8mb4`/strict mode dari konfigurasi MySQL existing, dan string-backed enum agar state mudah diperluas tanpa migrasi native enum.
- Foreign key menggunakan `restrictOnDelete` untuk menjaga riwayat domain; `accepted_by` memakai `nullOnDelete` agar riwayat handover tidak memblokir penghapusan user legacy.
- MySQL menambahkan `CHECK` untuk urutan range, formula quantity, batas online usage, dan nilai state/reason. SQLite development tetap dilindungi oleh action domain karena SQLite tidak menerima `ALTER TABLE ... ADD CONSTRAINT` yang sama.
- `DB_CONNECTION=sqlite` dan seluruh konfigurasi infrastructure tidak diubah. Enam migrasi Phase 04 telah dijalankan sukses pada `database/database.sqlite` tanpa menghapus data Phase 03.

## Migration

- `2026_08_30_093712_create_skpd_inventory_locks_table`
- `2026_08_30_093713_create_skpd_boxes_table`
- `2026_08_30_093715_create_skpd_allocations_table`
- `2026_08_30_093716_create_baps_table`
- `2026_08_30_093718_create_bap_usage_segments_table`
- `2026_08_30_093719_create_bap_cancellations_table`

## Model Eloquent

Model Eloquent baru memakai typed relationship, casts enum/tanggal, mass-assignment allow-list, dan relasi audit polimorfik. User dan Loket diperluas hanya dengan relationship domain; tidak ada perubahan perilaku autentikasi atau otorisasi Phase 03.

## Factory dan Seeder

- Factory ditambahkan untuk `SkpdBox`, `SkpdAllocation`, dan `Bap` untuk data test/development.
- Tidak ada seeder domain baru agar tidak membuat data yang dapat disalahartikan sebagai stok produksi.
- Role dan Loket existing tetap dipakai; tidak ada perubahan seed role.

## Index dan Constraint

- Unique: nomor Box, kombinasi `loket_id + service_date` BAP, batas awal/akhir BAP, dan nomeratur cancellation.
- Index: range Box, Box/status allocation, Loket/status allocation, range allocation, Loket/akhir BAP, status/tanggal BAP, serta range segment pemakaian.
- Foreign key: seluruh relasi Box, Loket, User, BAP, dan segment.
- Database membantu validasi bentuk data; overlap interval, satu-Loket-per-Box, coverage BAP, dan sequence tetap berada di application layer karena constraint interval portable tidak tersedia sebagai unique index biasa.

## Concurrency Strategy

Semua operasi tulis domain memakai `DB::transaction(..., attempts: 3)` lalu `lockForUpdate()` terhadap row tunggal `skpd_inventory_locks.id = 1`. Setelah lock diperoleh, action juga mengunci row Box, Allocation, atau BAP yang relevan sebelum memeriksa dan menyimpan data. Strategi ini menserialisasi register Box, allocation, handover, pembuatan BAP, cancellation, dan submission sehingga hanya satu transaksi yang dapat memakai range/urutan yang sama.

## Testing

PASS — `php artisan test --compact`: 59 test, 237 assertion.

Coverage `SkpdInventoryTest` mencakup range Box valid/invalid/duplicate/overlap/gap, partial allocation, ownership satu Loket, range di luar Box, overlap allocation, handover pending/accepted/cancelled, BAP lintas allocation kontigu, formula total, online inclusive, BAP per hari, zero-usage day, sequence jump, BAP di luar allocation, cancellation valid/invalid/duplicate/reuse, dan state BAP submission.

PASS — `vendor/bin/phpstan analyse --configuration=phpstan.neon --no-progress` tanpa error.

PASS — `vendor/bin/pint --dirty --format agent`.

`npm run check` dan `npm run types:check` tidak dijalankan karena tidak ada perubahan frontend.

## File yang Ditambahkan

- Enum domain: `app/SkpdBoxStatus.php`, `app/SkpdAllocationStatus.php`, `app/BapStatus.php`, `app/BapCancellationReason.php`
- Action domain: `app/Actions/SkpdInventory/`
- Model: `app/Models/SkpdBox.php`, `SkpdAllocation.php`, `Bap.php`, `BapUsageSegment.php`, `BapCancellation.php`
- Factory: `database/factories/SkpdBoxFactory.php`, `SkpdAllocationFactory.php`, `BapFactory.php`
- Enam migration Phase 04 pada `database/migrations/`
- Test: `tests/Feature/SkpdInventoryTest.php`
- Rule durable: `.ai/rules/skpd-inventory.md`

## File yang Diubah

- `PROJECT_STATUS.md`
- `app/Models/AuditLog.php`
- `app/Models/User.php`
- `app/Models/Loket.php`
- `.ai/rules/index.md`

## File yang Dihapus

Tidak ada.

## Dependency

Tidak ada dependency yang ditambah atau dihapus.

## Konfigurasi

Tidak ada konfigurasi environment, database connection, authentication, Fortify, atau frontend yang diubah.

## Known Issues

- Tidak ada server MySQL target yang dikonfigurasi pada workspace ini, sehingga DDL MySQL dan locking paralel belum diuji langsung terhadap server target. Migrasi dan seluruh test berjalan pada SQLite development/test database.
- Tidak ada browser E2E karena Phase 04 tidak membuat UI bisnis dan plugin browser tidak tersedia.

## Technical Debt

- `waiting_verification`, verification berjenjang, clarification, finalisasi Bendahara Barang, dan bulk sign-off belum diimplementasikan; hanya state foundation yang tersedia.
- Pemisahan reporting antara SIGNAL dan PRO NTT belum dimodelkan karena kebutuhan pelaporan belum diputuskan. Saat ini hanya total `online_usage_count` yang inklusif.
- Master alasan batal/rusak belum dibuat; reason saat ini enum `cancelled` atau `damaged` dengan description opsional.
- Validasi konkurensi nyata pada MySQL perlu dijalankan di environment target sebelum deployment.

## Open Questions

- Apakah SIGNAL dan PRO NTT harus disimpan sebagai channel terpisah untuk laporan?
- Apakah alasan batal/rusak harus menjadi master data yang dikelola Superadmin?
- Siapa yang berwenang membatalkan allocation pending selain pembuatnya, dan apakah allocation accepted dapat ditarik kembali melalui workflow khusus?
- Bagaimana kebijakan jika Box fisik resmi diterima dengan gap nomeratur yang membutuhkan justifikasi?

## Keputusan Teknis

- Nomeratur disimpan sebagai integer dan diformat 7 digit saat presentasi.
- Range/ledger dipilih di atas record nomeratur individual.
- Distribution digabung ke allocation untuk satu sumber lifecycle dan audit.
- BAP dapat memiliki beberapa usage segment agar pemakaian lintas allocation kontigu tetap akurat.
- Inventory dihitung dari ledger, bukan kolom stok mutable.
- Lock transaksi global yang kecil digunakan untuk menjaga operasi interval yang tidak dapat ditutup oleh unique index biasa.
- AuditLog Phase 03 dipakai ulang melalui relasi polimorfik.

## Keputusan Bisnis

- Satu nomeratur adalah satu set SKPD lima lembar.
- Satu Box tidak boleh dibagi ke beberapa Loket; partial allocation hanya boleh kepada Loket yang sama.
- Allocation pending bukan persediaan aktif Loket.
- Satu BAP hanya untuk satu Loket dan satu hari pelayanan; hari zero usage tidak menghasilkan BAP.
- Nomeratur batal/rusak tetap dikonsumsi dan tidak dapat digunakan ulang.
- SKPD online adalah bagian dari total pemakaian, bukan tambahan total.

## Handoff ke Phase 05

- Bangun route, policy, request validation, dan UI inventory/BAP di atas action `app/Actions/SkpdInventory/`; jangan menulis mutation langsung ke model.
- Pertahankan urutan lock `skpd_inventory_locks` sebelum lock entity domain saat menambah action baru.
- Gunakan `BapUsageSegment` untuk setiap perubahan yang perlu menghitung stok terpakai/sisa berdasarkan BAP.
- Implementasikan transisi `submitted → waiting_verification` dan workflow verifikasi berikutnya secara eksplisit, disertai policy dan audit baru.
- Jangan mengaktifkan kembali public registration, reset password email, email verification, passkey, atau 2FA tanpa keputusan bisnis dan test terpisah.
