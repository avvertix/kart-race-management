# Phase 3 – Livewire 4 + Flux

Branch: `jetstream-migration/phase-3-livewire` (stacked on phase 2).

## Tasks

- [x] Read the Livewire 4 upgrade guide (livewire.laravel.com/docs/4.x/upgrading) and audit the app:
      - `<livewire:…>` tags all self-closing ✔
      - `wire:model` only on real inputs (Blade components forward it to `<input>`/`<textarea>`); modals use `@entangle` ✔
      - no `.blur`/`.change` modifiers, `wire:scroll`, `wire:transition`, `$js`, `Livewire.hook`, `stream()`, `setUpdateRoute` ✔
      - no `config/livewire.php` (defaults); `LinkedDrivers` has explicit `#[Layout('layouts.app')]` ✔
- [x] `livewire/livewire` ^4.1 (4.4.7) + `livewire/flux` ^2.13 (2.20.1). Jetstream 5 doesn't require Livewire, no conflict.
      `livewire/blaze` skipped for now.
- [x] Flux CSS import, `@source` Flux stubs, class-based `dark` custom variant (keeps app light: no `dark:` classes in the app yet).
- [x] `@fluxScripts` in `layouts/app` and `layouts/guest`. No `@fluxAppearance` (light mode only).
- [x] `Route::get('drivers', LinkedDrivers::class)` → `Route::livewire(...)`.
- [x] Removed unused `alpinejs`, `@alpinejs/focus` (Livewire bundles Alpine incl. focus, mask, collapse, persist, intersect, anchor, sort).
- [x] `npm run build` OK (CSS 468 kB / 49 kB gzip because of Flux stubs).
- [x] Full test suite green on Livewire 4 (1125 passed, 3 skipped), incl. Jetstream profile Livewire components.
- [x] Manual check: participant listing search (debounce), category selector, race number, modals (assign points, wildcard settings),
      date inputs with `x-mask`, Jetstream profile page (2FA, delete account) still on Livewire 4.

## Notes

- Livewire endpoints are now `/livewire-{hash}/…` (hash from APP_KEY). Checked: nothing in `deploy/`, middleware or config matches `/livewire/`.
  Any external reverse proxy/WAF rule on `/livewire/` must be updated.
- Smart `wire:key` is on by default in v4.
- Composer on Windows via `herd.bat`: `^` is eaten by cmd, quote and use `~` or `^^`.
