# Phase 4 – Starter-kit auth & settings, remove Jetstream

Branch: `jetstream-migration/phase-4-auth`. Source: `../plain-starter-kit`.

## Copy in

- [x] `app/Livewire/Actions/Logout.php`
- [x] `app/Livewire/Settings/{Profile,Security,Appearance,DeleteUserForm}.php`, `Settings/TwoFactor/RecoveryCodes.php`
- [x] `app/Concerns/{PasswordValidationRules,ProfileValidationRules}.php`; password rules keep the app policy
      (`symbols()->letters()->numbers()`); removed `Actions/Fortify/{PasswordValidationRules,UpdateUserPassword,UpdateUserProfileInformation}`.
- [x] `FortifyServiceProvider`: starter view bindings + `passkeys` limiter; kept our login limiter.
- [x] `resources/views/livewire/auth/*`, `resources/views/livewire/settings/*`
- [x] `layouts/auth.blade.php`, `layouts/auth/simple.blade.php` (light, KRM logo; `card`/`split` variants dropped), `partials/head` (no `@fonts`, no `@fluxAppearance`), `partials/settings-heading`.
- [x] Components: `auth-header`, `auth-session-status`, `passkey-registration`, `passkey-verify` (guarded by `Features::canManagePasskeys()`),
      `settings/layout` (Appearance link hidden), new `app-logo-icon` (KRM svg).
- [x] `resources/js/passkeys.js` + `@laravel/passkeys`, added as Vite input.
- [x] `routes/settings.php` required from `routes/web.php`; `.well-known/passkey-endpoints` only when passkeys enabled.
- [x] Migration `2026_10_11_000000_create_passkeys_table.php` (not run yet).
- [x] `User`: `PasskeyUser` / `PasskeyAuthenticatable`, `initials()`.
- [ ] `resources/views/flux/*` overrides/icons: none needed yet (only used by the starter sidebar → Phase 5).

## App customisations ported

- Register: honeypot `website`, password hint, privacy policy checkbox (`policy.show` is an app route).
- Login: "Sign up" link only when `register` route exists (`APP_REGISTRATION`).
- Profile: changing email still sends a new verification notification (old Jetstream behaviour).
- Delete account uses `App\Actions\DeleteUser` (detaches participants `added_by`/`claimed_by`). `DeleteUser` is now an invokable action, no Jetstream contract.
- `navigation-menu` is a plain Blade `@include` (was Jetstream Livewire component); profile link → `profile.edit`, removed photo/API token branches.
- App layout has a `flux:toast` group for settings toasts.

## Configure

- [x] `config/fortify.php`: removed `updateProfileInformation`/`updatePasswords`; passkeys via `APP_PASSKEYS` env (default off) + `passkeys` config block and limiter.
- [x] Appearance page/route present, link hidden (dark mode forced off until later).
- [x] Italian translations for all new strings appended to `lang/it.json` (103).
- [ ] Manual check: login, register (APP_REGISTRATION), forgot/reset password, verify email, 2FA enable + challenge, settings pages, delete account, Italian locale.

## Tests

- [x] Deleted (approved): `ApiTokenPermissionsTest`, `CreateApiTokenTest`, `DeleteApiTokenTest`, `BrowserSessionsTest`,
      `ProfileInformationTest`, `UpdatePasswordTest`, `TwoFactorAuthenticationSettingsTest`, `DeleteAccountTest`.
- [x] Auth tests moved to `tests/Feature/Auth/` merged with starter cases; new `TwoFactorChallengeTest`, `Settings/ProfileUpdateTest`, `Settings/SecurityTest` (PHPUnit) – 44 tests.
- [ ] Optional: `UserFactory::withTwoFactor()` state (tests currently `forceFill` 2FA columns).

## Remove Jetstream

- [x] `composer remove laravel/jetstream laravel/sanctum`; `laravel/fortify` ^1.41 now a direct dependency (was transitive via Jetstream!).
- [x] Deleted `config/jetstream.php`, `config/sanctum.php`, `JetstreamServiceProvider` (+ `config/app.php`).
- [x] Deleted `resources/views/{api,profile,auth}`, `emails/team-invitation`, `terms.blade.php`.
- [x] Deleted unused components: `action-section`, `application-logo`, `confirmation-modal`, `confirms-password`, `switchable-team`.
- [x] Removed Jetstream `@source` from `app.css`, `jetstream-modal` class.
- [x] Migration `2026_10_12_070711_drop_personal_access_tokens_table.php`. `current_team_id` never existed (teams were never enabled);
      `users.profile_photo_path` kept on purpose.
- [x] `UserController::destroy()` now uses `App\Actions\DeleteUser` (participants detached) + test.
- [x] `grep -rni jetstream app config routes resources` → only `lang/` / CLAUDE.md docs.
- [x] Full test suite green.

## Notes

- CLAUDE.md "Authentication & Authorization" still mentions Jetstream.
- `bootstrap/cache/{packages,services}.php` had to be deleted after removing Jetstream (stale provider).
