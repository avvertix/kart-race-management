# E3. The Enrollment carries its option and payment

## Goal

The driver chooses an option while enrolling, and the Enrollment holds its own payment: amount, proof and status, like a Licence Request. No Order is created.

## Docs

- [Enroll in a championship](../../docs/how-to/driver/enroll-in-a-championship.md) (update)
- [Confirm enrollments and race numbers](../../docs/how-to/organizer/confirm-enrollments-and-race-numbers.md) (update)
- [Lifecycles: Enrollment](../../docs/reference/lifecycles.md#enrollment) (update; add the Enrollment payment statuses)

## Scope

- `enrollments` gets `enrollment_option_id`, `amount`, `currency`, `payment_status` (`NotDue`, `AwaitingPayment`, `PaymentSubmitted`, `Paid`), `paid_at`, `payment_confirmed_by`. The amount and currency are copied from the option on submit. `order_id` is removed.
- `Enrollment` implements `Payable` the way `LicenceRequest` does, so `SubmitPaymentProof` and the payment proofs work unchanged.
- Enrollment form, "What you are buying" step: the active options for the chosen licence's category (the previous licence's for a renewal; all options for a request whose type is not known yet). A single option is chosen implicitly.
- The race number step shows when the chosen option reserves one (`Enrollment::grantsBibReservation()` reads the option).
- The Enrollment page shows the amount, bank details and proof upload from the Enrollment's own payment. The organizer confirms or rejects the proof from the review page (`ConfirmEnrollmentPayment`, `RejectEnrollmentPayment`).
- `missingForConfirmation` requires `payment_status` `Paid` or `NotDue`.
- The organizer can switch a submitted Enrollment to another option of the championship until its payment is confirmed, e.g. when the licence category changed on renewal. Switching updates the amount; a proof waiting for review must be rejected first.
- Withdrawal and rejection keep the D5 rule: refused while a proof waits for review. A paid one is refunded outside the platform.
- Notifications: `OrderPaymentConfirmed` and `OrderPaymentRejected` become enrollment payment notifications linking to the Enrollment page.

## Out of scope

Nothing: E3 and E4 are built together, because once Enrollments stop placing Orders the renewal coverage and race credits of D2 and D4 must move to the Enrollment in the same step to keep the app working.

## Acceptance criteria

- A Kartsport driver with an Easyrace licence is offered Free and Promo Easyrace, not the National/International promo.
- A BKQ driver pays the option's amount from the Enrollment page; the organizer confirms it there and then confirms the Enrollment.
- The organizer moves an Enrollment to the other promo before confirming its payment, and the amount follows.
