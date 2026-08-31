---
paths:
  - app/Http/Controllers/SkpdBukuKendaliController.php
---

# Controllers

## Buku Kendali remains a completed-BAP read model
Buku Kendali may read only BAP completed. Keep BAP, usage segment, allocation, cancellation, and verification immutable; do not introduce a duplicate ledger or aggregate by joining segments and cancellations, which would double count BAP totals.
