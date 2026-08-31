---
paths:
  - 'app/Actions/SkpdVerification/**'
---

# Skpd Verification

## Verifikasi Tahap 1 tidak mengubah data sumber BAP
Hanya Petugas Penetapan yang dapat memulai dan menyelesaikan verifikasi. Aksi penyelesaian harus mengunci BAP dan sesi verifikasi, menyimpan checklist serta discrepancy terstruktur, lalu hanya mengubah status BAP ke Needs Clarification atau Waiting Verification Phase 2. Jangan memutasi rentang, total pemakaian, online, pembatalan, atau alokasi.
