# Phase 1 – Decouple roles/permissions from Jetstream

Branch: `jetstream-migration/phase-1-roles`. No UI change. Jetstream stays installed, but app code stops calling it.

## Tasks

- [x] Roles as a backed enum `App\Enums\Role` (`label()`, `description()`, `permissions()`, `options()`),
      same keys/labels/permissions as the former `Jetstream::role()` definitions.
- [x] `App\Rules\ValidRole` replacing `Laravel\Jetstream\Rules\Role` (same "must be a valid role" message).
- [x] `app/HasRole.php`: `userRole(): ?Role`, `hasRole(Role|string)`, typed returns. Dropped the `tokenCan` branch
      (it was dead code: `HasApiTokens` was never imported in the trait).
- [x] `UserController`, `CommunicationMessageController`, `communication/partials/form`, `user/index`, `BroadcastCommunications`.
- [x] `CreateNewUser`, `CreateNewUserWithRole`, `AddUserCommand`: dropped `Jetstream::hasTermsAndPrivacyPolicyFeature()`
      (feature was off, so `terms` was never validated – unchanged behaviour).
- [x] `App\Actions\Jetstream\DeleteUser` → `App\Actions\DeleteUser` (still implements Jetstream `DeletesUsers` until Phase 4).
- [x] `JetstreamServiceProvider` reduced to `deleteUsersUsing()`.
- [x] Routes: `auth:sanctum` → `auth`, `config('jetstream.auth_session')` → `auth.session`.
- [x] `User`: removed `HasApiTokens`, `HasProfilePhoto`, `profile_photo_url` append; removed photo handling
      from `UpdateUserProfileInformation`.
- [x] Tests: `HasRoleTest` rewritten/extended, new `Unit/RoleTest`, `Rules/ValidRoleTest`, `RegistrationTest` without `terms`.
- [x] Full test suite green (1125 passed, 3 skipped – Jetstream API token tests, feature disabled).

## Left for Phase 4

- `composer remove laravel/sanctum` (no longer used by app code; `personal_access_tokens` table can be dropped with a forward migration).
- `config/sanctum.php`, Jetstream API token views/tests.
- Register view still shows a required "privacy policy" checkbox (HTML-only `required`); decide whether to validate `terms` server side in the new register page.
