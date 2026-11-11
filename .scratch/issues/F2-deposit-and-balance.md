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

## Out of scope

Changing the option after the deposit (F3), reminders and overdue (F4).

## Acceptance criteria

- A Gold Enrollment with €300 confirmed can be confirmed, its covered renewal can be emitted, and it gets its 6 race credits; it shows €650 due by 31 March 2027.
- An option without a deposit behaves exactly as before.
