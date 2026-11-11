# F1. Payment records with amount, channel and review

## Goal

A payment is a record of money received: the amount, how it arrived, and whether the organizer confirmed it. The driver can attach a proof of a bank transfer; the organizer can record cash or credit card payments with no proof. What an Enrollment has paid is the sum of its confirmed records.

## Docs

- [Lifecycles: Enrollment payment](../../docs/reference/lifecycles.md)
- [GLOSSARY](../../GLOSSARY.md): add Payment Record (avoid: transaction, receipt)

## Scope

- `payments`: add nullable `amount` (cents), `channel` (`PaymentChannelType`), `status` (submitted, confirmed, rejected), `recorded_by`, `reviewed_by`, `reviewed_at`; `path` and `hash` become nullable. Race Participant payments keep writing only `participant_id`, `path` and `hash` and behave as today.
- The driver uploads a proof for an Enrollment as today: the record is submitted with no amount and channel bank transfer.
- The organizer confirms a submitted record by typing the amount read on the bank statement, or rejects it. Rejected records are kept.
- The organizer records a payment for an Enrollment received by cash, credit card or bank transfer, with its amount and no proof: it is confirmed at once.
- `Enrollment::paidAmount()`: sum of confirmed records. `EnrollmentPaymentStatus::PaymentSubmitted` is derived from a submitted record instead of stored; Paid when the paid amount reaches the Enrollment amount.
- The Enrollment page (driver and organizer) lists the records: date, channel, amount, status, proof link.
- Licence Requests: confirming a proof sets the record's amount to the request amount; `RecordLicenceRequestPaymentAtOffice` creates a confirmed record without proof. Their payment status stays as today.

- Implementation notes:
  - `PaymentStatus` (submitted, confirmed, rejected) is the record's status. `Enrollment::payment_status` stays stored, so lists keep filtering on it, but `Enrollment::refreshPaymentStatus()` recomputes it from the records after every change: nothing due, Paid once confirmed records reach the amount, Proof uploaded while a proof waits, otherwise To pay. `paidAmount()` and `balance()` read the confirmed records.
  - The driver uploads one proof at a time, as before. The organizer confirms it with the amount received (pre-filled with the balance) or rejects it; `ReviewEnrollmentPayment` takes the amount in cents and refuses to confirm without one. `RecordEnrollmentPayment` (route `enrollments.payment.record`) records cash, card or transfer payments while no proof waits for review.
  - The payment confirmed notification says what is still to pay after a partial payment.
  - Licence Requests: confirming a proof records the request amount on it; paying at the office creates a confirmed record without proof. `ParticipantPaymentController::show` answers 404 for a record without proof.
  - `Enrollment::latestPayment()` is removed: both enrollment pages list every record.
  - The option of an Enrollment can no longer change once anything is paid (F3 decides how changes after a deposit work). A withdrawn or rejected Enrollment with confirmed records tells the driver to ask for a refund.
  - Proofs uploaded on a development database before the migration have no status: `migrate:fresh` (Enrollments and Licence Requests are not on `main`).

## Out of scope

Deposits (F2), reminders (F4), refunds (outside the platform).

## Acceptance criteria

- Without deposits, an Enrollment is paid once its confirmed records reach its amount, by proof, by cash, or by a mix of both.
- A race registration payment proof uploads and shows exactly as before.
- Confirming a proof without an amount is refused.
