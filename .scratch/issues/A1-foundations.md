# A1. Foundations

## Goal

Lay the enums, permissions, policies and storage every later slice relies on, without any visible feature.

## Docs

- [Roles and permissions](../../docs/reference/roles-and-permissions.md)
- [Licences, users and identity](../../docs/explanation/licences-and-identity.md)

## Scope

- `App\Enums\LicenceProvider`: `Aci`, `Asi`, with labels. No `Foreign` case: foreign licences never live on the platform.
- `App\Enums\LicenceRole`: `Driver`, `Competitor`, `Mechanic`, with labels.
- `App\Enums\LicenceLevel`: `National`, `International` (ACI only), each mapping to the existing `DriverLicence` value used by race registration.
- Permissions on `App\Enums\Role`:
  - organizer: `licence-type:*`, `product:*`, `licence-request:list|view|update`, `licence:list|view|update`, `order:list|view|update`, `enrollment:*`, `promo-code:*`;
  - driver: `licence-request:create|view|update`, `licence:create|view|update`, `enrollment:create|view|update`, `order:view`, `promo-code:claim`.
- Policies, auto-discovered: `LicenceTypePolicy`, `ProductPolicy`, `OrderPolicy`, `LicencePolicy`, `LicenceRequestPolicy`. A driver acts only on records where `user_id` is theirs. Empty models and migrations can arrive in the slices that need them; policies may be added there too if the model doesn't exist yet.
- A private disk `licence-documents`, scoped on `local` like `payments`.

## Out of scope

Any model, page or route.

## Acceptance criteria

- Each role has exactly the permissions above, and `admin` still has `*`.
- The enums have translated labels in `resources/lang`.
- Files on `licence-documents` are not publicly reachable.
- Existing role and permission tests still pass.
