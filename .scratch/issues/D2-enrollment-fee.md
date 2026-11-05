# D2. Enrollment Fee and where enrollments are sold

## Goal

An Enrollment is free or costs a fixed Enrollment Fee. The fee can cover the licence renewal. A Package including an Enrollment replaces the fee. Both are bought only while enrolling.

## Docs

- [ADR 0005](../../docs/adr/0005-enrollment-fee-and-packages.md)
- [GLOSSARY: Enrollment Fee, Product](../../GLOSSARY.md)
- [Championship settings: Enrollment](../../docs/reference/championship-settings.md) (update)

## Scope

- Enrollment settings, under the "fee" option:
  - The fee stays the enrollment Product of the championship, behind the scenes.
  - It only accepts a fixed Price. A range is refused when saved, and an existing range Price is not orderable for enrollment.
  - A new `fee_covers_licence_renewal` setting (default off).
- When the fee covers the renewal:
  - the Enrollment's Order covers the Licence Request it waits for, like a Package's Order (`covering_order_id`);
  - the request needs no payment of its own;
  - a Driver who enrolls with a valid Licence pays the same fee.
- A Package including an Enrollment replaces the fee: one Order, for the Package only.
- An Enrollment grant can only be added to a Package for a championship with enrollment enabled. `CreateEnrollment` refuses a disabled championship for packaged enrollments too.
- On the products page:
  - Enrollment Products and Packages including an Enrollment aren't listed.
  - Ordering them redirects to the championship's enrollment page.
  - Packages with no Enrollment grant stay there.
  - Packages with Entitlement grants but no renewal ask which managed Licence they are for when ordered.
- `PublicProductController::order` authorizes with the Order policy.

## Out of scope

The enrollment page layout (D3). Renewing while enrolling (D4).

## Acceptance criteria

- An enrollment Product can't be ordered from the products page.
- A range Price can't be saved for an enrollment fee.
- With the fee covering renewals, a request started for that Enrollment isn't asked to pay.
- An existing championship's enrollment keeps working.
