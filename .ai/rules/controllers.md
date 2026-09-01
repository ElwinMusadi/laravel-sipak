---
paths:
  - app/Http/Controllers/SkpdBukuKendaliController.php
  - app/Http/Controllers/SkpdLaporanPemakaianController.php
---

# Controllers

## Buku Kendali remains a completed-BAP read model
Buku Kendali may read only BAP completed. Keep BAP, usage segment, allocation, cancellation, and verification immutable; do not introduce a duplicate ledger or aggregate by joining segments and cancellations, which would double count BAP totals.

## Read-only report exports
PDF and XLSX must reuse SkpdLaporanPemakaianQuery, remain limited to completed BAPs, and enforce view-laporan-pemakaian at their direct HTTP endpoints. Treat output as Laporan Sistem until an official document format is approved.
