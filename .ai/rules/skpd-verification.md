---
paths:
  - 'app/Actions/SkpdVerification/**'
---

# Skpd Verification

## Verifikasi Tahap 1 tidak mengubah data sumber BAP
Hanya Petugas Penetapan yang dapat memulai dan menyelesaikan verifikasi. Aksi penyelesaian harus mengunci BAP dan sesi verifikasi, menyimpan checklist serta discrepancy terstruktur, lalu hanya mengubah status BAP ke Needs Clarification atau Waiting Verification Phase 2. Jangan memutasi rentang, total pemakaian, online, pembatalan, atau alokasi.

## Klarifikasi BAP mempertahankan temuan dan attempt
Satu ticket klarifikasi terikat pada satu BapVerification dan dapat memiliki beberapa response/resolution round. Jangan ubah discrepancy atau source BAP saat klarifikasi; keputusan resolved harus mengantrekan attempt verifikasi baru pada stage sumber, bukan menimpa attempt lama.
