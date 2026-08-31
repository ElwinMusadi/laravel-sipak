# SIPAK — STATUS PROYEK

**Pembaruan terakhir:** 1 September 2026
**Fase saat ini:** PHASE 13 — LAPORAN PEMAKAIAN SKPD
**Status fase:** Selesai di SQLite lokal; laporan adalah proyeksi read-only BAP selesai administratif.

## Fase Saat Ini

PHASE 13 — LAPORAN PEMAKAIAN SKPD.

## Status Fase

Laporan pemakaian periodik tersedia tanpa tabel laporan, ledger transaksi, atau proses closing baru. Data selalu dihitung kembali dari BAP yang selesai administratif saat laporan dibuka.

## Ringkasan

- Sumber tunggal adalah BAP berstatus `completed`.
- Laporan memakai bulan, tahun, dan filter Loket opsional.
- Rekap per Loket adalah tampilan operasional provisional, bukan format administrasi resmi.
- Detail BAP tetap menggunakan halaman BAP existing melalui Wayfinder.

## Laporan Pemakaian SKPD

Halaman **Laporan Pemakaian SKPD** ber-subtitle **Rekap pemakaian Bukti SKPD berdasarkan BAP yang telah selesai administratif.** Halaman bersifat read-only dan menampilkan periode, ringkasan, rekap per Loket, serta detail BAP terpaginasikan.

## Sumber Data

Sumber laporan adalah BAP `completed`, dengan data BAP berikut sebagai nilai laporan:

- tanggal pelayanan;
- Loket;
- range nomeratur;
- `total_usage`;
- `online_usage_count`; dan
- jumlah `BapCancellation`.

Usage segment tetap merupakan ledger range sumber BAP. Penerimaan administratif, verifikasi, dan klarifikasi tidak disalin atau diubah oleh laporan.

## Data Eligibility

Hanya BAP `completed` yang masuk. Draft, submitted, under verification, needs clarification, waiting reverification, waiting verification phase 2, under verification phase 2, dan `verified_phase_2` tidak masuk, walaupun telah melalui sebagian proses verifikasi.

## Periode

Periode memakai tanggal pelayanan BAP. Input bulan dan tahun diubah menjadi hari pertama sampai hari terakhir bulan secara inklusif, lalu diterapkan dengan `whereDate`. Default adalah bulan berjalan pada timezone aplikasi.

## Filter

Filter server-side tersedia untuk:

- bulan;
- tahun; dan
- Loket opsional.

Tidak ada search aggregate karena laporan berorientasi periode. Detail BAP dapat dipaginasikan 15 baris per halaman dan seluruh query filter dipertahankan dalam pagination.

## Summary

Ringkasan selalu berasal dari scope BAP `completed` yang sama dengan detail dan rekap Loket:

- Total BAP;
- SKPD Terpakai;
- Online; dan
- Batal/Rusak.

## Total Pemakaian

Total pemakaian adalah `sum(baps.total_usage)`. Nilai ini adalah total domain BAP yang telah dibentuk dari range/usage segment pada proses BAP; laporan tidak menghitung ulang atau mengubah range tersebut. Batal/rusak tidak dikurangkan dari total pemakaian.

## Online

Online adalah `sum(baps.online_usage_count)` dari BAP completed dan tetap merupakan bagian dari total pemakaian. Tidak ada field online baru maupun inferensi dari angka total.

## Batal/Rusak

Batal/rusak adalah `count(bap_cancellations)` untuk BAP pada scope laporan. Nilai tersebut dihitung terpisah dari aggregate BAP agar satu BAP dengan beberapa pembatalan tidak melipatgandakan total BAP, terpakai, atau online.

## Rekap Per Loket

Rekap per Loket menampilkan BAP, terpakai, online, dan batal/rusak. Desktop memakai tabel dengan total; mobile memakai kartu ringkas. Tombol **Detail BAP** menerapkan Loket yang dipilih pada laporan yang sama.

Rekap ini belum diklaim sebagai format resmi administrasi. Format resmi dan grouping wajib diputuskan sebelum ada print/export resmi.

## Rekap Harian

Belum dibuat. Blueprint tidak menetapkan grouping/format harian, sehingga Phase 13 tidak membuat tampilan tambahan tanpa keputusan bisnis.

## Rekap Nomeratur

Belum dibuat sebagai grouping terpisah. Detail BAP menampilkan range nomeratur dari source BAP dengan formatter tujuh digit, misalnya `0582608–0582617`, sehingga leading zero tetap terjaga dan sumber dapat ditelusuri.

## Aggregation

Aggregate dilakukan database-side:

- `count`, `sum(total_usage)`, dan `sum(online_usage_count)` dari query BAP completed;
- cancellation melalui query terpisah dengan subquery id BAP yang sama; dan
- rekap per Loket melalui grouping BAP dan grouping cancellation terpisah.

Tidak ada pemuatan semua BAP ke PHP untuk menjumlahkan laporan.

## Double Count Protection

Laporan tidak melakukan join langsung BAP ke usage segment dan cancellation pada query aggregate. Dengan demikian, satu BAP yang memiliki beberapa usage segment dan beberapa cancellation tetap dihitung sekali untuk BAP, total pemakaian, dan online; cancellation tetap dihitung sesuai jumlah record sebenarnya.

Feature test memakai BAP dengan dua usage segment dan tiga cancellation pada dua BAP. Hasilnya tetap Total BAP 2, pemakaian 30, online 8, dan batal/rusak 3.

## Traceability

Detail BAP menampilkan tanggal pelayanan, nomor BAP, Loket, range nomeratur, terpakai, online, dan batal/rusak. Tidak ada angka aggregate tanpa daftar BAP sumber.

## Drill-down

Setiap baris dan rekap Loket menyediakan navigasi ke detail yang relevan. Rekap Loket memperbarui filter laporan ke Loket tersebut; tombol detail BAP membuka halaman BAP existing yang sudah menampilkan usage segment, cancellation, verifikasi, klarifikasi, penerimaan, dan audit source.

## Authorization

Gate `view-laporan-pemakaian` dan middleware route melindungi HTTP langsung. Akses diberikan kepada Bendahara Barang dan Kepala UPTD sesuai Blueprint: Bendahara mengelola Pusat Laporan Bulanan dan Kepala UPTD memiliki Pusat Laporan Read-Only.

Petugas Loket, Petugas Penetapan, Petugas Verifikasi, dan Superadmin ditolak. Superadmin tidak otomatis diberi oversight karena policy laporan read-only existing tidak memberi akses tersebut.

## Scope Loket

Tidak ada Petugas Loket yang diberi akses laporan pada implementasi aktual, sehingga tidak ada scope lintas-Loket yang dapat dibypass. Bendahara Barang dan Kepala UPTD melihat laporan seluruh Loket. Jika akses Loket diperlukan di masa depan, Gate dan query wajib ditambah scope `loket_id` server-side beserta test cross-Loket.

## Performance

- Filter memakai index BAP existing `status, service_date`.
- Cancellation memakai index `bap_id` existing.
- Loket hanya dimuat untuk detail BAP yang sedang dipaginasi.
- Aggregate dan grouping berjalan di database.
- Tidak ada migration baru karena index SQLite existing sudah mendukung query Phase 13.

Query plan dan efektivitas index pada MySQL target belum diuji.

## Buku Kendali Consistency

Laporan dan Buku Kendali memakai scope sumber BAP `completed` yang sama. Feature test memeriksa dataset Agustus identik dan menghasilkan Total BAP 2, pemakaian 30, online 8, dan batal/rusak 1 pada kedua halaman.

## UI/UX

Halaman memakai primitive shadcn/ui dan token tema Amber existing.

- Ringkasan empat kartu responsif.
- Desktop memakai tabel rekap dan tabel detail dengan scroll internal terkontrol.
- Mobile memakai kartu Loket dan kartu BAP agar tabel lebar tidak dipaksakan ke layar kecil.
- Tidak ada warna hard-coded atau tindakan mutasi.

Build dan type check membuktikan halaman dapat dikompilasi. Review browser interaktif desktop/mobile dan Light/Dark belum tervalidasi karena Browser Pest tidak terpasang.

## Navigation

Item sidebar **Laporan → Pemakaian** aktif, menggunakan Wayfinder, dan hanya ditampilkan ketika permission server-derived `viewLaporanPemakaian` bernilai benar. Menu laporan lain tetap planned dan tidak diubah.

## Dashboard

Tidak ada dashboard, shortcut, maupun metric baru karena Blueprint tidak memberi requirement dashboard Phase 13 yang spesifik.

## Route

- `GET /laporan/pemakaian` — `laporan-pemakaian.index`

Route berada di dalam middleware `auth`, `active`, dan `can:view-laporan-pemakaian`. Wayfinder dibuat ulang dengan varian form proyek.

## Query / Service Layer

`SkpdLaporanPemakaianController` mengikuti convention read-query Phase 12:

- `completedBapQuery` menentukan eligibility dan periode;
- `summaryData` menghitung summary;
- `loketRecapData` menghitung grouping Loket tanpa double count; dan
- `bapData` membentuk prop detail source.

Tidak ada Action domain baru karena laporan tidak melakukan mutation.

## Database

Tidak ada migration, tabel `monthly_reports`, `report_entries`, `report_snapshots`, atau duplicate transaction ledger. Schema BAP, usage segment, cancellation, Loket, dan receipt existing digunakan langsung.

## Inventory Impact

Tidak ada dampak inventaris. Laporan tidak mengubah Box, Allocation, status Allocation, usage segment, range nomeratur, atau stok derived.

## Source Immutability

Feature test membuktikan GET laporan tidak mengubah raw attribute BAP, usage segment, cancellation, verification, clarification, maupun metadata penerimaan administratif.

## Export

Tidak dibuat pada Phase 13. Blueprint menyebut export PDF/Excel secara umum, tetapi tidak memberi format administratif dan instruksi quality gate Phase 13 secara eksplisit melarang PDF, Excel, serta CSV. Konflik scope ini ditunda untuk keputusan Phase berikutnya.

## Print

Tidak dibuat. Tidak ada format print administrasi resmi yang dapat diimplementasikan tanpa mengarang format.

## Monthly Closing

Tidak dibuat. Tidak ada closing bulan, lock periode, reopen period, stock closing, atau snapshot laporan.

## Testing

### Feature Test

PASS — `LaporanPemakaianSkpdTest`: **10 test, 126 assertion**.

Cakupan: eligibility completed, batas tanggal bulan/tahun, filter Loket, summary, rekap Loket, aggregate tanpa double count, empty period, consistency Buku Kendali, traceability BAP, leading-zero source, immutability, dan authorization HTTP.

### Regression Test

PASS — seluruh suite: **154 test, 1.239 assertion**.

### npm run check

FAIL terbatas pada **12 berkas formatting pre-existing** di luar scope: `app.tsx`, `app-sidebar-header.tsx`, `nav-user.tsx`, `two-factor-setup-modal.tsx`, `user-info.tsx`, `auth/login.tsx`, `baps/index.tsx`, `dashboard.tsx`, `allocations/create.tsx`, `allocations/index.tsx`, `boxes/index.tsx`, dan `users/index.tsx`. Berkas `PROJECT_STATUS.md` dan halaman laporan Phase 13 lulus pemeriksaan terarah.

### npm run types:check

PASS — TypeScript tanpa error.

### npm run build

PASS — Vite build, route/action Wayfinder, serta halaman `laporan-pemakaian` berhasil dibangun.

### PHPStan

PASS — 0 error.

### Pint

PASS — `vendor/bin/pint --dirty --format agent`.

### git diff --check

PASS — tidak ada whitespace error.

## Known Issues

- `npm run check` global memiliki formatting pre-existing di luar scope.
- Browser interaktif desktop/mobile, Light/Dark, dan aksesibilitas belum tervalidasi karena Browser Pest tidak tersedia.
- Query plan dan index effectiveness pada MySQL target belum tervalidasi; bukti query saat ini berasal dari SQLite lokal.

## Technical Debt

- Belum ada grouping/format laporan resmi per Loket, harian, atau nomeratur.
- Belum ada nomor register laporan.
- Timezone bisnis operasional belum diputuskan; konfigurasi aplikasi masih UTC.
- Belum ada export dan print karena format administratif belum disahkan.

## Open Questions

1. Apakah rekap per Loket, harian, dan nomeratur harus mengikuti format administrasi resmi?
    - Opsi: mempertahankan tampilan provisional atau mengesahkan layout/dimensi resmi.
    - Dampak: menentukan grouping, total, print, export, dan test kontrak laporan berikutnya.
2. Timezone bisnis apa yang menjadi batas periode operasional?
    - Opsi: mempertahankan UTC aplikasi atau menetapkan timezone operasional seperti Asia/Makassar.
    - Dampak: dapat mengubah BAP yang masuk ke batas hari/bulan dan perlu uji histori.
3. Apakah PDF/Excel harus masuk ke fase reporting berikutnya meskipun Blueprint mencantumkan export secara umum?
    - Opsi: menetapkan format resmi lebih dahulu atau membangun export non-resmi.
    - Dampak: format, otorisasi, audit output, dan mekanisme generation harus diputuskan sebelum implementasi.
4. Apakah Petugas Loket perlu akses laporan yang dibatasi Loket?
    - Opsi: tetap hanya Bendahara/Kepala UPTD atau memberi akses Loket scoped.
    - Dampak: Gate, query server-side, navigasi, dan test isolasi Loket berubah.

## Keputusan Teknis

- Laporan adalah query/read model BAP completed, bukan ledger atau snapshot.
- Total BAP, pemakaian, dan online dihitung dari BAP; cancellation dihitung dengan aggregate terpisah.
- Periode menggunakan `service_date` dan batas bulan inklusif pada timezone aplikasi.
- Rekap Loket memakai grouping BAP dan cancellation terpisah untuk mencegah Cartesian double count.
- Detail menggunakan BAP existing melalui Wayfinder.
- Tidak ada migration karena schema/index SQLite existing memadai untuk scope ini.

## Keputusan Bisnis

- Terminologi status final adalah **Selesai Administratif** (`completed`).
- Online dan batal/rusak tetap termasuk total SKPD terpakai.
- Bendahara Barang dan Kepala UPTD dapat melihat laporan read-only berdasarkan Information Architecture Blueprint.
- Rekap per Loket ditampilkan sebagai tampilan provisional, bukan klaim format resmi.

## Batasan Phase Berikutnya

Phase 13 tidak mencakup PDF, Excel, CSV, print layout resmi, closing, lock/reopen periode, stock closing, inventaris mutation, BAP mutation, verification mutation, clarification mutation, duplicate ledger, maupun tabel snapshot laporan.

## Handoff ke Phase 14

Phase berikutnya perlu memutuskan format laporan resmi, grouping harian/nomeratur, nomor register, timezone bisnis, audience Loket, serta kebijakan dan format export/print sebelum implementasi output resmi dimulai.
