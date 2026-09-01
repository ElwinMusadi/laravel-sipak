# SIPAK — STATUS PROYEK

**Pembaruan terakhir:** 1 September 2026
**Fase saat ini:** Phase 18 selesai
**Status:** siap untuk manual testing konteks Loket pada MySQL lokal, dengan precondition alokasi MPP pada bagian Findings.

## Fase Saat Ini

Phase 18 — Superadmin Operational Loket Context & Manual Testing Readiness.

## Status Phase 18

**SELESAI.** Tidak ada role switching, impersonation, permanent assignment Loket untuk Superadmin, migration baru, atau fitur di luar scope.

## Tujuan

Memisahkan hak akses global Superadmin dari konteks Loket transaksi BAP. Superadmin dapat mencatat BAP untuk Loket aktif yang dipilih secara eksplisit tanpa berubah menjadi Petugas Loket.

## GAP Analysis

| Area | Existing | Missing | Risiko | Solusi |
| --- | --- | --- | --- | --- |
| Schema BAP | `baps.loket_id` dan `created_by` sudah terpisah serta terindeks. | Tidak ada. | Migration tambahan dapat mengubah ledger tanpa kebutuhan. | Tidak membuat migration. |
| Resolusi konteks | FormRequest, controller, dan `CreateBap` sudah membedakan Superadmin/Petugas Loket. | Bukti test Phase 18 dan penegasan detail aktor. | Frontend atau audit sulit diverifikasi manual. | Menambah feature test dan role aktor pada detail. |
| UI Create BAP | Selector Loket aktif telah tersedia untuk Superadmin; Petugas menerima Loket server-side. | Label operasional yang eksplisit dan komposisi Select lengkap. | Pengguna dapat mengira sedang berganti role. | Label `Loket Pelayanan`, SelectGroup, serta penjelasan konteks. |
| Detail/audit | BAP menyimpan Loket dan aktor; audit menyimpan `actor_id` dan `loket_id`. | Role aktor belum ditampilkan pada detail. | Who-created dan for-which-Loket kurang jelas. | Detail kini memuat Aktor, Role aktor, dan Loket. |
| Seed/manual test | Elwin, Simson, dan MPP ada pada MySQL development. | Alokasi accepted MPP belum ada. | Manual create BAP MPP tidak dapat dilanjutkan secara sah. | Tidak membuat inventory fiktif; buat/terima alokasi nyata melalui workflow existing sebelum uji manual. |

## Operational Context

Konteks Loket bersifat eksplisit hanya pada form Create BAP. Untuk Superadmin, `loket_id` adalah intent dari form yang divalidasi sebagai Loket aktif lalu di-resolve ulang oleh server. Tidak ada session context global, sehingga tidak ada stale context atau kebocoran lintas Loket.

## Role vs Loket

Role menjawab otorisasi user. Loket Pelayanan menjawab Loket domain pada transaksi BAP. `UserRole::Superadmin` tetap satu role global; `baps.loket_id` menyimpan konteks domain; `baps.created_by` menyimpan aktor.

## Superadmin

- Tetap memiliki global access melalui `Gate::before`.
- Tidak memiliki assignment Loket permanen.
- Dapat memilih semua Loket aktif pada Create BAP.
- Setiap pemilihan tetap melewati validasi Loket aktif, lock inventaris, alokasi, urutan nomeratur, dan satu BAP per Loket/hari.

## Petugas Loket

- Tetap hanya bekerja pada `users.loket_id` miliknya.
- Create BAP menampilkan Loket Pelayanan sebagai informasi read-only, bukan dropdown.
- `loket_id` dari POST ditolak oleh FormRequest; server selalu memakai assignment user.

## BAP Context

Create BAP Superadmin menerima Loket aktif terpilih. Create BAP Petugas Loket tidak menerima `loket_id` dari client. Update BAP tetap melarang perubahan `loket_id`, termasuk oleh Superadmin, untuk menjaga usage segment dan alokasi tetap konsisten.

## Server-side Resolution

Alur yang berlaku:

`Authenticated User → Gate/FormRequest → resolve Loket → lock inventaris dan Loket → cek active/alokasi/invarian → CreateBap → audit`.

`CreateBap` memakai `DB::transaction(..., attempts: 3)`, mengunci `skpd_inventory_locks.id = 1` dan row Loket sebelum membuat BAP serta usage segment.

## BAP UI

- Superadmin: label **Loket Pelayanan**, dropdown seluruh Loket aktif, lalu data alokasi dan nomeratur dimuat untuk pilihan itu.
- Petugas Loket: label **Loket Pelayanan** dan nama Loket read-only.
- UI tetap memakai Amber shadcn/Radix, Lucide, Tailwind v4, light/dark semantic token, serta Wayfinder yang ada.

## BAP Detail

Detail BAP menampilkan:

- Loket;
- Aktor;
- Role aktor;
- waktu pembuatan dan status lifecycle.

Dengan demikian, BAP dapat membedakan Elwin sebagai Superadmin pembuat dari Mall Pelayanan Publik sebagai Loket transaksi.

## Audit

Audit `bap.created` tetap menyimpan `actor_id` pada `audit_logs`, sementara `new_values.loket_id` menyimpan konteks Loket domain. Tidak ada mutasi role aktor atau audit mutable baru.

## Authorization

Otorisasi tetap server-side pada route middleware, Gate, FormRequest, controller, dan Action. `auth.permissions` hanya merupakan representasi UI. Superadmin tetap tunduk pada precondition domain; Petugas Loket tidak dapat memilih atau men-tamper Loket lain.

## Inventory Integrity

Tidak ada mutable stock, bypass allocation, atau ledger baru. `CreateBap` tetap menolak range di luar alokasi accepted/completed, celah/overlap penggunaan, nomeratur tidak berurutan, online melebihi total, dan BAP kedua pada Loket/tanggal yang sama. Nomeratur tetap divalidasi tujuh digit dengan minimum `0000001`.

## Inactive Loket

Loket inactive tidak muncul pada selector Superadmin dan ditolak untuk Create BAP. Petugas dengan assignment Loket inactive tidak dapat membuat BAP baru. BAP historis pada Loket inactive tetap dapat dibaca sesuai scope otorisasi.

## Seed Data

Verifikasi read-only MySQL development:

- `elwinmusadi16`: Elwin Bessiesura, `superadmin`, `loket_id = null`;
- `simson.sae`: Petugas Loket, terikat pada code `SAMSAT-KANTOR`;
- `MPP`: Mall Pelayanan Publik, aktif.

Tidak ada assignment palsu atau perubahan role untuk Elwin.

## Manual Test Scenario

1. Login sebagai `elwinmusadi16` dengan password development yang berlaku.
2. Pastikan Mall Pelayanan Publik memiliki alokasi accepted yang nyata melalui workflow Box/Alokasi existing.
3. Buka Create BAP, pilih **Loket Pelayanan: Mall Pelayanan Publik**, lalu gunakan range alokasi tersebut.
4. Pastikan detail menampilkan Aktor Elwin Bessiesura, Role aktor Superadmin, dan Loket Mall Pelayanan Publik.
5. Login sebagai `simson.sae`; Create BAP harus menampilkan Loket assignment-nya saja tanpa dropdown.

## Security Test

Feature test mencakup Superadmin context aktif, penyimpanan Loket/aktor/audit, assignment otomatis Petugas Loket, tampering `loket_id`, Loket inactive, data historis, alokasi, satu BAP per Loket/hari, larangan perubahan Loket draft, dan immutability submitted/completed.

## Database MySQL

Default koneksi aplikasi adalah `mysql`; `php artisan migrate:status` menunjukkan semua migration berstatus Ran. Full suite MySQL memakai `phpunit.mysql.xml` dan database testing terpisah.

## Migration

Tidak ada migration Phase 18. Schema BAP yang sudah ada (`loket_id`, `created_by`, unique `loket_id + service_date`) memenuhi kebutuhan operational context.

## Wayfinder

Tidak ada perubahan route/controller signature. `npm run build` memvalidasi dan meregenerasi actions, routes, serta form variants Wayfinder tanpa error.

## Testing

- Feature test Phase 18: 5 test, 90 assertion — PASS.
- Regresi terkait BAP/Superadmin/user management: 34 test, 442 assertion — PASS.
- Full suite SQLite: 168 test, 1.566 assertion — PASS.
- Full suite MySQL: 168 test, 1.566 assertion — PASS.

### Operational Context

PASS — Superadmin memilih Loket aktif tanpa permanent assignment atau role mutation.

### Superadmin

PASS — BAP memakai Loket terpilih; aktor dan role Superadmin tetap tersimpan/terlihat.

### Petugas Loket

PASS — assignment server-side dipakai otomatis dan UI tidak memberi pilihan Loket lain.

### Tampering

PASS — `loket_id` pada request Petugas Loket ditolak sebagai input terlarang.

### Inactive Loket

PASS — pembuatan baru ditolak, data historis tetap terbaca.

### BAP

PASS — alokasi dan one-BAP-per-Loket/day tetap dipaksakan; Loket BAP draft tidak dapat diubah; submitted/completed immutable.

### Audit

PASS — `actor_id` dan `new_values.loket_id` tervalidasi.

### MySQL

PASS — default MySQL, migration status Ran, dan full suite MySQL lulus.

### Full Regression

PASS — SQLite dan MySQL masing-masing 168 test, 1.566 assertion.

### TypeScript

PASS — `npm run types:check`.

### Build

PASS — `npm run build`, 3.317 modul ditransform.

### PHPStan

PASS — `vendor/bin/phpstan analyse --no-progress`, 0 error.

### Pint

PASS — `vendor/bin/pint --dirty --format agent`.

### git diff --check

PASS — tidak ada whitespace error.

## Findings

### P0

Tidak ada.

### P1

MySQL development saat ini belum memiliki alokasi accepted untuk MPP. Ini menghalangi hanya skenario manual create BAP sampai inventory nyata dialokasikan/diterima; tidak dibuat data inventory fiktif oleh Phase 18.

### P2

Nama Loket code `SAMSAT-KANTOR` pada MySQL development saat ini adalah `Kantor SAMSAT Kupang`, bukan label seed `SAMSAT Kantor`. Assignment Simson dan code tetap valid; tidak diubah karena bukan kebutuhan operational context.

### P3

Tidak ada.

## Fixes Implemented

- Menampilkan Role aktor pada detail BAP dan menambahkan `creator.role` pada read model.
- Menegaskan label **Loket Pelayanan**, pesan explicit context, validasi visual Select, dan `SelectGroup` pada form BAP.
- Menambah `OperationalLoketContextTest` untuk security/domain contract Phase 18.
- Memperbarui handoff proyek ini berdasarkan hasil audit dan quality gate aktual.

## Known Issues

Browser/a11y/responsive manual tidak diverifikasi karena harness browser tidak tersedia. Uji race/concurrency paralel MySQL juga belum tersedia; action dan suite serial tetap membuktikan lock/invarian yang diterapkan.

## Technical Debt

Global operational context tidak dibuat karena belum diperlukan lintas modul. Bila kebutuhan muncul, harus didesain dengan isolasi session, expiry, audit, dan revalidasi server-side tersendiri.

## Open Questions

Timezone operasional dan cut-off hari pelayanan masih belum ditetapkan; aplikasi tetap mengikuti konfigurasi waktu saat ini.

## Keputusan Teknis

Memilih selector Loket eksplisit pada Create BAP. Schema existing sudah memadai, sehingga resolusi dilakukan pada request/controller/action tanpa migration atau session global.

## Keputusan Bisnis

Superadmin tetap Superadmin; context transaksi tidak sama dengan role. Perubahan Loket draft ditolak sebagai keputusan aman karena BAP sudah memiliki usage segment dan keterikatan alokasi.

## Batasan

Phase 18 tidak menambah impersonation, role switching, notification, laporan, tahap verifikasi, closing, snapshot inventory, attachment, QR, atau signature.

## Handoff ke Phase 19

Selesaikan precondition alokasi MPP melalui workflow inventory yang sah sebelum demonstrasi manual end-to-end. Phase berikutnya tidak boleh mengubah operational context ini tanpa menjaga authorization server-side, lock ledger, immutability BAP, dan audit.
