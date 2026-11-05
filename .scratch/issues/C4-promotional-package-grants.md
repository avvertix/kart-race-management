# C4. Promotional Package grants

## Goal

A Package bundles a licence, an enrollment with race number, and Entitlements, granted automatically.

## Docs

- [Set up products and prices: Promotional Packages](../../docs/how-to/organizer/set-up-products-and-prices.md#promotional-packages)
- [Request or renew a licence](../../docs/how-to/driver/request-or-renew-a-licence.md)

## Scope

- `product_grants`: `product_id`, `kind` (`LicenceEmission` | `Enrollment` | `Entitlement`), `licence_type_id` nullable, `championship_id` nullable, `mode`/`amount` (Entitlement), `grants_bib_reservation` (Enrollment).
- Since A5b a licence grant covers one renewal: once the Order is paid, the buyer gets a "Start your licence renewal" link, and the request started from it gets `covering_order_id` and skips its own payment. It names no Licence Type.
- Organizer UI to add grants to a Package.
- A Package with an Enrollment grant: submitting the covered Licence Request creates a `Submitted` Enrollment waiting for the request, paid by the Package's Order. The driver picks the race number from the Enrollment with the B4 picker, also when the championship doesn't grant reservations to everyone (the grant's `grants_bib_reservation`); it is not a section of the request form.
- Drivers can choose a Package including the enrollment on the championship's enrollment page, instead of the enrollment Product. A Package with an Enrollment and no licence renewal is ordered only there, so it always knows its Licence; Packages with a licence renewal enroll through the covered request and are not offered there.
- `licence_type_id` is not stored: since A5b the licence grant names no Licence Type. Entitlements keep the `product_grant_id` that created them, for idempotency.
- Granting on `OrderPaid` and `LicenceEmitted`, whichever comes last: Entitlements created for the Licence. Idempotent.
- Rejecting the request rejects the packaged Enrollment and releases the number; nothing is granted.
- Packages for enrollments (enrollment + Entitlement) granted on `OrderPaid` once the Enrollment has a Licence.

## Acceptance criteria

- Paying before emission grants nothing until emission, and the reverse.
- Running the grant twice creates nothing twice.
- A rejected packaged request leaves no Enrollment, reservation or Entitlement active.
