# Phase 2 – Tailwind CSS 4 + Vite

Branch: `jetstream-migration/phase-2-tailwind`.

## Tasks

- [x] Ran `npx @tailwindcss/upgrade --force` (79 templates migrated, `tailwind.config.js` removed).
- [x] `resources/css/app.css`: `@import 'tailwindcss'`, `@plugin` forms + typography, `@source` pagination views,
      Jetstream vendor views (remove in Phase 4) and compiled views.
- [x] Switched to `@tailwindcss/vite`; removed `postcss.config.js`, `postcss`, `autoprefixer`, `@tailwindcss/postcss`.
- [x] Upgraded `vite` ^8.3.4 and `laravel-vite-plugin` ^3.2.0 (same as starter kit). `vite-plus` skipped.
- [x] Removed `ring-opacity-*` (not supported in v4, tool left it): `focus:ring-orange-200/50`, `ring-black/5`.
- [x] PHP class strings in `app/`: none using renamed utilities.
- [x] Compatibility CSS: default border colour (`gray-200`) and pointer cursor on buttons.
- [x] PDF/print views with inline `<style>` (`prints/*`, `*-pdf`) untouched by the tool; they don't load the Vite bundle.
- [x] `npm run build` succeeds.
- [x] Full test suite green (1125 passed, 3 skipped).
- [x] Visual pass (manual): dashboard, championship, race, participant create/show, public registration, auth pages, user menu dropdown, modals.

## Notes

- `resources/js/app.js` is empty; `alpinejs` and `@alpinejs/focus` are unused devDependencies → remove in Phase 3.
- Laravel pagination vendor views still use v3 class semantics (`shadow-sm`, `focus:ring`): slightly different look; publish/restyle in Phase 5 if paginated lists exist.
- Boost guidelines / `.claude/skills/tailwindcss-development` still describe Tailwind v3 → regenerate with `php artisan boost:update` (touches CLAUDE.md).
- Visible v4 differences to watch: `space-y-*`/`space-x-*` now use `:not(:last-child)` margin-bottom, `hover:` only on hover-capable devices, `shadow-sm`/`rounded-sm` scale shift (handled by the tool).
