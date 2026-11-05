# E4. Renewal coverage and race credits from the Enrollment

## Goal

An option covering the licence renewal pays the Licence Request through the Enrollment, and an option with race credits grants them once the Enrollment is paid and its Licence exists. The rules from D2 and D4 stay, with the Order taken out.

## Docs

- [Lifecycles: Licence Request payment](../../docs/reference/lifecycles.md) (update "Covered")
- [GLOSSARY: Entitlement](../../GLOSSARY.md) (granted by a promo code or an enrollment option)

## Scope

- `licence_requests.covering_order_id` becomes `covering_enrollment_id`. `LicenceRequest::coverWith(Enrollment)` and `isPaymentSettled()`: a covered request counts as paid once the covering Enrollment is paid.
- Renewing while enrolling with an option covering renewal covers the draft request. `ChangeEnrollmentLicence` moves the coverage to the new or chosen request after a rejection (D4 behaviour).
- Withdrawing or rejecting the covering Enrollment returns its open covered requests to their own payment (D2 behaviour of `CancelOrder`).
- `GrantEnrollmentEntitlements` replaces `GrantPackages`: when the Enrollment is paid and when its Licence is emitted or verified, whichever comes last, it creates the option's Entitlement once (`firstOrCreate` with the Enrollment as source). A rejected or withdrawn Enrollment grants nothing.
- `entitlements.product_grant_id` is removed; the Entitlement source is the Enrollment or the Promo Code.
- My licences no longer lists paid Packages with a renewal to start (there are none); the D4 "Renewal covered by" card is removed.
- Port the D2 and D4 tests on coverage and rejected requests to Enrollments.

## Out of scope

Removing the products surface (E5).

## Acceptance criteria

- A request covered by an unpaid Enrollment can't be emitted; once the Enrollment is paid it can.
- After the covered request is rejected, the driver renews again from the Enrollment page and the new request is covered.
- A Kartsport promo Enrollment gives 1 race credit to the emitted Licence once paid, and only once.
