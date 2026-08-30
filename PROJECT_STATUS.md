# SIPAK — STATUS PROYEK

## Fase Saat Ini

PHASE 06 — BAP PEMAKAIAN

## Status Fase

Selesai dan tervalidasi pada scope Phase 06.

## Tanggal

2026-08-30

## Ringkasan

Phase 06 menghadirkan workflow BAP Pemakaian SKPD dari draft sampai submit, daftar dan detail sesuai scope akses, serta integrasi dashboard dari data BAP aktual. Implementasi memakai range-ledger Phase 04 dan allocation Phase 05; tidak ada pengurangan stock mutable, workflow pembatalan/rusak, atau verifikasi BAP pada fase ini.

## BAP Pemakaian

- Petugas Loket membuat, melihat, memperbarui draft, dan mengirim BAP milik Loket yang ditugaskan kepadanya.
- Satu BAP memiliki tanggal pelayanan, range nomeratur, total pemakaian derived, pemakaian SKPD Online, pemakaian non-Online, creator, Loket, status, serta waktu submit.
- Form tidak menyediakan pilihan Loket; Loket selalu berasal dari user autentikasi.
- Tidak ada nomor/formal BAP fiktif. UI memakai ID BAP sistem dan nomor audit internal yang tersedia.

## BAP Lifecycle

- Create menghasilkan `draft`.
- Draft dapat diubah hanya oleh pembuat Petugas Loket yang memiliki Loket BAP.
- Submit mengubah status domain menjadi `submitted`, yang dipresentasikan sebagai `Menunggu verifikasi`.
- BAP submitted bersifat read-only. Tahap verifikasi, klarifikasi, finalisasi, dan reporting tidak ditambahkan.

## BAP Usage Segment

- Pemakaian dicatat sebagai `BapUsageSegment` per allocation yang dilintasi range BAP.
- Satu BAP dapat memakai beberapa allocation `accepted` atau `completed` dalam satu range kontinu.
- Total pemakaian dan sisa allocation tetap dihitung dari ledger segment, bukan kolom stock mutable.
- Create dan update mencatat audit BAP serta perubahan segment yang bernilai bisnis.

## Nomeratur

- Input browser menerima tepat tujuh digit dan mempertahankan leading zero.
- Domain dan database tetap menggunakan integer; presentasi menggunakan zero-padding tujuh digit.
- Total pemakaian selalu dihitung sebagai `end - start + 1`; nilainya tidak dipercaya dari request frontend.

## Sequence Validation

- BAP pertama Loket dimulai dari awal allocation aktif pertama; BAP berikutnya harus dimulai tepat setelah akhir BAP sebelumnya.
- Tanggal BAP tidak boleh melampaui hari ini atau mendahului BAP terakhir Loket.
- Satu Loket hanya dapat memiliki satu BAP untuk satu tanggal.
- Perubahan range draft ditolak jika sudah ada BAP berikutnya, sehingga kesinambungan nomeratur tidak dapat rusak.
- Semua validasi range, ownership allocation, dan overlap berjalan di server di bawah transaction serta global inventory lock.

## SKPD Online

- Nilai Online adalah bilangan bulat non-negatif dan merupakan bagian dari total pemakaian.
- Non-Online dihitung read-only sebagai `total pemakaian - Online`.
- Request dengan Online melebihi total atau nilai total manual ditolak.

## Allocation Integration

- BAP hanya dapat memakai allocation yang telah `accepted` atau `completed` dan milik Loket pembuat.
- Range BAP dapat melintasi allocation berurutan untuk Loket yang sama.
- Allocation yang seluruh range-nya telah terpakai ditandai `completed`; allocation yang kembali tersisa tetap `accepted`.

## Inventory Integration

- `skpd_inventory_locks.id = 1` dikunci sebelum mutasi BAP dan segment ledger.
- `CreateBap`, `UpdateBap`, dan `SubmitBap` memakai `DB::transaction(..., attempts: 3)`.
- Tidak ada migration, redesign schema, atau field persediaan mutable baru pada Phase 06.

## Authorization

- Gate `view-baps`, `view-all-baps`, `view-bap`, `create-bap`, `update-bap`, dan `submit-bap` melindungi route dan controller.
- Petugas Loket hanya dapat melihat dan memutasi BAP scope Loket sendiri; update/submit juga mengharuskan ia creator dan BAP masih draft.
- Role pengawasan dapat membaca sesuai scope; Superadmin tetap read-only untuk mutasi BAP.
- `loket_id`, `status`, dan `total_usage` dari request eksplisit dilarang untuk mencegah pemalsuan input frontend.

## Audit

- Audit mencatat `bap.created`, `bap.updated`, `bap.submitted`, serta event perubahan `bap_usage_segments`.
- Detail BAP menampilkan riwayat audit relevan bersama actor dan waktu kejadian.
- Event baru tidak menyimpan password, secret, token, maupun credential.

## UI/UX

- Halaman BAP tersedia di `/baps`, `/baps/create`, `/baps/{bap}`, dan `/baps/{bap}/edit`.
- Daftar menyediakan pencarian/filter dan tabel yang dapat di-scroll lokal pada layar sempit.
- Form menampilkan preview total, Online, non-Online, range allocation aktif, dan ringkasan sebelum submit.
- Dialog submit meminta konfirmasi dan menampilkan ringkasan BAP sebelum state berubah.
- Dashboard menggunakan metrik BAP hari ini, antrean kerja, dan BAP terbaru dari data aktual; data placeholder BAP dihapus.

## Route

- `GET baps` — daftar BAP sesuai scope.
- `GET|POST baps/create` — form dan pembuatan draft BAP.
- `GET baps/{bap}` — detail BAP.
- `GET|PUT baps/{bap}/edit` — edit draft BAP milik creator.
- `POST baps/{bap}/submit` — submit draft BAP.
- Wayfinder digenerate ulang dengan form variants setelah route/controller ditambahkan.

## Action / Domain Layer

- `CreateBap` menambah validasi tanggal berurutan dan audit segment saat draft dibuat.
- `UpdateBap` mengunci ledger, membangun ulang segment dari range valid, memulihkan status allocation terdampak, dan menjaga urutan BAP berikutnya.
- `SubmitBap` mempertahankan transaksi dan lock sebelum mengubah draft menjadi submitted.
- Controller hanya menangani projection Inertia, authorization, dan delegasi Action.

## Database

- Schema BAP dan `bap_usage_segments` Foundation Phase 04 dipakai tanpa perubahan.
- Constraint unik `(loket_id, service_date)`, `numerator_start`, dan `numerator_end` menjadi pertahanan database tambahan atas rule aplikasi.
- Development/test menggunakan SQLite; target MySQL belum tersedia untuk pengujian integrasi langsung.

## Testing

### Feature Test

PASS — `php artisan test --compact`: 81 test, 444 assertion.

`BapWorkflowTest` mencakup create multiallocation, payload terlarang, allocation wajib, format/props range, BAP tunggal per Loket/tanggal, range tidak valid, urutan nomeratur, isolasi akses Loket, update draft, submit, dashboard aktual, dan constraint unik database. Regression Phase 03–05 juga lulus pada suite penuh.

### npm run check

PASS — tanpa warning atau lint error.

### npm run types:check

PASS — `tsc --noEmit`.

### npm run build

PASS — Vite production build berhasil.

### PHPStan

PASS — tanpa error.

### Pint

PASS — `vendor/bin/pint --dirty --format agent`.

### git diff --check

PASS — tanpa whitespace error.

## Known Issues

- MySQL target belum tersedia, sehingga contention lock paralel dan DDL belum divalidasi lewat integration test MySQL.
- Browser E2E/visual automation tidak tersedia. Validasi lint, TypeScript, build, dan test aplikasi lulus; review visual desktop/mobile/light/dark tetap diperlukan sebelum release.

## Technical Debt

- Aggregate dashboard BAP dibaca langsung dari ledger dan belum memakai cache karena tidak ada kebutuhan konsistensi cache yang disetujui pada fase ini.
- Detail audit menampilkan event BAP relevan; halaman audit log lintas domain belum menjadi scope.

## Open Questions

- Apakah BAP membutuhkan nomor dokumen formal terpisah dari ID sistem sebelum pencetakan atau pelaporan resmi?
- Apakah SIGNAL dan PRO NTT harus dipisahkan sebagai channel pelaporan?
- Bagaimana state, otorisasi, reason master, dan dampak ledger untuk BAP Batal/Rusak?
- Siapa role dan aturan klarifikasi/approval pada Phase 07?

## Keputusan Teknis

- Segment ledger menjadi satu-satunya sumber pemakaian BAP dan allocation; total selalu derived.
- Lock global dan transaction Phase 04 dipakai ulang untuk seluruh mutasi BAP.
- Range draft tidak boleh diubah setelah ada BAP lebih baru pada Loket yang sama.
- Frontend memakai Wayfinder untuk link dan Form controller; URL mutasi tidak di-hardcode.

## Keputusan Bisnis

- BAP submitted dipresentasikan `Menunggu verifikasi`, tetapi tidak dilakukan proses verifikasi pada Phase 06.
- Petugas Loket hanya beroperasi pada Loket yang ditugaskan; role pengawasan bersifat read-only untuk BAP.
- Online selalu bagian dari total pemakaian, bukan counter tambahan di luar range.

## Perubahan Domain

- Menambah Action `UpdateBap`, Form Request BAP, Gate, controller, route, Inertia projection, komponen BAP, dan feature test.
- Memperketat `CreateBap` dengan tanggal berurutan dan audit usage segment.
- Tidak mengubah migration, enum BAP, atau kontrak range-ledger Phase 04.

## Batasan Phase Berikutnya

- Jangan menambahkan BAP Batal/Rusak, verifikasi, klarifikasi, finalisasi, reporting, PDF, atau nomor BAP formal tanpa keputusan bisnis tertulis.
- Jangan mengganti ledger dengan field stock mutable atau melonggarkan global lock.
- Perubahan lifecycle allocation accepted/completed harus mempertahankan audit dan dampak segment BAP.

## Handoff ke Phase 07

- Gunakan BAP `submitted` sebagai antrean awal `Menunggu verifikasi`.
- Pertahankan scope Loket, transaction lock, unique constraint, dan segment ledger saat mendesain verifikasi/klarifikasi.
- Putuskan terlebih dahulu role approver, state transition, reason code, serta konsekuensi BAP Batal/Rusak sebelum implementasi Phase 07.
