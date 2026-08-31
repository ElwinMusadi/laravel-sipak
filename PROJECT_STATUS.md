# SIPAK — STATUS PROYEK

**Pembaruan terakhir:** 31 Agustus 2026
**Fase saat ini:** PHASE 10 — KLARIFIKASI, PENYELESAIAN SELISIH & RE-VERIFIKASI
**Status fase:** Implementasi fungsional selesai dan tervalidasi pada SQLite lokal. Validasi visual browser langsung serta target MySQL masih perlu dilakukan di lingkungan target.

## Fase Saat Ini

PHASE 10 — KLARIFIKASI, PENYELESAIAN SELISIH & RE-VERIFIKASI.

## Status Fase

Selisih dari Verifikasi Tahap 1 maupun Tahap 2 kini dapat ditangani oleh Loket terkait, ditinjau verifier pada tahap sumber, dibuka kembali bila tanggapan belum cukup, lalu dikembalikan ke antrean verifikasi ulang sebagai attempt baru. Tidak ada finalisasi, approval Bendahara Barang, rekonsiliasi, laporan bulanan, PDF final, atau stock closing.

## Ringkasan

- Satu `BapClarificationRequest` terikat pada satu `BapVerification`, sehingga satu ticket dapat mengelompokkan seluruh discrepancy dari satu attempt pemeriksaan.
- `BapClarificationResponse` menyimpan setiap tanggapan Loket per putaran, sedangkan `BapClarificationResolution` menyimpan keputusan verifier untuk tanggapan tersebut.
- Penyelesaian tidak mengubah BAP, usage segment, cancellation, allocation, inventory, atau discrepancy historis. Ia hanya mengubah state BAP ke antrean re-verifikasi tahap sumber.
- `StartBapVerification` membuat nomor attempt berikutnya per BAP dan tahap; attempt lama tidak pernah ditimpa.

## Klarifikasi

Ticket dibuat atomik oleh `CompleteBapVerification` ketika hasil sebuah attempt adalah `discrepancy`. Ticket menyimpan BAP, verification sumber, requester, pesan permintaan, status, waktu dibuat, dan penerima Loket yang diturunkan dari BAP. Status workflow yang digunakan adalah `waiting_response`, `responded`, `resolved`, dan `reopened`.

Aktivitas **Buka** mencatat petugas Loket pertama serta waktunya tanpa menciptakan state terpisah. Status `open` dievaluasi, tetapi tidak dipakai karena tidak membawa perubahan workflow tambahan dibanding `waiting_response`.

## Discrepancy

- Discrepancy tetap menjadi historical finding pada `bap_verification_discrepancies` dan terhubung ke ticket melalui `bap_verification_id`.
- Satu ticket dapat menampilkan semua discrepancy dari verification yang sama; tidak ada duplikasi ticket per discrepancy.
- Response atau resolution tidak boleh memperbarui expected value, actual value, difference, maupun catatan discrepancy asli.

## Ownership

- **Requester:** Petugas Penetapan untuk sumber Tahap 1 atau Petugas Verifikasi untuk sumber Tahap 2, direkam pada `requested_by` milik ticket.
- **Target/owner:** Loket pemilik BAP. Tidak ada `assigned_to` personal karena Blueprint belum menetapkan penanggung jawab individu.
- **Respondent:** Petugas Loket aktif pada Loket pemilik BAP, direkam per response sebagai `responded_by`.
- **Resolver:** Petugas Penetapan untuk ticket Tahap 1 atau Petugas Verifikasi untuk ticket Tahap 2, direkam per resolution sebagai `resolved_by`.
- **Re-verifier:** pengguna dengan role verifier tahap sumber; implementasi tidak mengunci pada orang yang membuat attempt sebelumnya.

## Role

- Petugas Loket hanya melihat, membuka, dan menanggapi ticket BAP Loketnya sendiri.
- Petugas Penetapan hanya melihat serta meninjau ticket dari `phase_1`.
- Petugas Verifikasi hanya melihat serta meninjau ticket dari `phase_2`.
- Role lain tidak mempunyai akses operasional ke antrean atau aksi klarifikasi.

## Clarification Lifecycle

`needs_clarification → waiting_response → responded → {resolved | reopened}`

Ticket `reopened` kembali dapat ditanggapi Loket. Ticket `resolved` memindahkan BAP ke antrean re-verifikasi yang sesuai; ia tidak langsung mengubah hasil attempt sebelumnya.

## Request

Ketika verifier menyelesaikan attempt dengan `discrepancy`, sistem membuat satu ticket `waiting_response`, menyimpan catatan requester, dan merekam audit `bap_clarification.requested`. Ticket baru akan dibuat bila attempt re-verifikasi berikutnya kembali menghasilkan discrepancy.

## Response

Petugas Loket mengirim response teks melalui route khusus. Sistem mengunci ticket dan BAP, memeriksa ownership Loket dan state `needs_clarification`, lalu menyimpan nomor putaran berurutan, `responded_by`, isi response, serta `responded_at`. Ticket berubah ke `responded` dan audit `bap_clarification.response_submitted` dicatat.

## Review

Petugas Penetapan hanya dapat meninjau response Tahap 1; Petugas Verifikasi hanya dapat meninjau response Tahap 2. Review hanya dapat dilakukan pada response terakhir saat ticket berstatus `responded`.

## Resolution

Keputusan `resolved` atau `reopened`, catatan reviewer, resolver, dan waktu keputusan disimpan sebagai record terpisah pada `bap_clarification_resolutions`. Satu response hanya boleh mempunyai satu resolution. Semua resolution tetap dapat dibaca dari detail ticket.

## Reopen

Keputusan `reopened` mempertahankan BAP pada `needs_clarification`, mengubah ticket ke `reopened`, dan membuka putaran response berikutnya untuk Loket. Model data mendukung beberapa pasang response/resolution tanpa menghapus riwayat sebelumnya; UI menyajikannya sebagai timeline linear, bukan workflow multi-ticket yang kompleks.

## Re-verification

Keputusan `resolved` tidak menyatakan BAP lulus. Sistem mengubah BAP ke status menunggu re-verifikasi berdasarkan stage verification sumber, lalu verifier yang berwenang memulai pemeriksaan ulang melalui alur verifikasi yang telah ada.

## Verification Attempt

`bap_verifications.attempt` dihitung ulang per kombinasi BAP dan stage di dalam transaksi. Attempt `1` yang menghasilkan discrepancy tetap `completed/discrepancy`; pemeriksaan ulang membuat attempt `2` dengan status `in_progress`, checklist baru, result baru, dan audit baru. Unique key `(bap_id, stage, attempt)` tetap menjadi pengaman data.

## Phase 1 Re-entry

Resolusi ticket dari `phase_1` memindahkan BAP:

`needs_clarification → waiting_reverification_phase_1 → under_verification`

Jika attempt Tahap 1 yang baru `passed`, BAP masuk `waiting_verification_phase_2`. Jika kembali discrepancy, BAP kembali ke `needs_clarification` dan ticket baru terikat pada attempt tersebut.

## Phase 2 Re-entry

Resolusi ticket dari `phase_2` memindahkan BAP:

`needs_clarification → waiting_reverification_phase_2 → under_verification_phase_2`

Jika attempt Tahap 2 yang baru `passed`, BAP menjadi `verified_phase_2`. Jika kembali discrepancy, BAP kembali ke `needs_clarification` dengan ticket baru pada attempt Tahap 2 tersebut.

## History

Detail BAP menampilkan riwayat verification per stage dan attempt, hasil, completion time, serta tautan ticket terkait bila pengguna berwenang. Detail klarifikasi menampilkan discrepancy asli, request, seluruh response/resolution round, dan event audit klarifikasi. Tidak ada reset atau overwrite attempt/discrepancy lama.

## Audit

Audit yang ditambahkan mencakup:

- `bap_clarification.requested`;
- `bap_clarification.opened`;
- `bap_clarification.response_submitted`;
- `bap_clarification.reviewed`;
- `bap_clarification.resolved`;
- `bap_clarification.reopened`;
- `bap_clarification.reverification_requested`; dan
- `bap_clarification.reverification_completed`.

Audit start dan completion verification juga kini membawa nomor attempt.

## Authorization

Gate route, `Gate::authorize()` pada controller, dan validasi ulang role/ownership/state di action bekerja berlapis. Direct HTTP untuk Loket lain, Loket yang mencoba review, dan verifier lintas tahap menghasilkan `403`; penolakan state menghasilkan validation error tanpa mutasi data.

## Concurrency

Semua aksi klarifikasi menggunakan `DB::transaction(..., attempts: 3)` dan `lockForUpdate()` atas ticket serta BAP. Response juga mengunci record response ketika menghitung putaran berikutnya; review mengunci response terakhir dan state ticket; start/re-verification mengunci BAP dan attempt. Uji sequential response kedua membuktikan state `responded` menolak response ganda. Race MySQL aktual masih belum diverifikasi pada server MySQL.

## UI/UX

- Navigation `Klarifikasi` tersedia berdasarkan permission server-derived untuk tiga role operasional.
- Queue satu halaman menyesuaikan role: **Klarifikasi Saya** untuk Loket, **Klarifikasi Tahap 1** untuk Petugas Penetapan, dan **Klarifikasi Tahap 2** untuk Petugas Verifikasi.
- Queue memuat BAP, Loket, tahap, ringkasan discrepancy, status Bahasa Indonesia, waktu request, dan durasi menunggu.
- Detail memisahkan informasi BAP, discrepancy expected/actual/difference, request, response, resolution, dan riwayat audit. Form response memakai textarea; review memakai dialog keputusan.
- Badge BAP dan filter BAP mengenali state re-verifikasi. Komponen mengikuti shadcn/ui yang telah terpasang dengan Amber Minimal serta mendukung theme aplikasi.

## Route

- `GET /bap-clarifications` — antrean yang tersaring role dan state.
- `GET /bap-clarifications/{clarification}` — detail ticket sesuai authorization.
- `POST /bap-clarifications/{clarification}/open` — mencatat pembukaan oleh Loket.
- `POST /bap-clarifications/{clarification}/responses` — mengirim response Loket.
- `POST /bap-clarifications/{clarification}/review` — resolve atau reopen oleh verifier tahap sumber.

Wayfinder telah diregenerasi dengan varian form setelah route ditambahkan.

## Action / Domain Layer

- `OpenBapClarification` mencatat pembukaan pertama oleh Loket pada state yang masih dapat ditanggapi.
- `SubmitBapClarificationResponse` membuat response putaran baru dan memindahkan ticket ke `responded`.
- `ReviewBapClarification` membuat resolution, membuka ulang atau memasukkan BAP ke antrean re-verifikasi yang tepat.
- `StartBapVerification` menghitung attempt baru dan menerima state antrean re-verifikasi.
- `CompleteBapVerification` memakai attempt `in_progress` terbaru, membuat ticket jika kembali discrepancy, dan tidak menimpa record lama.

## Database

- `bap_clarification_requests` diperluas dengan `opened_by` dan `opened_at`; data status lama `open` dimigrasikan ke `waiting_response`.
- Tabel baru `bap_clarification_responses` menyimpan response per ticket/round dengan unique key `(bap_clarification_request_id, round)`.
- Tabel baru `bap_clarification_resolutions` menyimpan resolution per response dengan unique key `bap_clarification_response_id`.
- CHECK constraint MySQL untuk status BAP diperluas untuk `waiting_reverification_phase_1` dan `waiting_reverification_phase_2`.
- Migration `2026_08_31_130408_extend_bap_clarification_workflow_for_phase_ten` telah diterapkan pada SQLite lokal. DDL dan locking MySQL target belum diuji.

## State Transition

| Dari | Aksi | Ke |
| --- | --- | --- |
| `needs_clarification` | ticket dibuat | `waiting_response` pada ticket |
| `waiting_response` / `reopened` | Loket mengirim response | `responded` pada ticket |
| `responded` | verifier reopen | `reopened` pada ticket |
| `responded` | verifier resolve Tahap 1 | `waiting_reverification_phase_1` pada BAP |
| `responded` | verifier resolve Tahap 2 | `waiting_reverification_phase_2` pada BAP |
| `waiting_reverification_phase_1` | mulai ulang Tahap 1 | `under_verification` |
| `waiting_reverification_phase_2` | mulai ulang Tahap 2 | `under_verification_phase_2` |

## Inventory Impact

Tidak ada mutasi Box, Allocation, Usage Segment, Cancellation, inventory ledger, atau stock. Klarifikasi hanya mencatat komunikasi/keputusan dan mengembalikan BAP ke workflow pemeriksaan.

## Source Immutability

Feature test Phase 10 memotret lalu membuktikan tidak berubahnya field BAP, usage segment, allocation, dan cancellation setelah response, resolution, serta re-verifikasi Tahap 1. Discrepancy attempt awal tetap tersimpan dan dapat dibaca setelah attempt kedua lulus.

## Testing

### Feature Test

PASS — `php artisan test --compact`: **123 test, 844 assertion**. `BapClarificationWorkflowTest` PASS: 7 test, 62 assertion; mencakup sumber Tahap 1/Tahap 2, resolve/reopen multi-round, attempt kedua, source immutability, audit, dan HTTP authorization.

### npm run check

FAIL (pre-existing, di luar scope) — terdapat masalah formatting pada 11 berkas yang tidak disentuh Phase 10: `resources/js/app.tsx`, `components/app-sidebar-header.tsx`, `components/nav-user.tsx`, `components/two-factor-setup-modal.tsx`, `components/user-info.tsx`, `pages/auth/login.tsx`, `pages/dashboard.tsx`, `pages/skpd/allocations/create.tsx`, `pages/skpd/allocations/index.tsx`, `pages/skpd/boxes/index.tsx`, dan `pages/users/index.tsx`. Semua berkas UI Phase 10 yang disentuh diperiksa terpisah: PASS, tanpa warning atau lint error.

### npm run types:check

PASS — TypeScript tanpa error.

### npm run build

PASS — Vite build dan generasi type Wayfinder berhasil.

### PHPStan

PASS — `vendor/bin/phpstan analyse --memory-limit=1G`: 0 error.

### Pint

PASS — `vendor/bin/pint --dirty --format agent`.

### git diff --check

PASS — tidak ada whitespace error.

## Known Issues

- `npm run check` global masih gagal hanya karena 11 berkas formatting pre-existing di luar scope seperti daftar di atas; berkas tersebut tidak diformat sesuai batas phase.
- Review browser manual untuk queue/detail pada desktop, mobile, Light Mode, dan Dark Mode belum tersedia di lingkungan ini. Build serta type-check berhasil, tetapi ini bukan pengganti bukti visual interaktif.
- Migration constraint dan perilaku lock/race MySQL belum divalidasi pada MySQL target.

## Technical Debt

- Target ticket masih pada level Loket, bukan assignee individu; tidak ada redistribusi atau eskalasi.
- Tidak ada SLA, attachment bukti fisik, notifikasi, atau penjadwalan pengingat karena belum ada aturan bisnis.
- UI multi-round bersifat timeline linear dan belum menyediakan filter/penugasan khusus untuk volume ticket besar.

## Open Questions

1. **Ownership individu:** apakah target harus tetap seluruh Petugas Loket pada Loket BAP, pembuat BAP, atau penanggung jawab Loket tertentu? Opsi bersama Loket memberi kontinuitas shift; assignee individu memberi akuntabilitas personal tetapi memerlukan aturan pengalihan saat tidak aktif.
2. **SLA:** apakah request/response/review membutuhkan batas waktu, pengingat, serta eskalasi? Tanpa keputusan ini, waktu tunggu hanya bersifat informasi dan tidak memicu tindakan sistem.
3. **Identitas re-verifier:** apakah re-verifikasi boleh dikerjakan verifier lain dengan role/stage yang sama atau wajib verifier pembuat finding? Implementasi saat ini memakai role/stage agar operasional tidak terblokir, tetapi aturan personal perlu diputuskan bila dibutuhkan.
4. **Bukti fisik:** apakah response perlu attachment atau cukup narasi dan pemeriksaan ulang langsung? Attachment memerlukan aturan format, retensi, akses, dan penyimpanan.

## Keputusan Teknis

- Satu ticket per verification attempt mengelompokkan banyak discrepancy agar request/review dapat dilakukan bersama; response dan resolution dipisah untuk audit serta multiple round.
- Target owner diturunkan dari `bap.loket_id`, bukan menduplikasi `loket_id` atau mengarang assignee personal.
- Resolved mengembalikan BAP ke stage sumber dengan state re-verifikasi eksplisit; start membuat attempt baru melalui counter database yang dikunci.
- Ticket/response/resolution dan BAP dikunci dalam transaksi; source dan historical findings tidak dimutasi.
- Route frontend menggunakan Wayfinder; tidak ada URL hard-coded atau dependency/UI framework baru.

## Keputusan Bisnis

- Petugas Penetapan menangani klarifikasi dan re-verifikasi Tahap 1; Petugas Verifikasi menangani Tahap 2; Petugas Loket hanya menanggapi BAP Loketnya.
- Keputusan `resolved` berarti response cukup untuk meminta pemeriksaan ulang, bukan kelulusan otomatis.
- Hasil lulus attempt Tahap 1 kembali ke antrean Tahap 2; hasil lulus attempt Tahap 2 menjadi `verified_phase_2`.

## Batasan Phase Berikutnya

Jangan menambahkan finalization, approval Bendahara Barang, monthly reporting, PDF final, stock closing, rekonsiliasi, perubahan data sumber BAP, maupun mutasi inventory tanpa aturan bisnis eksplisit.

## Handoff ke Phase 11

Tidak ada pekerjaan Phase 11 yang dimulai otomatis. Input yang tersedia adalah ticket/responses/resolutions, riwayat attempt immutable, dan state `verified_phase_2` atau `needs_clarification`. Sebelum phase berikutnya, putuskan ownership individu, SLA/escalation, bukti fisik, serta aturan proses setelah `verified_phase_2`.
