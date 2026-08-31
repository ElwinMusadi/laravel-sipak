# SIPAK — STATUS PROYEK

**Pembaruan terakhir:** 31 Agustus 2026
**Fase saat ini:** PHASE 08 — VERIFIKASI TAHAP 1
**Status fase:** Implementasi fungsional selesai dan tervalidasi pada SQLite lokal. Quality gate format global masih terblokir oleh sembilan berkas lama di luar scope.

## Fase Saat Ini

PHASE 08 — VERIFIKASI TAHAP 1.

## Status Fase

Verifier Tahap 1, yaitu Petugas Penetapan, dapat mengambil BAP submitted, memeriksa data sistem terhadap fisik, lalu meneruskan BAP ke Verifikasi Tahap 2 atau mengirimkannya ke klarifikasi. Tidak ada approval, revisi/percakapan klarifikasi, atau workflow Tahap 2 yang diimplementasikan.

## Ringkasan

- BAP menerima lifecycle verifikasi Tahap 1 yang eksplisit dan immutable terhadap data sumber.
- Hasil pemeriksaan tersimpan per-item checklist, termasuk nilai harapan, nilai fisik, selisih, dan catatan verifier.
- Selisih membuat fondasi satu arah `BapClarificationRequest` berstatus `open`; belum ada modul penyelesaian klarifikasi.

## Verifikasi Tahap 1

- Hanya BAP `submitted` yang dapat dimulai.
- Memulai verifikasi membuat satu sesi Tahap 1 `in_progress` dan memindahkan BAP ke `under_verification`.
- Penyelesaian hanya dapat dilakukan oleh verifier yang memulai sesi tersebut.
- Hasil `passed` memindahkan BAP ke `waiting_verification_phase_2`; hasil `discrepancy` memindahkannya ke `needs_clarification`.

## Verifier

Petugas Penetapan adalah satu-satunya role yang memperoleh Gate, route middleware, CTA, dan validasi domain untuk melihat, memulai, atau menyelesaikan Verifikasi Tahap 1. Superadmin tidak memperoleh bypass operasional.

## Queue

- Queue memuat BAP `submitted` dan `under_verification`, dengan identitas BAP, Loket, pembuat, verifier, waktu pengajuan, dan status.
- BAP draft tidak muncul dan tidak dapat dimulai.
- Dashboard Petugas Penetapan menampilkan jumlah BAP menunggu dan sedang diverifikasi beserta tautan queue.

## Verification Lifecycle

`draft → submitted → under_verification → {waiting_verification_phase_2 | needs_clarification}`

Migration memetakan nilai legacy `waiting_verification` menjadi `submitted` sebelum lifecycle baru digunakan.

## Checklist

Setiap penyelesaian mewajibkan attestation verifier dan satu catatan terstruktur untuk lima pemeriksaan:

1. jumlah pemakaian;
2. range nomeratur;
3. set tindisan fisik;
4. BAP batal/rusak; dan
5. verifikasi online.

## Pemeriksaan Fisik

Nilai sistem selalu ditampilkan sebagai pembanding dan nilai fisik diisi oleh verifier. Set tindisan dihitung satu set per nomeratur (lima tindisan fisik per set), tanpa mengubah data pemakaian BAP.

## Physical Value

- Jumlah pemakaian, set tindisan, pembatalan, dan online menyimpan `expected_quantity`, `actual_quantity`, serta `quantity_difference`.
- Range nomeratur menyimpan batas awal/akhir sistem dan fisik; tampilannya mempertahankan tujuh digit dengan nol di depan.
- Range dianggap cocok hanya bila batas awal dan akhirnya sama dengan BAP, meskipun jumlahnya kebetulan sama.

## Discrepancy

- Hasil lulus ditolak bila ada nilai fisik yang tidak cocok.
- Hasil selisih ditolak bila tidak ada perbedaan.
- Setiap perbedaan wajib memiliki satu catatan verifier; sistem menyimpan expected value, actual value, difference, dan notes pada `bap_verification_discrepancies`.
- Hasil selisih membuat satu `bap_clarification_requests` berstatus `open` dan memindahkan BAP ke `needs_clarification`.

## BAP Integration

Verifikasi hanya membaca source of truth BAP: rentang nomeratur, total pemakaian, usage segment, penggunaan online, dan pembatalan. Verifikasi tidak mengubah BAP, usage segment, alokasi, maupun ledger persediaan.

## Cancellation Integration

Jumlah pembatalan yang diharapkan dihitung dari relasi `BapCancellation`. Nomeratur batal/rusak tetap merupakan pemakaian dan tidak dikembalikan ke stok.

## Online Verification

Nilai harapan pemeriksaan online berasal dari `online_usage_count` BAP. Verifier mencatat jumlah fisik/hasil pemeriksaan sebagai nilai aktual; tidak ada sinkronisasi atau mutasi sumber online pada Phase 08.

## Nomeratur Verification

Pemeriksaan range membandingkan batas BAP dengan range fisik. UI dan nilai range pada catatan discrepancy menggunakan representasi tujuh digit agar nol di depan tidak hilang.

## Authorization

- Gate `view-bap-verifications-phase-1`, `start-bap-verification-phase-1`, dan `complete-bap-verification-phase-1` memaksa role Petugas Penetapan.
- Middleware route dan `Gate::authorize()` tetap menjadi lapisan server-side di luar visibilitas UI.
- Action memverifikasi role, status BAP, status sesi, dan identitas verifier pemulai sebelum mutasi.

## Concurrency

- `StartBapVerification` dan `CompleteBapVerification` berjalan dalam `DB::transaction(..., attempts: 3)`.
- Kedua action mengunci BAP; penyelesaian juga mengunci sesi verifikasi Tahap 1.
- Unique key `(bap_id, stage, attempt)` dan state check di dalam transaksi mencegah dua sesi/penyelesaian aktif pada BAP yang sama.

## Audit

Audit trail yang dicatat: `bap_verification.phase_1_started`, `phase_1_checklist_completed`, `phase_1_passed`, `phase_1_discrepancy_recorded`, `phase_1_sent_to_clarification`, dan `phase_1_completed`.

## UI/UX

- Navigasi sidebar dan dashboard Petugas Penetapan menampilkan entry Verifikasi Tahap 1 berbasis permission server-derived.
- Halaman queue responsive menyediakan pencarian, status, detail, dan aksi mulai.
- Halaman detail menampilkan data sumber BAP, segmen pemakaian, pembatalan, checklist fisik, perbedaan terhitung, field catatan selisih, serta dialog konfirmasi penyelesaian.
- Badge BAP dan filter daftar BAP mendukung status baru Tahap 1, klarifikasi, dan menunggu Tahap 2.
- Build frontend berhasil; review browser manual pada viewport mobile/desktop belum dilakukan di lingkungan ini.

## Route

- `GET /bap-verifications` — queue Verifikasi Tahap 1.
- `GET /bap-verifications/{bap}` — detail BAP dan sesi verifikasi.
- `POST /bap-verifications/{bap}/start` — mulai verifikasi.
- `POST /bap-verifications/{bap}/complete` — simpan checklist dan hasil verifikasi.

## Action / Domain Layer

- `StartBapVerification` membuat attempt Tahap 1 dan memindahkan BAP dari submitted ke under verification.
- `CompleteBapVerification` menyimpan lima checklist, menghitung selisih, mencatat discrepancy/fondasi klarifikasi bila ada, dan melakukan state transition hasil.
- `CompleteBapVerificationRequest` memvalidasi bentuk payload dan melarang pengiriman field sumber BAP dari klien.

## Database

- Migration `2026_08_31_080115_add_phase_one_verification_workflow` telah diterapkan pada SQLite lokal.
- Tabel baru: `bap_verifications`, `bap_verification_checklist_items`, `bap_verification_discrepancies`, dan `bap_clarification_requests`.
- `baps` memperoleh index `(status, submitted_at)` dan constraint status MySQL diperluas untuk lifecycle baru.
- Kompatibilitas DDL MySQL belum diverifikasi pada server MySQL target.

## State Transition

| Dari | Aksi | Ke |
| --- | --- | --- |
| `draft` | submit BAP | `submitted` |
| `submitted` | mulai Tahap 1 | `under_verification` |
| `under_verification` | lulus | `waiting_verification_phase_2` |
| `under_verification` | ada selisih | `needs_clarification` |

## Testing

- PASS — `php artisan test --compact`: 102 test, 651 assertion.
- PASS — `php artisan test tests/Feature/BapVerificationWorkflowTest.php --compact`: 14 test, 117 assertion.
- PASS — regresi BAP, cancellation, dan dashboard: 32 test, 314 assertion.
- PASS — `vendor/bin/phpstan analyse --memory-limit=1G`: 0 error.
- PASS — `vendor/bin/pint --dirty --format agent`.
- PASS — `npm run types:check`.
- PASS — `npm run build`.
- PASS — `git diff --check`.
- PASS — `npx vp check` pada 10 berkas frontend Phase 08 yang tidak berada dalam sembilan berkas pre-existing: tanpa lint/format error.
- FAIL (pre-existing, di luar scope) — `npm run check` masih menemukan sembilan berkas format: `app-sidebar-header.tsx`, `nav-user.tsx`, `user-info.tsx`, `baps/index.tsx`, `dashboard.tsx`, `skpd/allocations/create.tsx`, `skpd/allocations/index.tsx`, `skpd/boxes/index.tsx`, dan `users/index.tsx`.

## Known Issues

- Quality gate format global belum hijau karena sembilan berkas pre-existing di atas; berkas tersebut tidak diubah massal demi menjaga scope Phase 08.
- Review browser manual dan verifikasi MySQL belum dilakukan pada lingkungan ini.

## Technical Debt

- Fondasi klarifikasi masih satu arah: belum ada assignee, percakapan, SLA, penyelesaian, atau re-verifikasi.
- Attempt Tahap 1 saat ini bernilai `1`; desain re-verifikasi berikutnya harus ditetapkan sebelum menambah attempt baru.

## Open Questions

- Siapa pemilik klarifikasi, batas waktunya, dan bagaimana Bukti/komentar disimpan?
- Setelah klarifikasi selesai, apakah BAP kembali ke submitted, membuat attempt Tahap 1 baru, atau memakai lifecycle tersendiri?
- Apa kontrak bisnis dan otorisasi untuk Verifikasi Tahap 2 serta approval berikutnya?

## Keputusan Teknis

- Data fisik dan hasil verifikasi disimpan terpisah dari source BAP sehingga audit dapat membandingkan expected versus actual tanpa memutasi sumber.
- Perbedaan dihitung server-side; klien tidak boleh mengirim status BAP, total pemakaian, range sumber, pembatalan, atau nilai online sistem.
- Wayfinder diregenerasi setelah route/action ditambahkan.

## Keputusan Bisnis

- Petugas Penetapan adalah Verifier Tahap 1 tunggal.
- Satu nomeratur mewakili satu set berisi lima tindisan fisik.
- BAP batal/rusak tetap dihitung sebagai pemakaian dan tidak mengembalikan persediaan.

## Batasan Phase Berikutnya

- Jangan menambahkan modul klarifikasi lengkap, workflow Verifikasi Tahap 2, approval, finalisasi, rekonsiliasi, reporting, atau PDF tanpa aturan bisnis eksplisit.
- Jangan mengizinkan verifikasi mengoreksi/mengubah pemakaian, range nomeratur, pembatalan, alokasi, atau ledger persediaan.

## Handoff ke Phase 09

Phase 09 hanya dapat mengonsumsi BAP `waiting_verification_phase_2` setelah user menetapkan role, checklist, hasil, exception, dan state transition bisnisnya. Selesaikan lebih dahulu keputusan klarifikasi/re-verifikasi serta review browser dan MySQL target; jangan memulai implementasi Tahap 2 otomatis.
