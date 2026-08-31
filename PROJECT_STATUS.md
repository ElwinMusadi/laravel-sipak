# SIPAK — STATUS PROYEK

**Pembaruan terakhir:** 1 September 2026
**Fase saat ini:** PHASE 12 — BUKU KENDALI & REKAP ADMINISTRATIF BAP
**Status fase:** Selesai di SQLite lokal; Buku Kendali read-only dari BAP completed.

## Fase Saat Ini

PHASE 12 — BUKU KENDALI & REKAP ADMINISTRATIF BAP.

## Status Fase

Buku Kendali tersedia sebagai index administratif BAP Selesai Administratif. Tidak ada create, edit, delete, penerimaan, verifikasi, klarifikasi, atau mutasi inventaris.

## Ringkasan

- Sumber tunggal adalah BAP berstatus completed.
- Tidak ada tabel, migration, materialized ledger, atau nomor register baru.
- Buku Kendali adalah query/read model; detail tetap menggunakan BAP yang sudah ada.

## Buku Kendali

Halaman Buku Kendali ber-subtitle **Rekap administratif BAP yang telah selesai.** Daftar memuat tanggal pelayanan, nomor BAP, Loket, range nomeratur, total pemakaian, online, batal/rusak, penerima Bendahara Barang, dan status Selesai Administratif.

## Sumber Data

Sumber resmi:

- BAP completed: tanggal, Loket, range, total, online, status, dan metadata penerimaan;
- BAP usage segment: ledger range sumber; dan
- BAP cancellation: total batal/rusak.

Tidak ada salinan BAP yang perlu disinkronkan manual.

## Eligibility Data

Hanya completed yang masuk. Draft, submitted, under verification, needs clarification, waiting verification, verified phase 2, serta status non-final lain tidak masuk.

## Read-Only

Route Buku Kendali hanya GET. Tidak ada Action mutasi dan permintaan tidak mengubah BAP, usage segment, cancellation, allocation, verifikasi, klarifikasi, atau inventory.

## Rekap

Ringkasan mengikuti filter daftar:

- Total BAP;
- Total SKPD Terpakai;
- Total Online; dan
- Total Batal/Rusak.

Online tetap bagian dari total pemakaian. Batal/rusak tetap termasuk SKPD terpakai dan tidak dikurangkan.

## Rekap Per Loket

Belum dibuat. Blueprint tidak menetapkan bentuk rekap per Loket Phase 12. Filter Loket dan aggregate query sudah tersedia sebagai fondasi.

## Rekap Harian

Belum dibuat. Buku Kendali menampilkan tanggal pelayanan per baris dan filter rentang tanggal, tanpa membuat tabel rekap transaksi baru.

## Filter

- Tanggal mulai dan akhir menggunakan tanggal pelayanan BAP.
- Default adalah bulan berjalan pada timezone aplikasi terkonfigurasi.
- Loket berasal dari Loket yang tersedia.
- Validasi server memastikan format tanggal, urutan periode, dan id Loket.

Model date terserialisasi sebagai timestamp ISO pada SQLite lokal; query mengikuti convention Phase 11 memakai whereDate agar perbandingan tanggal bisnis konsisten.

## Search

Pencarian server-side mendukung nomor BAP dengan awalan #, nama Loket, dan nomeratur tujuh digit seperti 0582608. Nomeratur dicari terhadap range BAP dan presentation menggunakan formatter existing yang mempertahankan leading zero.

## Pagination

Pagination berjalan di server, 15 BAP per halaman, dengan query string dipertahankan. Tidak ada pemuatan seluruh BAP completed untuk filter atau pencarian client-side.

## Aggregation

Total BAP, pemakaian, dan online dihitung database-side dari query completed yang telah terfilter. Total batal/rusak dihitung dari BAP cancellation dengan subquery id BAP pada scope sama.

Tidak ada join langsung BAP ke usage segment dan cancellation saat aggregate. Satu BAP dengan banyak segment/cancellation tetap terhitung sekali untuk total BAP, pemakaian, dan online.

## Data Consistency

Total pemakaian memakai BAP total_usage, yaitu hasil domain calculation dari range saat BAP dibuat/diperbarui dan selaras dengan usage segment ledger. Online memakai online_usage_count; batal/rusak memakai count BAP cancellation.

Test membuktikan satu BAP dengan dua usage segment dan dua cancellation tetap menghasilkan total 13, online 5, dan batal/rusak 2.

## Traceability

Setiap baris menyediakan link Wayfinder ke detail BAP existing. Bendahara Barang dapat menelusuri usage segment, cancellation, verifikasi, klarifikasi, penerimaan, dan audit pada BAP sumber tanpa detail duplikat.

## Authorization

- Gate view-buku-kendali dan middleware route menjadi authorization server-side.
- Bendahara Barang dapat mengakses Buku Kendali.
- Petugas Loket, Petugas Penetapan, Petugas Verifikasi, dan Superadmin ditolak pada HTTP langsung.
- Permission Inertia hanya mengontrol menu; Gate tetap authority.

## Scope Loket

Buku Kendali hanya untuk Bendahara Barang yang melihat seluruh Loket pada implementasi aktual. Petugas Loket tidak memiliki akses sehingga tidak ada scope lintas Loket yang dapat dibypass. Aturan beberapa Bendahara menurut wilayah/Loket belum tersedia.

## Performance

- Loket dan penerima Bendahara Barang di-eager-load.
- Cancellation daftar memakai withCount.
- Aggregate tidak meng-hydrate seluruh dataset.
- Pagination database-side.
- Pengukuran query plan dan efektivitas index pada MySQL target belum dilakukan.

## UI/UX

Halaman memakai shadcn/ui existing, token Amber semantik, serta appearance Light/Dark aplikasi.

- Desktop menggunakan tabel informasi-padat.
- Mobile menggunakan kartu ringkas agar tidak memaksa tabel lebar.
- Kartu ringkasan responsif menampilkan empat metrik.
- Filter dan pencarian tersedia pada semua breakpoint.

Validasi browser interaktif desktop/mobile dan Light/Dark belum dilakukan karena plugin Browser Pest tidak terpasang.

## Navigation

Item **Buku Kendali** yang sudah ada pada grup SKPD diaktifkan dengan route dan permission server-derived. Tidak ada menu duplikat.

## Dashboard

Tidak ada dashboard atau metric baru. Dashboard Phase 11 tetap memuat pekerjaan penerimaan administratif.

## Route

- GET /buku-kendali — buku-kendali.index

Route dilindungi auth, active, dan Gate. Wayfinder telah diregenerasi; frontend memakai helper generated.

## Query / Service Layer

SkpdBukuKendaliController memegang read query kecil sesuai convention:

- completedBapQuery untuk scope completed, periode, Loket, dan search;
- summaryData untuk aggregate tanpa double count; dan
- bapData untuk prop presentasi.

Tidak ada Action domain karena Phase 12 tidak melakukan mutation.

## Database

Tidak ada migration atau tabel baru. Schema BAP, usage segment, cancellation, Loket, penerimaan administratif, serta index existing cukup untuk read model.

## Inventory Impact

Tidak ada dampak inventory. Buku Kendali tidak mengubah Box, Allocation, status allocation, usage segment, range, maupun stok derived.

## Source Immutability

Feature test membuktikan request Buku Kendali tidak mengubah raw attribute BAP, usage segment, atau cancellation, dan tidak mencatat audit log baru.

## Testing

### Feature Test

PASS — BukuKendaliTest: **10 test, 142 assertion**.

Cakupan: eligibility completed, filter periode/Loket, search BAP/Loket/nomeratur, pagination, aggregate tanpa double count, detail BAP, source immutability, leading-zero source presentation, dan authorization HTTP.

### Regression Test

PASS — BukuKendaliTest + BapAdministrativeReceiptWorkflowTest: **21 test, 269 assertion**.
PASS — seluruh suite: **144 test, 1.113 assertion**.

### npm run check

FAIL terbatas pada **12 berkas formatting pre-existing** di luar scope: app.tsx, app-sidebar-header.tsx, nav-user.tsx, two-factor-setup-modal.tsx, user-info.tsx, auth/login.tsx, baps/index.tsx, dashboard.tsx, allocation create/index, box index, dan users index. Berkas Buku Kendali sendiri PASS pada pemeriksaan terarah format dan lint.

### npm run types:check

PASS — TypeScript tanpa error.

### npm run build

PASS — Vite build dan generasi Wayfinder berhasil.

### PHPStan

PASS — 0 error.

### Pint

PASS — vendor/bin/pint --dirty --format agent.

### git diff --check

PASS — tidak ada whitespace error.

## Known Issues

- npm run check global gagal karena 12 berkas pre-existing di luar scope Phase 12.
- Browser manual desktop/mobile, Light/Dark, dan aksesibilitas interaktif belum tervalidasi.
- Query plan, index effectiveness, dan lock MySQL target belum tervalidasi; bukti saat ini dari SQLite lokal.

## Technical Debt

- Belum ada rekap per Loket atau rekap harian resmi.
- Belum ada nomor register Buku Kendali.
- Tidak ada export, PDF, Excel, CSV, atau print layout.
- Timezone aplikasi masih UTC. Filter memakai timezone aplikasi sebagaimana konfigurasi, tetapi timezone bisnis operasional belum diputuskan eksplisit.

## Open Questions

1. Apakah Buku Kendali memerlukan nomor register resmi?
   - Opsi: memakai id BAP sekarang atau format register bisnis.
   - Konsekuensi: perlu aturan penomoran, uniqueness, audit, dan backfill sebelum data baru.
2. Apakah Bendahara Barang dibatasi Loket/wilayah atau terdapat beberapa Bendahara?
   - Opsi: semua Loket seperti sekarang atau scope penugasan server-side.
   - Konsekuensi: Gate, query, navigasi, dan test authorization diperluas.
3. Apakah rekap per Loket dan rekap harian memiliki format operasional resmi?
   - Opsi: tetap filter/query atau menambah presentation read-only.
   - Konsekuensi: tidak perlu tabel baru, tetapi dimensi dan total perlu disahkan.
4. Apakah Buku Kendali memerlukan print atau export?
   - Opsi: digital-only atau output reporting Phase berikutnya.
   - Konsekuensi: PDF/Excel/CSV dan layout cetak tidak dibuat tanpa format serta authority.
5. Timezone bisnis apa untuk batas bulan/tanggal pelayanan?
   - Opsi: mempertahankan UTC atau menetapkan timezone operasional.
   - Konsekuensi: perubahan konfigurasi harus diuji terhadap histori dan batas periode BAP.

## Keputusan Teknis

- Buku Kendali adalah query/read model BAP completed, bukan ledger baru.
- Aggregate memakai total_usage dan online_usage_count yang sudah dibentuk domain, serta count cancellation dari source relation.
- Aggregate cancellation dipisah dari aggregate BAP agar tidak double count.
- Periode default bulan berjalan pada timezone aplikasi; tidak ada bulan hard-coded.
- Detail memakai BAP show existing melalui Wayfinder.
- Tidak ada migration karena schema current memadai.

## Keputusan Bisnis

- Terminologi status adalah **Selesai Administratif**, konsisten Phase 11.
- Hanya BAP completed menjadi administrasi Buku Kendali.
- Batal/rusak dan online tetap termasuk total SKPD terpakai.
- Bendahara Barang satu-satunya pengguna Phase 12 karena Blueprint tidak memberi basis akses operasional lain.

## Batasan Phase Berikutnya

Phase 12 tidak mencakup laporan bulanan final, closing, rekonsiliasi, koreksi BAP completed, print, PDF, Excel, CSV, export, nomor register, rekap per Loket resmi, atau rekap harian resmi.

## Handoff ke Phase 13

Data completed yang siap menjadi source reporting:

- tanggal pelayanan;
- nomor/id BAP;
- Loket;
- range nomeratur tujuh digit;
- total pemakaian;
- online;
- batal/rusak; dan
- metadata penerimaan Bendahara Barang.

Phase 13 hanya dapat dimulai setelah format, periode, audience, register, print/export, dan aturan bisnis reporting diberikan eksplisit.
