---
paths:
  - 'app/Providers/**'
---

# Providers

## Global Superadmin authorization
Superadmin receives global authorization through Gate::before. Keep ordinary role Gates explicit for every other user. Domain actions must preserve state validation, transaction locks, and audit records; never simulate access by assigning a permanent Loket to Superadmin.
