# D6. Organizer enrollment review and race number confirmation

## Goal

The organizer reviews and confirms an Enrollment from one page: what is missing, the race number and the payment proof. They never jump to the Order.

## Docs

- [Confirm enrollments and race numbers](../../docs/how-to/organizer/confirm-enrollments-and-race-numbers.md) (update)
- [GLOSSARY: BIB Reservation](../../GLOSSARY.md)

## Scope

- The championship enrollments list shows, per Enrollment:
  - holder and Licence, with verified, issued or waiting;
  - Competitor;
  - race number and its status;
  - payment status;
  - a "ready to confirm" or "missing: …" summary.
- `enrollments.show` shows the checks of `missingForConfirmation`. Confirming requires:
  - a verified or issued driver Licence of the championship's year;
  - the Competitor when required;
  - a confirmed payment, when one is due.
- `enrollments.show` also shows the payment, with the proofs and confirm or reject payment, reusing the Order payment actions.
- The race number keeps its own confirmation, at any time, even before payment. Withdrawing or rejecting the Enrollment releases it, as today. The page states that the number is confirmed separately.
- The championship page is clear on every organizer page (title and year in the heading).

## Out of scope

Bulk confirmation.

## Acceptance criteria

- The organizer confirms a payment proof from the Enrollment page.
- An Enrollment with a pending payment can't be confirmed, and the page says why.
- A number confirmed before payment is released when the Enrollment is rejected.
