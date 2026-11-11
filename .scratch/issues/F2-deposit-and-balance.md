# F2. Deposit and balance due date on Enrollment Options

## Goal

An option can ask for a deposit first and the balance by a date, like the moto Gold (€950, deposit €300) and Silver (€600, deposit €200) offers with the balance due by 31 March 2027. Once the deposit is paid, the Enrollment gets everything its option includes.

## Docs

- [ADR 0006](../../docs/adr/0006-enrollment-options.md) (update: deposits)
- [GLOSSARY: Enrollment Option](../../GLOSSARY.md)
- [Lifecycles: Enrollment payment](../../docs/reference/lifecycles.md)

## Scope

- `enrollment_options`: nullable `deposit_amount` (cents, less than the amount) and `balance_due_on` (date, required with a deposit). Organizer form and option list show them.
- `Enrollment::isDepositPaid()`: confirmed records reach the deposit, or the full amount when the option has none; true when nothing is due. It replaces `isPaymentSettled()` where the Enrollment unlocks something: confirmation (`missingForConfirmation`), the covered Licence Request (`LicenceRequest::isPaymentSettled`), the race fee discount (`GrantEnrollmentEntitlements`), race number reservation.
- New payment status "Deposit paid" between Awaiting payment and Paid.
- The driver sees what to pay next: the deposit, then the balance and its due date; paying everything at once is allowed. The organizer's list shows paid and balance.
- The enrollment form shows the deposit and due date of each option.

- Implementation notes:
  - The Enrollment copies `deposit_amount` and `balance_due_on` from its option, like the amount, also when the organizer changes the option. The unreleased `enrollment_options` and `enrollments` migrations are rewritten: a development database needs `migrate:fresh`.
  - `Enrollment::isDepositPaid()` reads the confirmed Payment Records; `isPaymentSettled()` keeps meaning paid in full or nothing due.
  - A proof waiting for review shows as Proof uploaded even after the deposit; what is unlocked follows the records, not the status. Drivers can upload a proof and organizers record payments while the status is To pay or Deposit paid.
  - Without a deposit, the balance due date is ignored. Race number reservations were never tied to payment, so they need no change.
  - Two tests used the Paid status as a shortcut for a paid Enrollment; they now record a real payment.

## Out of scope

Changing the option after the deposit (F3), reminders and overdue (F4).

## Acceptance criteria

- A Gold Enrollment with €300 confirmed can be confirmed, its covered renewal can be emitted, and it gets its 6 race credits; it shows €650 due by 31 March 2027.
- An option without a deposit behaves exactly as before.
