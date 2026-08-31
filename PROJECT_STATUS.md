# PROJECT STATUS — SIPAK

**Pembaruan terakhir:** 31 Agustus 2026
**Fase saat ini:** PHASE 07 — BAP Batal/Rusak
**Status fase:** Implementasi fungsional selesai; satu quality gate global masih terblokir oleh sembilan berkas format di luar scope Phase 07.

## Ringkasan

Phase 07 menambahkan pencatatan nomeratur SKPD yang batal atau rusak pada BAP draft. Catatan hanya dapat dibuat oleh Petugas Loket pembuat BAP pada Loket yang sama, hanya untuk nomeratur yang telah tercatat pada pemakaian BAP tersebut, dan tidak mengubah ledger persediaan maupun alokasi.

## BAP Batal/Rusak

- Halaman daftar, detail, dan form pencatatan telah tersedia.
- BAP berstatus submitted tetap read-only, mengikuti lifecycle BAP Pemakaian Phase 06.
- Satu BAP dapat memiliki banyak catatan batal/rusak.

## Cancellation Model

- Menggunakan model `BapCancellation` yang sudah tersedia.
- Setiap catatan menyimpan BAP, nomeratur, alasan, uraian opsional, dan pembuat catatan.
- Audit event `bap_cancellation.recorded` dicatat ketika pencatatan berhasil.

## Relasi BAP

- `Bap` memiliki relasi `cancellations`.
- Detail BAP memuat daftar pembatalan/kerusakan, jumlah batal/rusak, dan jumlah pemakaian normal.

## Nomeratur

- Input menerima satu hingga tujuh digit dan ditampilkan kembali sebagai tujuh digit dengan nol di depan.
- Nomeratur harus berada di rentang BAP dan benar-benar tercatat pada `BapUsageSegment` BAP tersebut.
- Nomeratur tidak boleh dicatat lebih dari sekali secara global; unique constraint database tetap menjadi lapisan terakhir.

## Alasan

- Alasan yang tersedia mengikuti enum saat ini: `Batal` dan `Rusak`.
- Uraian opsional dibatasi hingga 1.000 karakter.

## Validation

- Validasi request melarang pengiriman identitas BAP, pembuat, Loket, tanggal layanan, status, dan total pemakaian dari klien.
- Validasi domain menolak BAP non-draft, nomeratur di luar pemakaian BAP, duplikasi, dan akses lintas Loket/pembuat BAP.

## Inventory Impact

- Tidak ada perubahan pada stok, mutasi, atau ledger persediaan SKPD.
- Nomeratur batal/rusak tetap dihitung sebagai terpakai sesuai Blueprint.

## Allocation Impact

- Tidak ada perubahan pada alokasi, penerimaan alokasi, ataupun saldo alokasi Loket.

## Authorization

- `view-bap-cancellations` mengikuti cakupan akses lihat BAP.
- Pencatatan memerlukan Petugas Loket, Loket yang sama, pembuat BAP yang sama, dan BAP draft.
- Endpoint tidak menyediakan edit atau hapus.

## Audit

- Pencatatan berhasil menghasilkan audit trail `bap_cancellation.recorded` dengan referensi BAP, nomeratur, alasan, dan uraian.

## UI/UX

- Navigasi BAP Batal/Rusak aktif berdasarkan izin server-derived.
- Daftar mendukung pencarian dan filter alasan.
- Form menggunakan route Wayfinder, normalisasi tampilan tujuh digit saat blur, serta ringkasan BAP dan pemakaian yang tersisa.
- Detail BAP menyediakan tautan ke catatan batal/rusak dan ringkasan jumlah pemakaian.

## Route

- `GET /bap-cancellations` — daftar catatan.
- `GET /bap-cancellations/{bapCancellation}` — detail catatan.
- `GET /baps/{bap}/cancellations/create` — form pencatatan.
- `POST /baps/{bap}/cancellations` — simpan catatan.

## Action Domain

- `RecordBapCancellation` menjalankan seluruh mutasi dalam `DB::transaction(..., attempts: 3)`.
- Action mengunci `skpd_inventory_locks.id = 1`, BAP, segmen pemakaian terkait, dan pemeriksaan duplikasi sebelum membuat catatan.

## Database

- Tidak ada migration baru pada Phase 07; tabel `bap_cancellations` dan indeks/unique constraint yang sudah tersedia digunakan.

## Concurrency

- Pencatatan mengikuti kontrak lock persediaan global yang sama dengan mutasi domain SKPD lain.
- Pemeriksaan duplikasi dilakukan di dalam transaksi; unique index database menangani race condition akhir.

## Testing

- PASS — `php artisan test tests/Feature/BapCancellationWorkflowTest.php --compact`: 7 test, 90 assertion.
- PASS — `php artisan test --compact`: 88 test, 534 assertion.
- PASS — `vendor/bin/phpstan analyse --memory-limit=1G`: 0 error.
- PASS — `vendor/bin/pint --dirty --format agent`.
- PASS — `npm run types:check`.
- PASS — `npm run build`.
- PASS — `git diff --check`.
- FAIL (di luar scope Phase 07) — `npm run check` masih melaporkan format pada sembilan berkas perubahan yang sudah ada/berjalan paralel: `app-sidebar-header.tsx`, `nav-user.tsx`, `user-info.tsx`, `baps/index.tsx`, `dashboard.tsx`, `skpd/allocations/create.tsx`, `skpd/allocations/index.tsx`, `skpd/boxes/index.tsx`, dan `users/index.tsx`. Berkas Phase 07 telah diformat dan tidak dilint ulang secara otomatis untuk menghindari perubahan di luar scope.

## Known Issues

- Quality gate `npm run check` global belum hijau karena sembilan berkas di luar scope Phase 07 sebagaimana dicatat di atas.
- Review browser manual dan verifikasi MySQL belum dilakukan pada lingkungan ini.

## Technical Debt

- Master reason `batal_reasons` yang disebut Blueprint belum memiliki daftar bisnis final; Phase 07 memakai enum `Batal` dan `Rusak` yang telah ada.
- Pengelolaan master reason dan pelaporan khusus batal/rusak ditunda sampai aturan bisnisnya tersedia.

## Open Questions

- Apakah alasan pembatalan/kerusakan perlu dikelola melalui master data resmi selain dua nilai enum saat ini?
- Apakah audit cancellation membutuhkan tampilan riwayat khusus atau akan cukup melalui audit trail umum?

## Keputusan Teknis

- Pencatatan hanya menambah `BapCancellation`; ledger dan allocation ledger tidak dimutasi.
- Otorisasi dipaksakan pada Gate, route middleware, Form Request, dan Action domain.
- Wayfinder digenerasikan ulang untuk seluruh route/action baru.

## Keputusan Bisnis

- Nomeratur batal/rusak tetap termasuk pemakaian BAP dan tidak mengembalikan stok.
- BAP submitted tidak dapat menerima catatan baru atau perubahan dari Phase 07.
- Verifikasi, approval, reconciliation, finalisasi, reporting, dan PDF belum diimplementasikan karena belum ada aturan bisnis Phase berikutnya.

## Batasan

- Tidak ada bulk input, upload, edit, hapus, approval, atau mutasi persediaan/alokasi.
- Tidak ada perubahan dependency, skema database, atau lifecycle BAP Pemakaian di luar kontrak yang sudah ada.

## Handoff

- Lanjutkan ke pemeriksaan browser/manual dan MySQL jika lingkungan target telah tersedia.
- Selesaikan format sembilan berkas di luar scope secara terpisah sebelum menjadikan `npm run check` global sebagai quality gate hijau.
- Jangan melanjutkan ke Phase 08 tanpa aturan bisnis dan persetujuan eksplisit.
