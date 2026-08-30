# SIPAK PROJECT STATUS

## Current Phase

PHASE 01 — Frontend Foundation & shadcn/ui Theme

## Phase Status

Completed

## Date

2026-08-30

## Agent/Model

Codex / GPT-5

## Project Baseline

- Laravel 13.29.0, PHP 8.3.23, React 19.2.8, Inertia Laravel 3.3.1, Inertia React 3.7.0, Tailwind CSS v4, Vite 8, TypeScript, MySQL target database, npm.
- Frontend architecture remains React + Inertia. Livewire is not used.
- shadcn/ui is configured in `components.json` with `radix-luma`, Radix primitives, Lucide, CSS variables, and Tailwind v4.
- The AI Product Blueprint is available at `blueprint/AI PRODUCT BLUEPRINT SIPAK - SISTEM INFORMASI PEMAKAIAN BUKTI SKPD.docx`.
- No prior `PROJECT_STATUS.md` or separate Phase 00 audit artifact was present in the repository when Phase 01 began.

## Completed Work

- Verified the existing shadcn configuration; no blind reinitialization was performed.
- Established the Amber Minimal token baseline in `resources/css/app.css` using semantic CSS variables for light and dark modes: background, foreground, card, popover, primary, secondary, muted, accent, destructive, border, input, ring, chart, and sidebar tokens.
- Set Inter Variable as the global UI font and removed the unused Instrument Sans Bunny font integration.
- Preserved the starter application's existing appearance mechanism; Sonner now consumes its resolved light/dark appearance instead of introducing a second `next-themes` provider.
- Added the missing shadcn foundation primitives: Textarea, Radio Group, Switch, Popover, and Table.
- Verified existing Button, Input, Label, Checkbox, Select, Dialog, Sheet, Dropdown Menu, Tooltip, Card, Badge, Skeleton, Alert, Sonner, Avatar, and Sidebar primitives. Existing starter shell, AppLayout, AuthLayout, and sidebar are retained for Phase 02 adaptation.
- Set the application-name defaults to `SIPAK`. The full product name remains `Sistem Informasi Pengelolaan Bukti SKPD` for future shell and page metadata work.
- Kept visible focus-ring tokens, native form labels, Radix dialog/sheet semantics, keyboard-capable primitives, and reduced-motion-aware upstream transition utilities.
- Added formatter exclusions for repository agent guidance so frontend quality checks target application source rather than rewriting instructions.

## Files Added

- `PROJECT_STATUS.md`
- `resources/js/components/ui/popover.tsx`
- `resources/js/components/ui/radio-group.tsx`
- `resources/js/components/ui/switch.tsx`
- `resources/js/components/ui/table.tsx`
- `resources/js/components/ui/textarea.tsx`
- `resources/js/hooks/use-mobile.ts`

## Files Modified

- `components.json`
- `resources/css/app.css`
- `resources/js/app.tsx`
- `resources/js/components/ui/sonner.tsx`
- Existing `resources/js/components/ui/*` primitives updated to the configured Radix Luma source style.
- `vite.config.ts`
- `boost.json` (formatting baseline)
- `package.json` and `package-lock.json`
- `config/app.php` and `.env.example`

## Files Removed

None.

## Dependencies Added

- `@fontsource-variable/inter` for locally bundled Inter Variable.
- `radix-ui` for the configured Radix Luma component source.
- `shadcn` as the project-local shadcn CLI/tooling dependency.

## Configuration Changes

- `components.json` resolves preset `b1GwReFhg`: Luma style, Amber theme, Neutral base, small radius, Inter, Lucide, and Radix.
- Tailwind v4 remains CSS-first; no `tailwind.config.*` file is required.
- Vite no longer requests Instrument Sans from Bunny; Inter is imported from the local package in `app.css`.
- `vite.config.ts` formatter ignores agent instruction directories and `AGENTS.md`.

## Design System Decisions

- Amber is the action and brand color; neutral surfaces preserve enterprise readability and high information density.
- Components must use semantic tokens such as `bg-primary`, `text-muted-foreground`, and `border-border`; do not distribute raw color values through feature components.
- Inter Variable is the application font; headings inherit the same family for a compact, professional hierarchy.
- Keep use of shadcn/Radix primitives and their accessible APIs. Do not hand-roll dialogs, sheets, menus, tooltips, form controls, tables, or status badges when an installed primitive covers the need.
- Retain the existing `useAppearance` mechanism for class-based dark mode. Do not add a parallel theme provider.

## Current UI State

- The Laravel React Starter Kit shell is intentionally still present. Dashboard and application-shell redesign are not part of this phase.
- Sidebar remains the starter sidebar with only Dashboard navigation. It is ready for Phase 02 information architecture work.
- Auth layouts remain structurally unchanged and inherit the new tokens and typography.

## Tests / Validation

### npm run check

PASS — 73 application/configuration files correctly formatted; no warnings or lint errors in 65 files.

### npm run types:check

PASS — `tsc --noEmit` completed successfully.

### build

PASS — `npm run build` completed successfully and generated the Vite production bundle with local Inter assets.

### backend tests

PASS — `php artisan test --compact`: 39 tests passed, 136 assertions.

### Additional Verification

- `npx shadcn@latest info --json` confirms Tailwind v4, Radix Luma, Amber, Inter, Lucide, and all expected foundation primitives.
- `npm ls next-themes --depth=0` confirms no unused parallel theme dependency remains.
- `git diff --check` completed without whitespace errors before handoff.

## Known Issues

- `npm install` reports an engine warning because installed Node is 22.17.0 while `vite-plus` declares Node `^20.19.0 || ^22.18.0 || >=24.11.0`. Checks, TypeScript validation, build, and tests pass on the current environment.
- The repository retains an untracked `pnpm-lock.yaml` although npm is the declared package manager; it was pre-existing and was not removed in this phase.

## Technical Debt

- Starter-kit Welcome content and starter sidebar footer links still contain Laravel/repository copy. Replace these only when Phase 02 redesigns the shell and dashboard.
- The untracked duplicate legacy `resources/js/hooks/use-mobile.tsx` remains while the updated shadcn Sidebar resolves `resources/js/hooks/use-mobile.ts`. Consolidate it only after confirming no external tooling imports the legacy extension.

## Open Questions

- Confirm the approved SIPAK logo asset before replacing the starter SVG mark.
- Confirm the final Indonesian navigation labels and role-aware sidebar taxonomy during Phase 02.

## Decisions Made

- Did not install Dashboard-01 or build any business module.
- Did not alter Fortify authentication, passkeys, authorization, routes, database schema, or business workflow.
- Did not remove existing starter components; only added verified missing shadcn primitives.
- Did not install `Field`, because current starter forms have native labels and its introduction should coincide with the first domain-form composition.

## Important Business Rules Relevant to Next Phase

- One nomeratur equals one SKPD set with five physical sheets.
- A nomeratur range may be split across different lokets, and cancelled/damaged nomeratur remain counted in sequential use.
- BAP workflow is `draft → waiting_verif_1 → waiting_apprv_1 → waiting_verif_2 → waiting_apprv_2 → waiting_final → waiting_signoff → completed`; clarification interrupts the flow.
- UI status colors should remain purposeful: draft neutral, waiting amber, clarification destructive, completed success treatment to be formally added when the workflow UI requires it.

## Next Recommended Phase

PHASE 02 — adapt the application shell and implement Dashboard-01 using this component and token foundation.

## Handoff Notes

- Start Phase 02 by reading this file, the Blueprint, `components.json`, and `resources/css/app.css`.
- Use `@/components/ui/*` and semantic tokens before introducing custom UI.
- Preserve the existing React + Inertia + Wayfinder architecture. Use generated route helpers rather than hardcoded URLs when frontend routes are introduced.
- Do not start inventory, distribution, BAP, verification, clarification, reporting, audit-trail, or RBAC business logic until their approved phases.
