---
paths:
  - 'app/Actions/SkpdInventory/**'
---

# Skpd Inventory

## Range-ledger integrity and transaction lock
Registering boxes, creating or accepting allocations, creating BAP, recording cancellations, and submitting BAP must occur in a database transaction after locking skpd_inventory_locks id=1. Range overlaps and per-Loket sequential usage are application rules guarded by this lock; do not replace them with a mutable stock column.

## Pending allocation cancellation
Only the creator may cancel a pending allocation. Accepted allocations remain immutable in this phase; a future withdrawal workflow requires its own authorization, audit, and state-transition design.

## Draft BAP sequence preservation
BAP draft updates must acquire the shared inventory lock and preserve per-Loket numerator continuity. Once a later BAP exists, reject range changes to the earlier draft; usage segments remain the ledger source of truth and total usage stays derived.
