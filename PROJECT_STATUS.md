# SIPAK — STATUS PROYEK

## Fase Saat Ini

PHASE 03 — AUTHENTICATION, USER MANAGEMENT & RBAC

## Status Fase

Selesai dan tervalidasi pada scope Phase 03.

## Tanggal

2026-08-30

## Ringkasan

SIPAK sekarang menggunakan autentikasi username + password dengan akun yang hanya dapat dibuat oleh Superadmin. Phase ini menambahkan fondasi role sistem, relasi satu pengguna ke satu Loket, manajemen pengguna administratif, audit trail perubahan pengguna tanpa password, dan otorisasi server-side untuk seluruh route user management. Light Mode menjadi tema default dan pengalih Light/Dark tersedia di header setelah tombol notifikasi.

## Perubahan Authentication

- Credential login adalah `username` + password; email tidak dipakai untuk login.
- Username dinormalisasi lowercase, wajib unik, panjang 3–50 karakter, serta hanya menerima huruf, angka, titik, tanda hubung, dan underscore.
- Public registration, reset password berbasis email, email verification, dan passkey login tidak lagi terdaftar sebagai route Fortify.
- Akun inactive tidak dapat login. Middleware `active` juga logout, invalidate session, dan regenerate CSRF token ketika akun yang sudah login dinonaktifkan.
- Login mencatat `last_login_at` setelah credential valid dan aktif.

## Perubahan Authorization

- Gate `manage-users` hanya mengizinkan `UserRole::Superadmin`.
- Semua route `/users` berada di balik `auth`, `active`, dan `can:manage-users`.
- Prop Inertia `auth.permissions.manageUsers` dikirim dari server untuk representasi navigasi; React tidak membuat keputusan akses berdasarkan string role.

## Role yang Diimplementasikan

- Superadmin
- Petugas Loket
- Petugas Penetapan
- Kasie Penetapan
- Petugas Verifikasi
- Kasie Verifikasi
- Bendahara Barang
- Kepala UPTD

Role bersifat fixed/system-defined melalui enum `App\UserRole`; tidak ada CRUD role bebas.

## User Management

- Superadmin dapat melihat daftar pengguna, mencari username/nama, serta memfilter role, status, dan Loket.
- Superadmin dapat membuat, melihat detail, mengubah username/nama/role/Loket/status, dan mereset password pengguna.
- Password selalu melalui hash cast Laravel, tidak ditampilkan di respons detail, dan tidak pernah masuk `audit_logs`.
- Tidak ada hard-delete user dari Profile Settings.

## Relasi User dan Loket

- Tabel `lokets` merupakan fondasi minimal master Loket, tanpa UI/CRUD master data.
- `users.loket_id` nullable dengan foreign key `nullOnDelete`.
- Role Petugas Loket wajib memiliki satu Loket; perubahan ke role lain mengosongkan penugasan Loket agar tidak menyisakan relasi yang tidak relevan.

## Perubahan Fortify

- `fortify.username` diubah menjadi `username` dan custom authentication callback hanya menerima pengguna aktif dengan password valid.
- Throttling login username + IP dan lifecycle session Fortify dipertahankan.
- 2FA tetap tersedia sebagai layer opsional untuk keputusan bisnis berikutnya.
- Feature registration, password reset email, email verification, dan passkey dinonaktifkan.

## Perubahan Database

- Migrasi development SQLite sudah dijalankan:
    - `2026_08_30_085235_create_lokets_table`
    - `2026_08_30_085236_add_user_management_fields_to_users_table`
    - `2026_08_30_085238_create_audit_logs_table`
- `users` kini memiliki `username`, `role`, `loket_id`, `is_active`, dan `last_login_at`; `email` nullable untuk kompatibilitas data lama, bukan credential.
- Existing user akan diberi username migrasi `user-{id}` agar unique dan dapat diperbarui Superadmin.
- Target deployment tetap MySQL; tidak ada perubahan konfigurasi environment/database infrastructure.

## Perubahan UI

- Login SIPAK memakai Logo Pemprov NTT, Username, Password, checkbox Ingat saya, dan tombol Masuk; tidak menampilkan Register, lupa password email, verifikasi email, atau passkey.
- Halaman React + Inertia baru: daftar pengguna, tambah, detail, edit, dan reset password.
- Sidebar menampilkan menu Pengguna hanya ketika server mengirim permission `manageUsers`.
- Pengalih tema Light/Dark berada tepat setelah tombol notifikasi di header.
- Light Mode adalah default server dan client; opsi System dihapus dari preference tema.

## File yang Ditambahkan

- `app/UserRole.php`
- `app/Actions/UserManagement/RecordUserManagementAudit.php`
- `app/Http/Controllers/UserManagementController.php`
- `app/Http/Middleware/EnsureUserIsActive.php`
- `app/Http/Requests/UserManagement/`
- `app/Models/AuditLog.php`
- `app/Models/Loket.php`
- `database/factories/LoketFactory.php`
- `database/migrations/2026_08_30_085235_create_lokets_table.php`
- `database/migrations/2026_08_30_085236_add_user_management_fields_to_users_table.php`
- `database/migrations/2026_08_30_085238_create_audit_logs_table.php`
- `lang/en/auth.php`
- `resources/js/components/app/appearance-toggle.tsx`
- `resources/js/components/users/user-form-fields.tsx`
- `resources/js/pages/users/`
- `tests/Feature/AppearanceTest.php`
- `tests/Feature/UserManagementTest.php`

## File yang Diubah

- `PROJECT_STATUS.md`
- konfigurasi, routes, provider, middleware Inertia, model, dan factory autentikasi terkait
- layout/header/theme React, navigasi aplikasi, halaman login, Profile, Security, Welcome, dan type auth
- Feature tests autentikasi, profile, security, dan 2FA yang masih relevan

## File yang Dihapus

- Action Fortify untuk public registration dan reset password email
- Profile delete request dan profile self-delete UI
- UI/login passkey dan UI pengelolaan passkey
- halaman auth Register, Forgot Password, Reset Password, dan Verify Email

## Dependency yang Ditambahkan

Tidak ada.

## Dependency yang Dihapus

Tidak ada. Package/migrasi passkey lama dipertahankan untuk menghindari perubahan dependency atau schema di luar scope; feature dan UI-nya dinonaktifkan.

## Konfigurasi yang Diubah

- `config/fortify.php`: username credential dan feature Fortify.
- `bootstrap/app.php`: alias middleware `active`.
- `resources/views/app.blade.php`, `HandleAppearance`, dan `use-appearance`: default Light Mode.

## Testing

### Feature Test

PASS — `php artisan test --compact`: 39 test, 168 assertion.

### npm run check

PASS — format, lint, dan warning frontend bersih.

### npm run types:check

PASS — `tsc --noEmit` selesai tanpa error.

### npm run build

PASS — Vite production build dan generation Wayfinder selesai.

### PHPStan

PASS — `vendor/bin/phpstan analyse --configuration=phpstan.neon --no-progress` tanpa error.

### Pint

PASS — `vendor/bin/pint --dirty --format agent`.

### git diff --check

PASS — tidak ada whitespace error.

## Known Issues

- Browser E2E belum tersedia karena `pestphp/pest-plugin-browser` tidak terpasang. SSR feature test membuktikan default Light Mode dan cookie Dark Mode, tetapi interaksi dropdown/filter, pengalih tema header, dan tampilan responsif belum divalidasi dengan browser automation.

## Technical Debt

- Audit trail Phase 03 hanya melingkupi perubahan administratif user; modul audit log UI dan audit untuk domain BAP/inventory ditunda untuk phase audit.
- Package dan tabel passkey lama masih ada tetapi tidak aktif. Penghapusan dependency/schema memerlukan keputusan migration cleanup terpisah.
- Master Loket belum memiliki CRUD atau data produksi; hanya fondasi relasi untuk Petugas Loket.

## Open Questions

- Apakah 2FA akan diwajibkan untuk role tertentu atau tetap opsional bagi semua pengguna?
- Apakah satu Petugas Loket tetap selalu satu Loket, atau perlu dukungan multi-loket pada phase domain berikutnya?
- Siapa yang membuat Superadmin awal pada deployment MySQL pertama?

## Keputusan Teknis

- Authentication = Username + Password.
- Email bukan credential login dan tetap nullable hanya untuk kompatibilitas data lama.
- Public registration dinonaktifkan; user hanya dibuat oleh Superadmin.
- Username lowercased adalah technical decision untuk konsistensi case-insensitive.
- Password reset dilakukan Superadmin dan tidak ada password, plaintext, atau hash yang masuk audit trail.
- Inactive user tidak dapat login atau mempertahankan sesi aktif.
- Authorization role dilakukan di server melalui Gate dan middleware; frontend menerima permission dari server.
- Terminologi Administrator pada blueprint dipetakan menjadi Superadmin.
- Light Mode adalah tema default; user hanya dapat memilih Light atau Dark.

## Keputusan Bisnis

- Petugas Loket wajib terkait satu Loket.
- Role sistem diperlakukan fixed dan tidak memiliki custom role administration pada Phase 03.

## Security Considerations

- Fortify tetap menangani rate limiting login, regenerasi sesi, invalidasi sesi logout, CSRF, dan password hashing.
- Pesan login gagal tetap generik: `Username atau password tidak sesuai.`
- Gate server-side menolak bypass HTTP pada user management.
- Input user management tervalidasi, mass assignment dibatasi, dan relasi Loket memakai foreign key.

## Handoff ke Phase Berikutnya

- Gunakan Gate/pattern permission server-side yang sama ketika menambahkan modul domain dan permission yang lebih granular.
- Jangan mengaktifkan kembali route/public UI registration, reset email, email verification, atau passkey tanpa keputusan bisnis dan test baru.
- Buat seed/prosedur Superadmin awal yang aman untuk deployment MySQL sebelum rollout pertama.
- Jangan membangun CRUD Master Loket atau modul bisnis BAP/Inventory di luar scope phase berikutnya yang disetujui.
