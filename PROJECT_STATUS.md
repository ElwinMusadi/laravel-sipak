# SIPAK — STATUS PROYEK

**Pembaruan terakhir:** 31 Agustus 2026
**Fase saat ini:** PHASE 09 — VERIFIKASI TAHAP 2
**Status fase:** Implementasi fungsional selesai dan tervalidasi pada SQLite lokal. Verifikasi format global dan review browser mengikuti quality gate di bawah.

## Fase Saat Ini

PHASE 09 — VERIFIKASI TAHAP 2.

## Status Fase

Petugas Verifikasi dapat mengambil BAP yang sudah lulus Verifikasi Tahap 1, memeriksa kembali data sistem dan tindisan fisik, lalu menyelesaikan Verifikasi Tahap 2 menjadi `verified_phase_2` atau mengirim BAP ke `needs_clarification`. Phase ini tidak membuat approval, finalisasi Bendahara Barang, rekonsiliasi, pelaporan, atau workflow klarifikasi dua arah.

## Ringkasan

- Phase 09 memperluas satu fondasi `BapVerification` dari Phase 08 melalui `stage = phase_1 | phase_2`; tidak ada tabel, model, atau skema checklist verifikasi paralel.
- Hasil pemeriksaan fisik tetap tersimpan per checklist dengan nilai harapan, nilai aktual, selisih, dan catatan verifier.
- Semua mutasi berjalan di dalam transaksi, mengunci BAP dan sesi verifikasi, serta tidak mengubah data sumber BAP, cancellation, allocation, usage segment, atau ledger persediaan.

## Verifikasi Tahap 2

- Tahap 2 memakai stage `phase_2`, attempt `1`, dan status sesi `in_progress` atau `completed` pada tabel `bap_verifications` yang sama dengan Tahap 1.
- Hasil `passed` hanya sah apabila lima checklist lengkap, seluruh nilai fisik sesuai, dan tidak ada selisih.
- Hasil `discrepancy` hanya sah apabila terdapat selisih dan setiap selisih memiliki catatan verifier.

## Verifier

Verifier operasional Tahap 2 adalah **Petugas Verifikasi**. Petugas Loket, Petugas Penetapan, Bendahara Barang, dan Superadmin tidak memperoleh bypass operasional untuk melihat antrean, detail, memulai, atau menyelesaikan Tahap 2. Superadmin tetap berada pada batas oversight sesuai policy aplikasi, bukan verifier Tahap 2.

## Queue

- Route `GET /bap-verifications-phase-2` hanya memuat BAP `waiting_verification_phase_2` dan `under_verification_phase_2` yang memiliki record Tahap 1 berstatus `completed` dengan hasil `passed`.
- Queue menampilkan nomor BAP, tanggal, Loket, nomeratur tujuh digit, total, online, status, verifier aktif, hasil Tahap 1, waktu selesai Tahap 1, dan durasi menunggu.
- BAP draft, submitted, sedang Tahap 1, needs clarification, maupun BAP yang dipaksa ke state menunggu tanpa hasil lulus Tahap 1 tidak masuk antrean.

## Eligibility

Memulai Tahap 2 membutuhkan dua kondisi server-side: status BAP `waiting_verification_phase_2` dan record Tahap 1 `completed/passed`. Controller menyaring keduanya pada queue, sementara `StartBapVerification` memeriksa ulang record Tahap 1 di dalam transaksi dengan `lockForUpdate()` agar tidak ada bypass melalui direct HTTP atau kondisi balapan.

## Verification Lifecycle

`draft → submitted → under_verification → waiting_verification_phase_2 → under_verification_phase_2 → {verified_phase_2 | needs_clarification}`

Tidak ada re-entry dari `needs_clarification` pada Phase 09. Mekanisme penyelesaian klarifikasi dan penentuan attempt re-verifikasi adalah batas Phase 10.

## Reuse Verification Architecture

`BapVerificationStage` kini menentukan role verifier, status BAP awal, status saat pemeriksaan, status lulus, label, dan prefix audit bagi Tahap 1 maupun Tahap 2. `StartBapVerification`, `CompleteBapVerification`, controller, Form Request, `BapVerification`, `BapVerificationChecklistItem`, serta `BapVerificationDiscrepancy` dipakai bersama; implementasi Tahap 2 tidak membuat `VerificationPhase2` terpisah.

## Checklist

Lima checklist Phase 08 dipakai kembali untuk Tahap 2:

1. Pemakaian;
2. Range Nomeratur;
3. Set Tindisan;
4. Batal/Rusak; dan
5. Online.

Setiap item wajib diattestasi. Range dinilai cocok hanya bila batas awal dan akhir fisik sama dengan BAP, bukan hanya jumlahnya sama.

## Pemeriksaan Fisik

Halaman detail menampilkan nilai sistem dan form nilai fisik untuk setiap checklist. Jumlah pemakaian, set tindisan, Batal/Rusak, dan online menyimpan expected, actual, serta difference. Nomeratur menyimpan batas awal/akhir expected dan actual serta menampilkan nol di depan hingga tujuh digit. Satu nomeratur tetap satu set berisi lima lembar tindisan.

## Discrepancy

- Satu sesi Tahap 2 dapat menghasilkan lebih dari satu discrepancy.
- Setiap record menyimpan `stage = phase_2` melalui relasi verification, type, expected value, actual value, difference, notes, verifier, dan timestamp.
- Selisih menciptakan satu fondasi `BapClarificationRequest` berstatus `open`, mencatat audit, lalu memindahkan BAP ke `needs_clarification`.
- Record Tahap 1 tidak ditimpa dan tetap dapat ditelusuri secara independen.

## Integrasi Verifikasi Tahap 1

Detail Tahap 2 selalu memuat hasil Tahap 1: verifier, waktu selesai, hasil, catatan, dan daftar selisih jika ada. Karena queue hanya menerima hasil Tahap 1 `passed`, BAP dengan selisih Tahap 1 tidak mendapat jalan pintas ke Tahap 2.

## State Transition

| Dari                           | Aksi                      | Ke                             |
| ------------------------------ | ------------------------- | ------------------------------ |
| `submitted`                    | mulai Verifikasi Tahap 1  | `under_verification`           |
| `under_verification`           | Tahap 1 lulus             | `waiting_verification_phase_2` |
| `waiting_verification_phase_2` | mulai Verifikasi Tahap 2  | `under_verification_phase_2`   |
| `under_verification_phase_2`   | Tahap 2 lulus             | `verified_phase_2`             |
| `under_verification_phase_2`   | Tahap 2 menemukan selisih | `needs_clarification`          |

`verified_phase_2` bukan finalization, monthly closing, reported, atau state pemrosesan Bendahara Barang.

## Authorization

- Gate `view-bap-verifications-phase-2`, `start-bap-verification-phase-2`, dan `complete-bap-verification-phase-2` membatasi aksi operasional pada Petugas Verifikasi.
- Middleware route, `Gate::authorize()`, Gate per-BAP saat mulai, serta pemeriksaan role dan state di action adalah lapisan server-side yang terpisah dari visibilitas CTA Inertia.
- `auth.permissions` hanya mengendalikan navigasi/sidebar; direct HTTP tidak bergantung pada UI.

## Concurrency

`StartBapVerification` dan `CompleteBapVerification` berjalan dengan `DB::transaction(..., attempts: 3)`. Keduanya mengunci BAP dengan `lockForUpdate()`; penyelesaian juga mengunci sesi tahap terkait. State diperiksa kembali di dalam transaksi dan unique key `(bap_id, stage, attempt)` menjaga agar hanya satu start dan satu completion Tahap 2 yang berhasil.

## Audit

Audit Tahap 2 yang tercatat pada `audit_logs` adalah:

- `bap_verification.phase_2_started`;
- `bap_verification.phase_2_checklist_completed`;
- `bap_verification.phase_2_discrepancy_recorded`;
- `bap_verification.phase_2_passed`;
- `bap_verification.phase_2_sent_to_clarification`; dan
- `bap_verification.phase_2_completed`.

## UI/UX

- Sidebar Petugas Verifikasi menampilkan entry Verifikasi Tahap 2 berbasis permission server-derived.
- Dashboard Petugas Verifikasi menampilkan metrik menunggu, sedang diperiksa, selisih, dan lulus Tahap 2 serta tautan queue.
- Satu halaman queue/detail responsive dipakai untuk dua stage dengan route Wayfinder terpisah; detail Tahap 2 menampilkan sumber BAP, usage segment, Batal/Rusak, hasil Tahap 1, checklist fisik, selisih, dan dialog konfirmasi.
- Badge status memakai teks semantic untuk menunggu, sedang diverifikasi, lulus, dan perlu klarifikasi; warna bukan satu-satunya penanda.

## Route

- `GET /bap-verifications-phase-2` — queue Verifikasi Tahap 2.
- `GET /bap-verifications-phase-2/{bap}` — detail Tahap 2 dan riwayat Tahap 1.
- `POST /bap-verifications-phase-2/{bap}/start` — mulai Tahap 2.
- `POST /bap-verifications-phase-2/{bap}/complete` — simpan checklist dan hasil Tahap 2.

Route Tahap 1 yang sudah ada tetap dipertahankan.

## Action / Domain Layer

- `StartBapVerification` menerima `BapVerificationStage`, memvalidasi role dan eligibility tahap, membuat sesi, mengunci data, lalu mengubah state BAP yang sesuai.
- `CompleteBapVerification` menerima stage yang sama, mencatat checklist dan multiple discrepancy, menghitung difference server-side, kemudian melakukan transition lulus atau klarifikasi.
- `CompleteBapVerificationRequest` yang dipakai bersama melarang klien mengirim atau mengganti field sumber BAP.

## Database

- Tidak ada tabel verifikasi baru; schema Phase 08 mendukung stage Tahap 2.
- Migration korektif `2026_08_31_091959_extend_bap_status_for_phase_two_verification` memperluas CHECK constraint MySQL dengan `under_verification_phase_2` dan `verified_phase_2` tanpa migrasi destruktif.
- SQLite lokal tidak memakai CHECK constraint tersebut; migration tetap aman pada SQLite.

## Testing

### Feature Test

PASS — `php artisan test --compact`: 116 test, 779 assertion. Regresi fokus BAP, cancellation, dashboard, Verifikasi Tahap 1, dan Verifikasi Tahap 2 juga PASS: 46 test, 442 assertion.

### npm run check

FAIL (pre-existing, di luar scope) — `npm run check` masih menemukan sembilan berkas formatting lama: `app-sidebar-header.tsx`, `nav-user.tsx`, `user-info.tsx`, `baps/index.tsx`, `dashboard.tsx`, `skpd/allocations/create.tsx`, `skpd/allocations/index.tsx`, `skpd/boxes/index.tsx`, dan `users/index.tsx`. Tiga berkas baru/berubah khusus Phase 09 (`PROJECT_STATUS.md`, queue, detail) telah diperiksa terpisah tanpa lint/format error.

### npm run types:check

PASS — TypeScript tanpa error setelah route Wayfinder dan UI Tahap 2 diperbarui.

### npm run build

PASS — `npm run build`.

### PHPStan

PASS — `vendor/bin/phpstan analyse --memory-limit=1G`: 0 error.

### Pint

PASS — `vendor/bin/pint --dirty --format agent`.

### git diff --check

PASS — `git diff --check`.

## Known Issues

- `npm run check` pada Phase 08 masih gagal karena sembilan berkas formatting pre-existing di luar scope: `app-sidebar-header.tsx`, `nav-user.tsx`, `user-info.tsx`, `baps/index.tsx`, `dashboard.tsx`, `skpd/allocations/create.tsx`, `skpd/allocations/index.tsx`, `skpd/boxes/index.tsx`, dan `users/index.tsx`. Status ini harus diverifikasi kembali pada quality gate akhir.
- Review browser manual desktop/mobile dan light/dark serta verifikasi MySQL target belum tersedia di lingkungan ini.

## Technical Debt

- Foundation klarifikasi tetap satu arah: belum ada assignee, komunikasi, SLA, response, resolution, atau re-verifikasi.
- Attempt kedua belum dirancang; Phase 09 mempertahankan attempt `1` dan tidak menambah bypass re-entry.

## Open Questions

- Setelah klarifikasi selesai, apakah BAP kembali ke Tahap 1 atau Tahap 2, dan apakah selalu membuat attempt baru?
- Siapa pemilik klarifikasi, SLA, bukti fisik, dan kontrak response/resolution?
- Apa aturan operasional Bendahara Barang setelah `verified_phase_2` sebelum finalization/reporting?

## Keputusan Teknis

- `BapVerificationStage` menjadi sumber konfigurasi workflow dua tahap agar role, state, audit, dan action tetap konsisten dalam satu arsitektur.
- Eligibility Tahap 2 diperiksa pada query queue dan diulang di dalam transaksi start, sehingga state BAP saja tidak cukup untuk bypass Tahap 1.
- Data fisik dan findings tetap terpisah dari source BAP; difference dihitung server-side.
- Wayfinder diregenerasi setelah route Tahap 2 ditambahkan.

## Keputusan Bisnis

- Petugas Verifikasi adalah verifier tunggal Tahap 2.
- Hanya hasil Tahap 1 `passed` yang dapat diteruskan ke Tahap 2.
- Selisih Tahap 2 menghentikan workflow pada `needs_clarification`; tidak ada jalur lulus sekaligus selisih.
- Lulus Tahap 2 membuat BAP siap sebagai input proses Bendahara Barang, bukan final.

## Batasan Phase Berikutnya

- Jangan mengimplementasikan workflow klarifikasi lengkap, rekonsiliasi, approval Kasie, finalization Bendahara Barang, monthly closing, pelaporan, PDF, atau bulk sign-off tanpa aturan bisnis eksplisit.
- Jangan mengubah pemakaian, range nomeratur, pembatalan, allocation, inventory, atau ledger ketika menangani selisih.

## Handoff ke Phase 10

Phase 10 menerima BAP `needs_clarification` beserta `BapClarificationRequest`, verification stage, checklist, discrepancy, dan audit trail yang sudah immutable. Phase 10 harus menetapkan pemilik, komunikasi, response, resolution, transaksi state re-entry, serta apakah BAP kembali ke Tahap 1 atau Tahap 2; Phase 09 tidak menyediakan tombol bypass untuk lulus atau verifikasi ulang.
