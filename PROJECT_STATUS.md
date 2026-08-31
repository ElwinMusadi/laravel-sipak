# SIPAK — STATUS PROYEK

**Pembaruan terakhir:** 31 Agustus 2026
**Fase saat ini:** PHASE 11 — PENERIMAAN & FINALISASI ADMINISTRATIF BENDAHARA BARANG
**Status fase:** Implementasi fungsional selesai dan tervalidasi pada SQLite lokal. Validasi browser interaktif serta perilaku lock pada MySQL target masih perlu dilakukan pada lingkungan target.

## Fase Saat Ini

PHASE 11 — PENERIMAAN & FINALISASI ADMINISTRATIF BENDAHARA BARANG.

## Status Fase

Bendahara Barang dapat menerima BAP yang sudah `verified_phase_2` menjadi `completed`. Proses ini adalah penerimaan/finalisasi administratif, bukan approval tambahan, tidak mengubah data sumber inventaris, dan tidak menambah reporting, PDF, Excel, rekonsiliasi, atau stock closing.

## Ringkasan

- Satu-satunya transisi baru adalah `verified_phase_2 → completed`.
- Hanya role `BendaharaBarang` yang dapat membuka antrean, detail administrasi, dan menjalankan penerimaan.
- Penerimaan menyimpan `received_by`, `received_at` dari server, serta `receipt_notes` opsional.
- Queue aktif hanya memuat BAP `verified_phase_2`; queue selesai adalah riwayat read-only BAP `completed`.
- BAP tetap memakai nomor aktual `#id`; sistem tidak mengarang format nomor BAP baru.

## Penerimaan BAP

Saat Bendahara Barang mengonfirmasi penerimaan, action domain mengunci BAP dan memvalidasi ulang bahwa:

- status masih `verified_phase_2`;
- terdapat hasil **lulus** Verifikasi Tahap 1 dan Tahap 2;
- tidak ada `bap_verifications` berstatus `in_progress`; dan
- tidak ada klarifikasi berstatus `waiting_response`, `responded`, atau `reopened`.

Metadata penerimaan dan event audit hanya ditulis setelah seluruh syarat tersebut terpenuhi. Tanggal dari browser (`received_at`, `receipt_date`) dan field sumber BAP ditolak oleh request validation.

## State Transition

| Dari               | Aksi                                           | Ke              |
| ------------------ | ---------------------------------------------- | --------------- |
| `verified_phase_2` | Bendahara Barang menerima secara administratif | `completed`     |
| `completed`        | Tidak ada transisi Phase 11                    | tetap read-only |

Tidak ada state approval/penetapan tambahan yang dibuat antara `verified_phase_2` dan `completed`.

## Role dan Authorization

- **Bendahara Barang:** melihat antrean administrasi, detail BAP yang eligible/selesai, serta menerima BAP eligible.
- **Petugas Loket, Petugas Penetapan, Petugas Verifikasi, dan Superadmin:** tidak dapat mengakses antrean maupun endpoint penerimaan melalui HTTP langsung.
- **Superadmin:** tetap memiliki akses monitoring BAP melalui policy BAP yang sudah ada, tetapi bukan pengguna operasional queue/penerimaan Phase 11.

Authorization berjalan berlapis melalui middleware route, Gate controller, dan validasi role/state di action.

## Concurrency

`ReceiveBapByBendaharaBarang` menggunakan `DB::transaction(..., attempts: 3)` dan `lockForUpdate()` pada BAP, catatan verifikasi, serta klarifikasi yang relevan. Setelah lock, state dan prerequisite dibaca ulang; penerimaan kedua ditolak dan tidak membuat audit kedua.

Pengujian race/lock aktual pada MySQL target belum dilakukan. Validasi saat ini membuktikan pengaman transaksional dan penolakan sequential pada SQLite lokal.

## Source Immutability

Penerimaan administratif tidak mengubah:

- identitas, tanggal, rentang nomeratur, total penggunaan, atau jumlah online BAP;
- `BapUsageSegment` dan `SkpdAllocation` sumber;
- nomeratur batal/rusak;
- attempt verifikasi, checklist, discrepancy; maupun
- request, response, dan resolution klarifikasi yang telah menjadi riwayat.

Perubahan yang diizinkan hanya status BAP, metadata penerimaan, dan satu event audit baru.

## Queue dan Detail BAP

Antrean Bendahara Barang menyediakan filter tanggal pelayanan, Loket, status (menunggu/selesai), dan pencarian nomor BAP (`#id`), Loket, atau nomor dalam rentang nomeratur. Tabel menampilkan BAP, tanggal, Loket, rentang nomeratur, total penggunaan, batal/rusak, online, verifier Tahap 1/2, waktu selesai Tahap 2, durasi tunggu atau penerima, serta status administratif.

Detail BAP menyediakan tab Ringkasan, Riwayat Verifikasi, Klarifikasi, dan Riwayat Audit. Ringkasan memuat identitas, source usage segment, dan batal/rusak; riwayat verifikasi memuat checklist serta discrepancy per attempt; klarifikasi memuat request/response/resolution; dan audit memuat aktor serta timestamp.

Dialog penerimaan menyatakan bahwa Verifikasi Tahap 1, Verifikasi Tahap 2, dan seluruh klarifikasi harus selesai. Dialog juga menegaskan bahwa penerimaan tidak mengubah data sumber inventaris.

## Audit

Event baru:

- `bap_administration.received` — merekam actor Bendahara Barang, timestamp server, status lama `verified_phase_2`, status baru `completed`, penerima, waktu penerimaan, dan catatan opsional.

Riwayat audit BAP yang sudah ada tetap tidak diubah.

## Dashboard dan Navigation

- Dashboard Bendahara Barang menampilkan metrik **BAP Hari Ini** dan **Menunggu Penerimaan**, serta work item **BAP menunggu penerimaan** dan **BAP selesai administratif**.
- Navigation menambahkan **Administrasi BAP** di grup Administrasi hanya bila permission server-derived `viewBapAdministrations` tersedia.
- Status `completed` menggunakan label Bahasa Indonesia **Selesai Administratif** pada badge dan filter antrean administrasi.

## Route

- `GET /bap-administrations` — antrean Bendahara Barang; mendukung filter dan queue selesai.
- `GET /bap-administrations/{bap}` — detail read-only BAP `verified_phase_2` atau `completed` untuk Bendahara Barang.
- `POST /bap-administrations/{bap}/receive` — penerimaan administratif BAP eligible.

Wayfinder telah diregenerasi untuk route tersebut dan frontend memakai helper Wayfinder, bukan URL hard-coded.

## Action / Domain Layer

- `ReceiveBapByBendaharaBarang` mengunci, memvalidasi ulang, mentransisikan BAP, menyimpan metadata penerimaan, dan mencatat audit secara atomik.
- `ReceiveBapAdministrativeReceiptRequest` menerima hanya `receipt_notes` opsional serta menolak timestamp penerimaan dan field sumber yang dikirim klien.
- `BapStatus` mengenal `Completed` sebagai state terminal dengan transition tunggal dari `VerifiedPhase2`.

## Database

Migration `2026_08_31_141943_add_administrative_receipt_to_baps_table` menambahkan:

- `baps.received_by` nullable foreign key ke `users` dengan `restrictOnDelete()`;
- `baps.received_at` nullable timestamp;
- `baps.receipt_notes` nullable text; dan
- nilai `completed` pada CHECK constraint status MySQL.

Rollback mengembalikan status `completed` ke `verified_phase_2`, mengembalikan CHECK constraint lama, lalu menghapus metadata penerimaan. Dengan demikian rollback eksplisit membuang metadata finalisasi yang memang tidak dapat direpresentasikan pada skema lama.

## Inventory Impact

Tidak ada mutasi Box, Allocation, inventory ledger, BAP usage segment, cancellation, atau stock. Phase 11 hanya menambah finalisasi administratif pada BAP yang telah lulus verifikasi.

## Buku Kendali

Belum ada fondasi domain, route, atau data Buku Kendali yang terimplementasi. Item navigation yang masih planned tidak dipakai sebagai pengganti ledger atau laporan. Data final BAP untuk fase pelaporan berikutnya tersedia lewat BAP `completed`: tanggal pelayanan, Loket, rentang nomeratur, total penggunaan, online, dan batal/rusak.

## Testing

### Feature Test

PASS — `php artisan test --compact tests/Feature/BapAdministrativeReceiptWorkflowTest.php`: **11 test, 127 assertion**. Cakupan meliputi antrean eligible/completed, detail, penerimaan dan audit, metadata server-side, penolakan field klien, source immutability, duplicate receipt, prerequisite/clarification aktif, dan HTTP authorization lintas role.

### Validasi lanjutan

- PASS — `php artisan test --compact`: **134 test, 971 assertion**.
- PASS — `npm run types:check`: TypeScript tanpa error.
- PASS — `npm run build`: Vite build dan generasi type Wayfinder berhasil.
- PASS — `vendor/bin/phpstan analyse --memory-limit=1G`: 0 error.
- PASS — `vendor/bin/pint --dirty --format agent` dan format PHP file Phase 11.
- PASS — `git diff --check`: tidak ada whitespace error.
- PASS — migration Phase 11 diterapkan pada SQLite lokal sebagai batch 7.

## Known Issues

- Review browser manual pada desktop/mobile serta Light/Dark Mode belum tersedia di lingkungan ini; type-check dan build bukan pengganti bukti visual interaktif.
- Constraint DDL dan race/lock MySQL belum divalidasi pada server MySQL target.
- `npm run check` global masih gagal hanya karena 12 berkas formatting pre-existing di luar scope: `resources/js/app.tsx`, `components/app-sidebar-header.tsx`, `components/nav-user.tsx`, `components/two-factor-setup-modal.tsx`, `components/user-info.tsx`, `pages/auth/login.tsx`, `pages/baps/index.tsx`, `pages/dashboard.tsx`, `pages/skpd/allocations/create.tsx`, `pages/skpd/allocations/index.tsx`, `pages/skpd/boxes/index.tsx`, dan `pages/users/index.tsx`. Berkas frontend Phase 11 sendiri lulus format/lint saat diperiksa terpisah.

## Technical Debt

- Tidak ada notifikasi, SLA, attachment bukti fisik, atau eskalasi karena aturan bisnisnya belum diberikan.
- Tidak ada halaman register Buku Kendali atau reporting; data yang tersedia sengaja hanya menjadi input fase berikutnya.

## Open Questions

1. Apakah Bendahara Barang dapat menolak BAP, dan jika ya, apakah harus kembali ke klarifikasi atau ke status/record baru?
2. Apakah penerimaan `completed` dapat dibatalkan atau dibuka kembali, oleh siapa, dan bagaimana jejak audit/efek inventory-nya?
3. Apakah koreksi BAP setelah `completed` diperbolehkan, melalui mekanisme apa, dan apakah harus membuat BAP pengganti?
4. Apakah terdapat beberapa Bendahara Barang dengan scope Loket/wilayah tertentu, delegation, atau pemisahan tugas yang harus dibatasi server-side?
5. Apakah nomor/register Buku Kendali, aturan penomoran, dan proses pelaporan periodik perlu dibentuk dari BAP `completed`?

## Keputusan Teknis

- Phase 11 memakai transisi langsung `verified_phase_2 → completed` karena Phase 10 memang berhenti pada `verified_phase_2` dan brief Phase 11 secara eksplisit menempatkan penerimaan Bendahara Barang setelah kedua tahap verifikasi. Blueprint lama yang menyebut approval Kasie/Kepala tidak diimplementasikan sebagai state tambahan karena belum ada aturan Phase 11 yang menetapkannya; konflik ini harus diputuskan sebelum workflow tersebut diperluas.
- Timestamp penerimaan selalu berasal dari server, bukan input frontend.
- Penerimaan dikunci dan diverifikasi ulang pada domain layer; Gate frontend/presentation tidak diperlakukan sebagai authorization.
- Route frontend menggunakan Wayfinder, sedangkan Tabs memakai primitive shadcn/ui yang sudah ada tanpa menambah dependency.

## Keputusan Bisnis

- Istilah operasional yang digunakan adalah **Diterima Bendahara Barang** dan **Selesai Administratif**; tidak memakai istilah approved.
- BAP yang selesai administratif bersifat read-only dalam scope Phase 11.
- Tidak ada reporting, PDF/Excel, rekonsiliasi, stock closing, atau jurnal pada Phase 11.

## Handoff ke Phase 12

Phase 11 berhenti setelah finalisasi administratif. Jika Phase 12 dimulai dengan aturan bisnis eksplisit, gunakan BAP `completed` sebagai sumber pelaporan dengan dimensi tanggal pelayanan, Loket, rentang nomeratur, total penggunaan, penggunaan online, pembatalan/kerusakan, verifier, dan metadata penerimaan. Jangan membuat Buku Kendali, laporan, PDF, Excel, closing, atau perubahan sumber data sebelum aturan tersebut diberikan.
