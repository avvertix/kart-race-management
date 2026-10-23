# Phase 5 – App layout & shared components on Flux

Working on branch `jetstream-migration/phase-4-auth` (continued there on request).
Light mode only for now (no `class="dark"` on `<html>`, no `@fluxAppearance`).

## Layout

- [x] Header layout chosen (serves both guests and logged-in users). `layouts/app.blade.php` rewritten:
      `flux:header` + `flux:navbar`, Flux mobile sidebar (`collapsible="mobile"`), `flux:profile` user dropdown
      (Settings, Log out), Sign in/Register for guests, `print:hidden` on navigation. Uses `partials.head`.
- [x] Nav items in `partials/navigation.blade.php`, rendered as `flux::navbar.item` or `flux::sidebar.item`; same `@can` rules as before.
- [x] Kept `x-banner`, `$header` slot, `x-broadcast-communications`, footer, `@stack('modals')`, toast group.
- [x] `x-language-selector` rebuilt on `flux:dropdown`/`flux:menu` (mobile: language links in the sidebar).
- [x] Deleted `navigation-menu`, `x-nav-link`, `x-responsive-nav-link`, `x-dropdown-link`.
- [x] Championship + race tabs → `flux:navbar scrollable` with orange accent (`[--color-accent-content:...]`);
      race tabs now also visible (scrollable) on mobile.
- [x] Policy page on `x-app-layout`; deleted guest layout, `GuestLayout`, `x-authentication-card(-logo)`.
- [ ] Remove `App\View\Components\AppLayout` in favour of an anonymous component? (only if worth it; 68 usages of `x-app-layout`, name can stay).
- [x] Print/PDF views untouched.

## Shared components (keep names, implement with Flux)

Flux free (2.20) includes button, input, textarea, select, checkbox, radio, field, label, error, dropdown, menu, modal,
navbar, navlist, sidebar, table, card, badge, callout, toast, tooltip, separator, heading, text. Not free: tabs, date picker, autocomplete.

- [x] `x-button`, `x-secondary-button`, `x-danger-button`, `x-button-link`, `x-secondary-button-link` → thin `flux:button`
      wrappers (primary / outline / danger; Flux default look). `x-button` still defaults to `type="submit"`.
      Pass Flux props through the wrapper as `icon:trailing="..."` (kebab `icon-trailing` is lost when spreading attributes).
- [x] `x-input`, `x-textarea`, `x-input-date` → `flux:input` / `flux:textarea` wrappers (date keeps `x-mask` via Flux `mask` + `x-data`).
      `x-input type="file"` stays a native input (Flux file input is a different widget). Classes on `x-input` land on
      Flux's wrapper div; input-only classes go through `class:input="..."`.
- [x] `x-validation-errors` → `flux:callout variant="danger"`.
- [x] All 27 plain `<select>` (outside `prints/`) → native `flux:select` (plain `<option>` kept); styling classes dropped,
      `text-sm` → `size="sm"`, `w-auto` where the select sits inline (Flux defaults to full width).
- [ ] Remaining plain controls: `shared_bib_licences` textarea (championship/edit), number inputs in point-scheme form
      and free-race-numbers, radios in assign-points / race-communications.
- [x] Kept native, restyled to Flux look: `x-label` (`flux:label` is `<ui-label>`, no `for=` association outside `flux:field`),
      `x-input-error`, `x-checkbox` (plain POST forms rely on native `name`/`checked`/`required`; orange style already matches).
- [x] `x-dropdown` (2 usages: `championship/participant/index`, `race/partials/heading`) → `flux:dropdown` / `flux:menu`
      with new `x-menu-item` (icon slot + description); `x-dropdown` deleted.
- [x] Flux look aligned with the old components in `app.css` (unlayered, so per-usage utilities need `!`):
      button focus ring + darker pressed state, menu fade/scale in-out, roomier menu items, `shadow-lg`.
      User menu redesigned (avatar + name/email), language menu marks the current locale.
- [x] `x-modal`, `x-dialog-modal` → `flux:modal` (only usage: `livewire/assign-points-button`, inlined; both components
      and the unused `@stack('modals')` removed).
- [x] `x-tab-link` → `flux:navbar.item`
- [x] `x-banner` → dismissible `flux:callout` (styles: success, danger, warning, anything else → secondary).
      Server-rendered only (the unused `banner-message` JS event was dropped); layout adds page padding.
- [-] ~~`x-action-message` → Flux toast~~ – scrapped, keep own.
- [-] ~~`x-table` → `flux:table`~~ – scrapped, keep own.
- [ ] Keep own: `x-input-date`, `x-search-input`, `x-options-selector`, `x-price`, `x-time`, `x-race-status`
- [ ] Remix icons (`x-ri-*`) keep working; decide whether to move to Flux icons later.

## Done when

- [x] Visual pass of every main screen (admin + driver role + public pages, desktop + mobile).
- [x] Unused components deleted (audit: every remaining component/layout/partial is referenced); dead `is-invalid` classes removed.
- [x] Full test suite green (fixed `NationalityInputTest`: `$this->blade()` needs `withViewErrors([])` since `flux:input` reads `$errors`).
