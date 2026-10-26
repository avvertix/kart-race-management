# A4. Licences: declare, list, verify

## Goal

Users manage the Licences of the people they act for; organizers verify declared ones.

## Docs

- [Licences, users and identity](../../docs/explanation/licences-and-identity.md)
- [Request or renew a licence: Already have a licence?](../../docs/how-to/driver/request-or-renew-a-licence.md#already-have-a-licence)
- [ADR 0001](../../docs/adr/0001-licence-as-identity.md)

## Scope

- `HolderData` (Spatie Data): first and last name, sex, birth date, birth place, fiscal code (nullable), nationality, email, phone, residence address. Fields mirror the Participant competitor fields; reuse `FiscalCodeFormatRule` and `DateFormat`. A DTO so company holders (VAT, no fiscal code or birth date) can extend it later.
- `licences`: ulid, `user_id` (manager), `provider`, `licence_type_id` nullable, `role`, `level` nullable, `licence_grade_id` nullable, `grade_other` nullable, `race_licence_category`, `number` (encrypted), `number_hash` (sha512), `validity_year`, `holder` (encrypted `HolderData`), `source` (`Emitted` | `Declared`), `verified_at`, `verified_by`, `temporary_licence_path`, `licence_request_id` nullable.
- Unique: `number_hash` + `validity_year` + `provider`. A second User declaring the same number and year is told to contact the organizer.
- Driver pages: **My licences** (`/licences`: managed Licences, open requests, "Request a licence", "Add a licence I already have"), **Declare a licence** form (ACI or ASI only), Licence detail with the temporary licence PDF via a signed URL.
- Organizer: licences list (filter by provider, year, declared and unverified), verify action.
- Navigation: *Licences* for drivers, next to *Drivers and competitors*; `LinkedDrivers` unchanged.

## Out of scope

Emitted Licences (created by A6), editing a Licence's number after verification.

## Acceptance criteria

- A User sees and edits only the Licences they manage; organizers see all.
- Number and holder are encrypted at rest; the hash matches `participants.driver_licence` for the same number.
- Duplicate declaration of the same number and year is refused.
- Verified Licences can no longer be edited by the User.
