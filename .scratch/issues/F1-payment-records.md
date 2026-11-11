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

## Out of scope

Deposits (F2), reminders (F4), refunds (outside the platform).

## Acceptance criteria

- Without deposits, an Enrollment is paid once its confirmed records reach its amount, by proof, by cash, or by a mix of both.
- A race registration payment proof uploads and shows exactly as before.
- Confirming a proof without an amount is refused.
