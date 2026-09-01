# SIPAK — STATUS PROYEK

**Pembaruan terakhir:** 1 September 2026
**Fase saat ini:** PHASE 14 — OUTPUT DOKUMEN, PDF, EXCEL & PRINT
**Status fase:** Selesai di SQLite lokal. Output laporan adalah proyeksi read-only dari BAP selesai administratif dan tidak diklaim sebagai dokumen administrasi resmi.

## Fase Saat Ini

PHASE 14 — OUTPUT DOKUMEN, PDF, EXCEL & PRINT.

## Status Fase

PDF, XLSX, dan print browser tersedia untuk **Laporan Pemakaian SKPD**. Ketiganya memakai filter bulan, tahun, dan Loket yang sama, serta sumber BAP `completed` yang sama dengan halaman web Phase 13.

## Ringkasan

- Output PDF dan Excel dihasilkan saat diminta; tidak ada tabel report, snapshot, atau penyimpanan file baru.
- PDF diberi label **Laporan Sistem**, bukan format dokumen administrasi resmi.
- Excel memiliki sheet **Ringkasan**, **Rekap Loket**, dan **Detail BAP**.
- Print memakai `window.print()` pada halaman laporan yang sudah terproteksi.
- Tidak ada mutasi BAP, usage segment, cancellation, verifikasi, klarifikasi, penerimaan, Box, atau Allocation.

## Laporan Pemakaian SKPD

Halaman **Laporan Pemakaian SKPD** tetap read-only dan menampilkan periode, ringkasan, rekap per Loket, serta detail BAP terpaginasikan. Header halaman sekarang menyediakan tombol **PDF**, **Excel**, dan **Cetak**.

## Sumber Data

Sumber tunggal laporan dan seluruh output adalah BAP berstatus `completed`. Nilai yang digunakan:

- `service_date`;
- Loket;
- `numerator_start` dan `numerator_end`;
- `total_usage`;
- `online_usage_count`; dan
- jumlah `BapCancellation`.

`BapUsageSegment` tetap merupakan ledger range sumber nilai BAP. Status `completed` adalah bukti finalitas penerimaan administratif yang dipakai untuk eligibility. Output tidak menghitung ulang range, tidak mengubah receipt, dan tidak membuat data turunan yang dapat dimutasi.

## Data Eligibility

Hanya BAP `completed` yang masuk. Draft, submitted, seluruh status verifikasi/klarifikasi, dan `verified_phase_2` tidak masuk.

## Periode

Periode memakai `service_date`. Bulan dan tahun diubah menjadi hari pertama sampai hari terakhir bulan secara inklusif dengan `whereDate`. Default tetap bulan berjalan pada timezone aplikasi.

## Filter

Filter server-side tersedia untuk bulan, tahun, dan Loket opsional. Halaman web, PDF, dan XLSX menerima kontrak filter yang sama. Pagination halaman web mempertahankan filter; PDF dan XLSX selalu memuat seluruh BAP yang eligible dalam filter, bukan hanya halaman pagination aktif.

## Summary

Ringkasan dari scope BAP yang sama menampilkan:

- Total BAP;
- SKPD Terpakai;
- Online; dan
- Batal/Rusak.

## Total Pemakaian

Total pemakaian adalah `sum(baps.total_usage)`. Nilai tersebut sudah dibentuk oleh domain BAP dari range/usage segment, sehingga reporting tidak membangun atau memodifikasi ledger baru. Batal/rusak tidak dikurangkan dari total pemakaian.

## Online

Online adalah `sum(baps.online_usage_count)` dari BAP completed dan tetap bagian dari total SKPD terpakai. Tidak ada inferensi atau field online baru.

## Batal/Rusak

Batal/rusak adalah `count(bap_cancellations)` untuk BAP dalam scope laporan. Nilai dihitung terpisah dari aggregate BAP agar satu BAP dengan beberapa cancellation tidak melipatgandakan total BAP, terpakai, atau online.

## Rekap Per Loket

Rekap per Loket menampilkan BAP, terpakai, online, dan batal/rusak. Desktop memakai tabel dengan total; mobile memakai kartu ringkas. Tombol **Detail BAP** menerapkan filter Loket pada laporan yang sama.

Rekap ini adalah tampilan operasional sistem, bukan format administrasi resmi.

## Rekap Harian

Belum dibuat. Blueprint tidak menetapkan grouping dan format harian.

## Rekap Nomeratur

Belum dibuat sebagai grouping terpisah. Detail BAP tetap menampilkan range sumber dengan formatter tujuh digit, misalnya `0582608–0582617`.

## Aggregation

`SkpdLaporanPemakaianQuery` menjadi query layer bersama untuk halaman web, PDF, dan XLSX:

- scope BAP `completed`, periode, dan Loket ditentukan sekali;
- `count`, `sum(total_usage)`, dan `sum(online_usage_count)` dijalankan database-side;
- cancellation memakai subquery ID BAP yang sama; dan
- rekap Loket mengelompokkan BAP dan cancellation secara terpisah.

Tidak ada pemuatan seluruh BAP ke PHP untuk menghitung aggregate.

## Double Count Protection

Aggregate tidak melakukan join langsung BAP ke usage segment dan cancellation. Satu BAP dengan beberapa usage segment atau cancellation tetap dihitung sekali untuk BAP, total pemakaian, dan online; cancellation dihitung sesuai jumlah record sebenarnya.

## Traceability

Detail BAP pada web, PDF, dan XLSX memuat tanggal pelayanan, nomor BAP, Loket, range nomeratur, terpakai, online, dan batal/rusak. XLSX menyimpan nomeratur awal/akhir sebagai teks dengan format `%07d` agar leading zero tidak hilang.

## Drill-down

Detail web tetap menggunakan halaman BAP existing melalui Wayfinder. Output tidak menambah endpoint detail BAP baru.

## Authorization

Gate `view-laporan-pemakaian` dan middleware route melindungi halaman serta endpoint PDF/XLSX secara langsung. Akses tetap diberikan hanya kepada Bendahara Barang dan Kepala UPTD. Petugas Loket, Petugas Penetapan, Petugas Verifikasi, dan Superadmin ditolak.

Tombol presentasi tidak menjadi sumber otorisasi; endpoint mengulang Gate pada server.

## Scope Loket

Bendahara Barang dan Kepala UPTD melihat seluruh Loket. Tidak ada Petugas Loket yang diberi akses laporan pada implementasi aktual. Jika akses scoped Loket diperlukan kemudian, Gate dan query wajib diberi scope `loket_id` server-side beserta test isolasi lintas-Loket.

## Performance

- Filter memakai index BAP existing `status, service_date`.
- Cancellation memakai index `bap_id` existing.
- Query detail memakai eager load Loket dan `withCount('cancellations')`; XLSX memakai `FromQuery` untuk menulis detail secara chunked.
- Aggregate dan grouping tetap berjalan di database.
- Tidak ada migration atau index baru.

Query plan dan efektivitas index pada MySQL target belum diuji.

## Buku Kendali Consistency

Laporan, PDF, dan XLSX memakai scope BAP completed yang sama dengan Buku Kendali. Feature test laporan mempertahankan dataset pembuktian BAP 2, pemakaian 30, online 8, dan batal/rusak 3 tanpa double count.

## PDF

`GET /laporan/pemakaian/pdf` menghasilkan unduhan PDF A4 landscape bernama `laporan-pemakaian-skpd-{bulan}-{tahun}.pdf`.

- Menggunakan `barryvdh/laravel-dompdf` 3.1.2 dan view Blade `pdf.laporan-pemakaian`.
- Memakai lambang Pemprov NTT existing dari `public/images/logo-pemprov-ntt.png` tanpa membuat ulang aset.
- Header memuat SIPAK-SKPD, **Sistem Informasi Pemakaian Bukti SKPD**, dan **UPTD Pendapatan Daerah Wilayah Kota Kupang**.
- Memuat periode, Loket bila dipilih, waktu pembuatan, ringkasan, rekap Loket, dan detail BAP.
- Footer menyatakan bahwa hasil adalah laporan sistem dari data BAP final dan bukan format dokumen administrasi resmi.

## Excel

`GET /laporan/pemakaian/excel` menghasilkan unduhan XLSX bernama `laporan-pemakaian-skpd-{bulan}-{tahun}.xlsx`.

- Menggunakan `maatwebsite/excel` 4.0.2.
- **Ringkasan** memuat periode/Loket dan empat nilai total.
- **Rekap Loket** memuat tabel Loket, Total BAP, Terpakai, Online, dan Batal/Rusak dengan filter dan freeze pane.
- **Detail BAP** memuat BAP sumber dengan heading, filter, freeze pane, dan auto-filter.
- `WithStrictNullComparison` memastikan nilai nol benar-benar keluar sebagai `0`, bukan sel kosong.
- Kolom nomeratur awal/akhir diformat sebagai teks Excel untuk menjaga tujuh digit.

## Print

Tombol **Cetak** memanggil `window.print()` pada halaman laporan yang sedang difilter. CSS `@media print`:

- menyembunyikan sidebar, header aplikasi, filter, tombol aksi, kartu mobile, dan pagination;
- menampilkan tabel desktop responsif sebagai tabel print;
- menyembunyikan kolom aksi; dan
- memakai A4 landscape, header tabel berulang, serta mencegah pemisahan baris tabel.

Print adalah keluaran halaman web saat ini. Detail BAP yang tercetak mengikuti halaman pagination yang sedang dibuka; gunakan PDF atau XLSX untuk seluruh detail filter.

## Status Dokumen

Tidak ada format resmi, nomor register, tanda tangan, QR, barcode, layout kertas, atau approval dokumen yang disahkan oleh Blueprint. Karena itu semua keluaran diberi status **Laporan Sistem**, bukan dokumen administrasi resmi.

## Export Audit

Tidak ada audit trail khusus setiap kali PDF/XLSX dihasilkan. Sistem audit existing melekat pada record domain yang diaudit, sedangkan Blueprint belum menetapkan kebutuhan actor, waktu, hash file, retensi, atau register keluaran. Keputusan tersebut diperlukan sebelum audit export dibuat.

## BAP Document Output

Tidak dibuat PDF/print BAP individual. Blueprint menyebut print/export pada detail BAP, tetapi implementasi aktual belum memiliki Slide-over detail BAP maupun format BAP resmi yang dapat direplikasi tanpa mengarang aturan dokumen.

## Inventory Report

Tidak dibuat laporan persediaan bulanan atau historical month-end. Inventory saat ini derived dari ledger/range aktif dan tidak memiliki kebijakan closing/snapshot yang cukup untuk merekonstruksi saldo historis secara sah.

## UI/UX

Halaman mempertahankan primitive shadcn/ui dan token tema Amber existing.

- Tombol PDF, Excel, dan Cetak berada di header laporan dan responsif.
- Filter tetap tidak tercetak.
- Tabel desktop dipakai untuk print; kartu mobile dan tindakan navigasi disembunyikan.
- Tidak ada hard-coded URL frontend; PDF dan XLSX memakai helper Wayfinder yang digenerate.

Build membuktikan halaman dapat dikompilasi. Review browser interaktif desktop/mobile serta Light/Dark belum tervalidasi karena Browser Pest tidak tersedia.

## Navigation

Item sidebar **Laporan → Pemakaian** tidak berubah dan tetap menggunakan permission server-derived `viewLaporanPemakaian`. Tidak ada menu laporan baru.

## Dashboard

Tidak ada dashboard, shortcut, atau metric baru.

## Route

- `GET /laporan/pemakaian` — `laporan-pemakaian.index`
- `GET /laporan/pemakaian/pdf` — `laporan-pemakaian.pdf`
- `GET /laporan/pemakaian/excel` — `laporan-pemakaian.excel`

Ketiganya berada dalam middleware `auth`, `active`, dan `can:view-laporan-pemakaian`. Wayfinder digenerate ulang setelah route ditambahkan.

## Query / Service Layer

`SkpdLaporanPemakaianController` menangani HTTP, validasi filter, authorization, dan generation output. `SkpdLaporanPemakaianQuery` menangani query read-only bersama. Tidak ada Action domain baru karena output tidak melakukan mutation.

## Database

Tidak ada migration, tabel `monthly_reports`, `report_entries`, `report_snapshots`, duplicate ledger, atau file storage baru. Schema BAP, usage segment, cancellation, Loket, dan receipt existing digunakan langsung.

## Inventory Impact

Tidak ada dampak inventaris. PDF, XLSX, dan print tidak mengubah Box, Allocation, status Allocation, usage segment, range nomeratur, atau stok derived.

## Source Immutability

Feature test membuktikan request PDF dan XLSX tidak mengubah raw attribute BAP, usage segment, cancellation, verification, clarification, maupun metadata penerimaan administratif.

## Monthly Closing

Tidak dibuat closing bulan, lock/reopen periode, stock closing, snapshot laporan, atau rekonsiliasi.

## Testing

### Feature Test

PASS — `LaporanPemakaianOutputTest`: **7 test, 45 assertion**.

Cakupan: PDF valid dan download, workbook XLSX yang dibuka kembali, tiga sheet, ringkasan, rekap, leading-zero nomeratur, filter bulan/tahun/Loket, empty period, source immutability, dan denial HTTP langsung.

PASS — laporan Phase 13 dan output Phase 14 bersama: **17 test, 171 assertion**.

### Regression Test

PASS — seluruh Unit/Feature existing dijalankan dalam empat batch deterministik: **160 test, 1.283 assertion**.

### Composer

PASS — `composer validate --strict`. Dependensi direct baru terpasang: `barryvdh/laravel-dompdf` 3.1.2 dan `maatwebsite/excel` 4.0.2.

### PHPStan

PASS — `composer run types:check --no-interaction`: **0 error**.

### Pint

PASS — `vendor/bin/pint --dirty --format agent`.

### npm run check

FAIL terbatas pada **12 berkas formatting pre-existing** di luar scope: `app.tsx`, `app-sidebar-header.tsx`, `nav-user.tsx`, `two-factor-setup-modal.tsx`, `user-info.tsx`, `auth/login.tsx`, `baps/index.tsx`, `dashboard.tsx`, `allocations/create.tsx`, `allocations/index.tsx`, `boxes/index.tsx`, dan `users/index.tsx`.

Pemeriksaan terarah halaman laporan dan CSS print lulus format/lint.

### npm run types:check

FAIL baseline di luar scope: 20 pemanggilan Wayfinder `.form()` pada modul auth, 2FA, BAP, inventory, settings, dan users tidak cocok dengan tipe Wayfinder yang digenerate saat ini. Tidak ada diagnostik dari `resources/js/pages/laporan-pemakaian/index.tsx`.

### npm run build

PASS — Vite build, generation Wayfinder, CSS print, dan chunk `laporan-pemakaian` berhasil dibangun.

### git diff --check

PASS — tidak ada whitespace error.

## Known Issues

- `npm run check` global memiliki 12 formatting issue pre-existing di luar scope.
- `npm run types:check` global memiliki 20 error Wayfinder `.form()` pre-existing di luar scope.
- Browser interaktif desktop/mobile, Light/Dark, print preview fisik, dan aksesibilitas belum tervalidasi karena Browser Pest tidak tersedia.
- Query plan serta efektivitas index pada MySQL target belum tervalidasi; bukti query berasal dari SQLite lokal.

## Technical Debt

- Belum ada template atau kontrak dokumen resmi per Loket, harian, maupun nomeratur.
- Belum ada nomor register, tanda tangan, QR/barcode, atau kebijakan retensi output.
- Timezone bisnis operasional belum diputuskan; konfigurasi aplikasi masih UTC.
- Print seluruh detail terfilter belum memiliki route khusus; output lengkap tersedia melalui PDF/XLSX.

## Open Questions

1. Apakah PDF/XLSX ini perlu disahkan menjadi dokumen administrasi resmi?
   - Keputusan yang dibutuhkan: template, ukuran/orientasi, penandatangan, nomor register, QR/barcode, audience, dan approval.
2. Apakah export harus memiliki audit trail dan/atau arsip file?
   - Keputusan yang dibutuhkan: actor, timestamp, hash, retensi, akses ulang, dan apakah generation harus diregister.
3. Apakah detail BAP harus memiliki PDF/print individual dari Slide-over atau halaman detail?
   - Keputusan yang dibutuhkan: sumber layout BAP resmi dan status dokumen.
4. Apakah laporan persediaan bulanan/historical month-end diperlukan?
   - Keputusan yang dibutuhkan: definisi saldo, cutoff, closing/snapshot, koreksi, dan tanggung jawab rekonsiliasi.
5. Timezone bisnis apa yang menjadi batas periode operasional?
   - Opsi saat ini: UTC aplikasi atau timezone operasional seperti Asia/Makassar.
6. Apakah Petugas Loket perlu akses laporan yang dibatasi Loket?
   - Dampak: Gate, query server-side, navigasi, dan test isolasi Loket berubah.

## Keputusan Teknis

- Query layer `SkpdLaporanPemakaianQuery` dipakai bersama oleh web, PDF, dan XLSX untuk menjaga filter dan aggregate konsisten.
- PDF menggunakan Dompdf dan Blade; XLSX menggunakan Laravel Excel dengan tiga sheet; keduanya dihasilkan on-demand.
- Nomeratur XLSX dipaksa sebagai teks tujuh digit dan nilai nol dipertahankan dengan strict null comparison.
- Print menggunakan `window.print()` dan CSS `@media print`, tanpa endpoint print atau state baru.
- Tidak ada migration, snapshot, ledger tambahan, file persistence, atau mutation domain.

## Keputusan Bisnis

- Terminologi status final tetap **Selesai Administratif** (`completed`).
- Online dan batal/rusak tetap termasuk dalam total SKPD terpakai.
- Bendahara Barang dan Kepala UPTD tetap menjadi audience laporan read-only.
- Keluaran Phase 14 adalah **Laporan Sistem**, bukan klaim format dokumen resmi.

## Batasan Phase Berikutnya

Phase berikutnya tidak boleh menambahkan format dokumen resmi, BAP PDF individual, stock closing, historical inventory, register output, audit export, atau scope Loket baru tanpa keputusan bisnis tertulis.

## Handoff

Phase 14 selesai. Tahap selanjutnya dimulai hanya setelah salah satu open question di atas diputuskan, terutama format administrasi resmi dan kebijakan audit/retensi output bila keluaran akan digunakan di luar laporan sistem.
