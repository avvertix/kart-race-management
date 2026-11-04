# B1. Championship licence provider and enrollment settings

## Goal

New championships choose their Licence Provider and whether, when and how drivers enroll.

## Docs

- [Championship settings](../../docs/reference/championship-settings.md)
- [Configure a new championship](../../docs/how-to/organizer/configure-a-new-championship.md)

## Scope

- `LicenceSettingsData::$provider` nullable, falling back to `races.licence.provider`. Licence descriptions (`DriverLicence`, `CompetitorLicence`) and the ACI promotion export read the championship's provider; the export is offered only for ACI championships.
- `EnrollmentSettingsData` on `championships.enrollment` (JSON, nullable): `enabled`, `opens_at`, `closes_at`, `product_id` (enrollment Product; none means free), `grants_bib_reservation`, `requires_competitor_when_underage` (default on).
- Organizer UI on its own Enrollment settings page, linked from the championship page; the provider choice on create and on the Accepted licences page.
- Public championship page shows enrollment open/closed in the configured period.

## Acceptance criteria

- Existing championships (no settings) behave exactly as today, including licence wording and exports.
- Enrollment can't be enabled without opens and closes dates; the Product must be an enrollment Product of that championship.
