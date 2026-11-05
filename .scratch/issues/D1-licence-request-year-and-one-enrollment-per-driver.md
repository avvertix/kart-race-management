# D1. Licence Request year and one Enrollment per Driver

## Goal

A Licence Request is for an explicit validity year, so an Enrollment can only wait for a request of the championship's year. A Driver has at most one active Enrollment per championship.

## Docs

- [GLOSSARY: Licence Request, Enrollment](../../GLOSSARY.md)
- [Lifecycles: Enrollment](../../docs/reference/lifecycles.md#enrollment) (update)
- [Enroll in a championship](../../docs/how-to/driver/enroll-in-a-championship.md) (update)

## Scope

- `licence_requests.validity_year`, nullable and additive. Backfill existing rows from `validityYearFor(submitted_at ?? created_at)`.
- The year is chosen when the request is created. It is the current year, or the next one from December, or earlier while a championship of that year has enrollment open.
- `LicenceRequest::scopeEnrollableIn` filters by the championship's year, `role = Driver`, and the provider when set.
- When issuing the licence, the year defaults to the request's year. `EmitLicence` refuses a different year while active Enrollments wait for the request.
- `Enrollment::missingForConfirmation` checks that the Licence is a driver licence and that its year matches the championship's.
- `Enrollment::ensureNotEnrolled` matches by Driver as well:
  - first by the licence number hash;
  - otherwise by the holder's fiscal code;
  - across the Licences and Licence Requests of active Enrollments.
- When `EmitLicence` relinks an Enrollment to a Licence that already has an active Enrollment in the same championship, the relinked one is rejected with a reason.

- Implementation notes:
  - `LicenceRequestController::store` accepts `validity_year` among `LicenceRequest::requestableYears()`. No form offers the next year yet; the enrollment page will (D4).
  - The year shows on the request pages and the review.
  - The fiscal-code match loads the championship's active Enrollments in PHP, because holders are encrypted.
  - The holder of a request found at emission is matched with `HolderData::identityKey()`, so a request edited to the same holder after enrolling is caught.

## Out of scope

Creating the request from the enrollment page (D4).

## Acceptance criteria

- A request for 2026 can't be enrolled in a 2027 championship.
- A mechanic licence request can't be enrolled.
- The same Driver can't be enrolled twice through a request and a declared Licence.
- An Enrollment with a Licence of the wrong year can't be confirmed.
