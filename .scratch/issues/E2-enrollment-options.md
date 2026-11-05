# E2. Enrollment options

## Goal

A championship offers one or more enrollment options instead of an enrollment fee Product and Packages. Each option is what a driver enrolls with: free or a fixed amount, with optional extras. This slice adds the options and their settings; E3 makes enrollments use them.

## Docs

- ADR 0006 (new, supersedes [ADR 0005](../../docs/adr/0005-enrollment-fee-and-packages.md)): enrollment options replace Products, Packages and Orders for enrollment.
- [GLOSSARY](../../GLOSSARY.md): add **Enrollment Option**; Enrollment Fee and Promotional Package become an option's amount and extras.
- [Championship settings: Enrollment](../../docs/reference/championship-settings.md) (update)

## Decisions

- An option has a fixed amount; no ranges, no price periods. Dates can be added later when a championship needs them.
- Options can be limited to licence categories (`DriverLicence`), e.g. Kartsport's "Promo Easyrace" and "Promo National/International".
- Nothing is sold outside enrollment: promo codes hand out race credits on their own.
- Nothing was deployed: the unreleased migrations of this branch are rewritten instead of adding drop migrations (exception to the forward-only rule in the README, for migrations never run outside development).

## Scope

- `enrollment_options` table and `EnrollmentOption` model, per championship:
  - `ulid`, `championship_id`, `title`, `description`, `active`, `sort`;
  - `amount` in cents (0 is free), `currency`;
  - `covers_licence_renewal`, `reserves_race_number` (booleans);
  - `entitlement_mode` and `entitlement_amount`, both nullable: race credits or balance granted with the enrollment;
  - `licence_categories`: the `DriverLicence` values it is for, empty meaning any.
- Organizer CRUD on the championship's enrollment settings page: list, add, edit, deactivate. An option chosen by an Enrollment can't be deleted, only deactivated.
- `EnrollmentSettingsData` keeps `enabled`, `opens_at`, `closes_at`, `requires_competitor_when_underage`. `product_id`, `fee_covers_licence_renewal` and `grants_bib_reservation` are removed (their migrations and settings form are rewritten).
- Turning enrollment on requires at least one active option.
- `EnrollmentOption::isFor(?DriverLicence $category)` and a scope listing the active options for a category.

## Out of scope

Enrollments using options (E3).

## Acceptance criteria

- The organizer configures the four 2027 cases: Kartsport (Free; Promo Easyrace; Promo National/International, each covering renewal, reserving a number and 1 race credit), Fourstroke (Free), BKQ (one option covering renewal), and a championship without enrollment.
- An option with an amount below zero, or race credits without an amount of credits, is refused.
