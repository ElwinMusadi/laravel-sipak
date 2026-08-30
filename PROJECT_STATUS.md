# SIPAK — STATUS PROYEK

## Fase Saat Ini

PHASE 02 — SIPAK APPLICATION SHELL & DASHBOARD

## Status Fase

Selesai dengan keterbatasan validasi browser runtime yang tercatat.

## Tanggal

2026-08-30

## Ringkasan

Application shell React + Inertia SIPAK telah menggantikan shell starter pada area aplikasi dan halaman publik. Shell memakai Sidebar shadcn yang responsif, header dengan breadcrumb, placeholder notifikasi, menu pengguna, navigasi statis yang siap menerima RBAC, serta dashboard berorientasi pekerjaan dengan data presentasi terisolasi.

## Baseline Proyek

- Laravel 13.29.0, PHP 8.3, React 19, Inertia Laravel/React v3, Tailwind CSS v4, TypeScript, shadcn/ui, Wayfinder, dan npm.
- Database development saat ini SQLite; MySQL tetap target deployment/domain sesuai blueprint.
- Arsitektur frontend tetap React + Inertia. Tidak ada Livewire, database domain SIPAK, business workflow, atau RBAC backend yang ditambahkan pada fase ini.
- Tema Amber Minimal dan mekanisme appearance light/dark yang sudah ada dipertahankan.

## Pekerjaan yang Selesai

- Menerapkan identitas SIPAK untuk shell aplikasi, auth layout, halaman publik, dan judul fallback Inertia.
- Menyalin aset resmi `blueprint/Logo Pemprov-NTT HD.png` secara utuh ke `public/images/logo-pemprov-ntt.png`; tidak ada pembuatan ulang, filter, atau distorsi logo. Logo dipakai di branding aplikasi dan favicon.
- Mengadaptasi Sidebar shadcn menjadi navigasi SIPAK yang dikelompokkan: Dashboard, Operasional, SKPD, Verifikasi, Laporan, dan Administrasi.
- Memusatkan metadata navigasi pada `applicationNavigation`, termasuk metadata role sebagai fondasi UI. Hanya Dashboard aktif; modul bisnis tampil nonaktif dan tidak mengklaim akses atau authorization.
- Menyediakan header aplikasi dengan sidebar trigger, breadcrumb reusable, placeholder notifikasi yang eksplisit, dan menu pengguna dengan avatar/initials, nama, email, profil/pengaturan, serta logout.
- Mengadaptasi Dashboard-01 menjadi dashboard SIPAK berorientasi `My Work`: KPI, antrean pekerjaan, BAP terbaru, aktivitas, dan empty state persediaan.
- Menempatkan data dashboard presentasi di layer terpisah dan mendukung penggantian melalui prop Inertia `dashboard` pada fase integrasi berikutnya.
- Menambahkan fondasi reusable `EmptyState`, `LoadingState`, dan `ErrorState`.
- Menambahkan token semantic `success` untuk status selesai, tanpa menambah dependency atau warna feature-level yang terpisah dari design system.
- Menghapus komponen dan aset starter Laravel yang sudah tidak direferensikan, termasuk tautan repository/dokumentasi starter dan favicon starter.
- Memperkuat test dashboard untuk memastikan route terproteksi mengembalikan komponen Inertia `dashboard` dan prop pengguna terautentikasi.

## File yang Ditambahkan

- `public/images/logo-pemprov-ntt.png`
- `resources/js/components/app/application-navigation.tsx`
- `resources/js/components/app/navigation.ts`
- `resources/js/components/app/header-user-menu.tsx`
- `resources/js/components/app/notification-placeholder.tsx`
- `resources/js/components/app/empty-state.tsx`
- `resources/js/components/app/loading-state.tsx`
- `resources/js/components/app/error-state.tsx`
- `resources/js/components/dashboard/dashboard-data.ts`
- `resources/js/components/dashboard/dashboard-metrics.tsx`
- `resources/js/components/dashboard/my-work.tsx`
- `resources/js/components/dashboard/recent-baps.tsx`
- `resources/js/components/dashboard/activity-feed.tsx`
- `resources/js/components/dashboard/inventory-summary.tsx`
- `resources/js/components/dashboard/status-badge.tsx`

## File yang Diubah

- `PROJECT_STATUS.md`
- `resources/css/app.css`
- `resources/js/components/app-logo.tsx`
- `resources/js/components/app-sidebar.tsx`
- `resources/js/components/app-sidebar-header.tsx`
- `resources/js/components/breadcrumbs.tsx`
- `resources/js/components/user-info.tsx`
- `resources/js/components/user-menu-content.tsx`
- `resources/js/layouts/auth/auth-card-layout.tsx`
- `resources/js/layouts/auth/auth-simple-layout.tsx`
- `resources/js/layouts/auth/auth-split-layout.tsx`
- `resources/js/pages/dashboard.tsx`
- `resources/js/pages/welcome.tsx`
- `resources/views/app.blade.php`
- `tests/Feature/DashboardTest.php`

## File yang Dihapus

- `public/apple-touch-icon.png`
- `public/favicon.ico`
- `public/favicon.svg`
- `resources/js/components/app-header.tsx`
- `resources/js/components/app-logo-icon.tsx`
- `resources/js/components/nav-footer.tsx`
- `resources/js/components/nav-main.tsx`
- `resources/js/layouts/app/app-header-layout.tsx`

## Dependency yang Ditambahkan

Tidak ada.

## Konfigurasi yang Diubah

- `resources/css/app.css` menambahkan token `success`/`success-foreground` untuk status selesai di light dan dark mode.
- `resources/views/app.blade.php` memakai favicon Pemprov NTT dan fallback title `SIPAK`.
- Build Wayfinder tetap menghasilkan helpers route/action yang telah ada; tidak ada route atau dependency baru.

## Keputusan Design System

- Tetap gunakan token semantic shadcn/Tailwind (`primary`, `muted`, `destructive`, `success`, dan seterusnya), bukan warna raw pada feature component.
- Amber tetap menjadi warna action/waiting. Status completed menggunakan token `success`; clarification dan revision menggunakan treatment destructive; draft menggunakan secondary.
- Logo Pemprov NTT selalu ditampilkan dengan `object-contain` tanpa efek dekoratif.

## Keputusan UI/UX

- Sidebar shadcn yang sama melayani desktop persistent/collapsible dan mobile Sheet, sehingga navigasi tidak memiliki implementasi desktop/mobile yang terpisah.
- Breadcrumb tetap dikendalikan oleh page layout context; header tidak menghardcode breadcrumb per halaman.
- Placeholder notifikasi bersifat disabled dan berlabel jelas agar tidak terlihat sebagai sistem notifikasi aktif.
- Menu modul bisnis yang belum berada dalam scope dibuat nonaktif. Metadata role hanya fondasi presentasi dan belum menyaring, memberi akses, atau menggantikan authorization server-side.
- Dashboard menampilkan label `Data presentasi` agar angka/aktivitas mock tidak terbaca sebagai data produksi.

## Kondisi Application Shell

- Shell terdiri dari sidebar, header, breadcrumb, main content, user menu, dan navigasi mobile melalui primitive Sidebar/Sheet shadcn yang tersedia.
- Sidebar menampilkan logo Pemprov NTT, SIPAK, serta instansi UPTD Pendapatan Daerah Wilayah Kota Kupang. Pada keadaan collapsed, logo tetap terlihat dan setiap navigasi aktif memiliki tooltip.
- Header berisi sidebar trigger, breadcrumb responsif, placeholder notifikasi, serta menu pengguna yang mempertahankan route/profile/logout starter kit.
- Branding Laravel starter, tautan repository/dokumentasi starter, dan favicon starter tidak lagi dipakai oleh runtime aplikasi.

## Kondisi Dashboard

- Dashboard menampilkan KPI BAP hari ini, menunggu verifikasi, perlu klarifikasi, dan selesai hari ini.
- `My Work` menjadi fokus utama dengan pending task, need clarification, dan need revision.
- BAP terbaru menggunakan komponen Table shadcn dengan status semantic dan container overflow horizontal pada layar sempit.
- Ringkasan persediaan menggunakan Empty State yang menjelaskan kondisi data, bukan copy `Coming Soon`.
- Semua data dashboard berada pada `dashboard-data.ts` dan dapat digantikan prop `dashboard` ketika phase backend/domain berikutnya tersedia.

## Validasi dan Testing

### npm run check

PASS — 83 file terformat benar; 74 file diperiksa tanpa warning atau lint error.

### npm run types:check

PASS — `tsc --noEmit` selesai tanpa error.

### npm run build

PASS — Vite production build selesai; Wayfinder menghasilkan helpers route/action dan dashboard bundle berhasil dibuat.

### php artisan test --compact

PASS — 39 test, 147 assertion.

### git diff --check

PASS — tidak ada whitespace error.

### Validasi Tambahan

- PASS — `vendor/bin/pint --dirty --format agent`.
- PASS — test fokus `tests/Feature/DashboardTest.php`: 2 test, 14 assertion.
- PASS — pemeriksaan source tidak menemukan copy starter repository/documentation/Laravel welcome yang aktif.
- PASS — inspeksi visual aset sumber mengonfirmasi logo Pemprov NTT sebelum penyalinan ke public asset.

## Known Issues

- Browser E2E/runtime tidak tersedia: `pestphp/pest-plugin-browser` tidak terpasang. Interaksi Sheet mobile, tooltip collapsed, user dropdown, serta review visual pada breakpoint desktop/tablet/mobile dan light/dark belum dibuktikan oleh browser automation pada fase ini.
- Aset sumber `blueprint/Logo Pemprov-NTT HD.png` hadir sebagai file untracked dari project owner dan dipertahankan; salinan runtime berada di `public/images/logo-pemprov-ntt.png`.

## Technical Debt

- Konfigurasi navigasi sudah membawa metadata role, tetapi filtering menu dan authorization sesungguhnya menunggu RBAC backend.
- KPI, antrean kerja, BAP terbaru, dan aktivitas dashboard masih data presentasi deterministik sampai prop/domain SIPAK tersedia.
- Loading dan Error State sudah reusable, namun belum ditampilkan pada dashboard karena dashboard fase ini tidak melakukan request data mandiri/deferred.

## Open Questions

- Tetapkan mapping role final terhadap tiap modul ketika desain RBAC dan permission database disetujui.
- Tetapkan kontrak Inertia props dashboard dan definisi metrik produksi sebelum mengganti data presentasi.

## Keputusan Teknis

- Memakai Wayfinder helper untuk route Dashboard, home, login, register, profile, dan logout; tidak memperkenalkan URL frontend hardcoded untuk route aplikasi.
- Menjaga data presentasi bebas dari query, Eloquent, migration, atau business workflow.
- Memakai Sidebar shadcn yang tersedia, bukan implementasi sidebar/drawer baru.

## Keputusan Bisnis yang Relevan

- Tidak ada perubahan aturan bisnis, inventaris, BAP, verifikasi, klarifikasi, pelaporan, audit trail, atau database domain SIPAK.
- Status dashboard hanya presentasi UI, bukan representasi data operasional atau izin akses produksi.

## Batasan Phase Berikutnya

- Jangan menganggap menu nonaktif sebagai route, permission, atau modul yang sudah tersedia.
- Jangan mengganti Fortify, passkeys, route profile, session, atau logout saat mengembangkan modul bisnis tanpa scope khusus.
- Pertahankan `applicationNavigation` sebagai satu sumber metadata navigasi ketika RBAC diimplementasikan.

## Rekomendasi Phase Berikutnya

PHASE 03 sebaiknya memulai modul bisnis yang telah disetujui dengan kontrak data backend, policy/RBAC server-side, dan props Inertia yang menggantikan data presentasi secara bertahap.

## Catatan Handoff

- Dashboard aktif berada di `resources/js/pages/dashboard.tsx`; komposisi dashboard berada di `resources/js/components/dashboard/`.
- Shell reusable berada pada komponen starter yang diperbarui serta `resources/js/components/app/`.
- Gunakan `DashboardPresentationData` sebagai bentuk awal prop sebelum menghubungkannya ke source data yang nyata.
- Setelah browser test tersedia, validasi sidebar expanded/collapsed, Sheet mobile, breadcrumb, user menu, logo, table overflow, serta light/dark secara runtime.
- Jangan memulai PHASE 03 secara otomatis tanpa instruksi baru.
