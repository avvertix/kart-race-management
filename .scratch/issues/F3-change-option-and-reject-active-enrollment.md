# F3. Change option, soft withdrawal and the deposit as race credit

## Goal

Drivers and the organizer switch an Enrollment between options (Silver and Gold) until it is paid in full, which only changes what is still due. A confirmed Enrollment is final: only it makes the driver take part in the championship, and the driver never goes back to wildcard. A withdrawn Enrollment is a soft state: the organizer can bring it back by recording a payment before the first race, or turn what was paid into a race fee balance.

## Decisions (with the organizer, 2026-11-11)

- Only confirmed Enrollments count for the championship; everyone else races as a wildcard (already so with the "based on enrollment" wildcard strategy).
- The option can change while the Enrollment is not paid in full: by the driver until the enrollment window closes, by the organizer also afterwards.
- Confirming needs the payment in full. The deposit still unlocks the covered licence renewal and the race fee discount (F2), not the confirmation.
- A confirmed Enrollment can't be withdrawn or rejected, by anyone.
- After the enrollment window closes nobody enrolls a new Licence, the organizer included. The only way back in is a withdrawn Enrollment brought back before the first race.
- A withdrawn Enrollment with confirmed payments: the organizer either records a payment before the championship's first race, which brings it back, or turns what was paid into a race fee balance on the driver's Licence.

## Docs

- [ADR 0006](../../docs/adr/0006-enrollment-options.md) (update: confirmation needs the full payment, soft withdrawal)
- [Lifecycles: Enrollment and its payment](../../docs/reference/lifecycles.md)
- [Confirm enrollments and race numbers](../../docs/how-to/organizer/confirm-enrollments-and-race-numbers.md), [Enroll in a championship](../../docs/how-to/driver/enroll-in-a-championship.md)
- [GLOSSARY: Enrollment](../../GLOSSARY.md)

## Scope

- `ChangeEnrollmentOption` accepts submitted Enrollments not paid in full, also with confirmed payments; the amount, deposit and due date follow the new option, and the status is recomputed from the payment records. A proof waiting for review must be reviewed first. When the paid amount exceeds the new amount, the Enrollment shows the difference to refund outside the platform.
- The driver switches the option from their Enrollment page while the window is open (new policy and route); the organizer from the review page at any time.
- `missingForConfirmation()` asks for the full payment (`isPaymentSettled()`) instead of the deposit. Free options are unchanged.
- `WithdrawEnrollment` and `RejectEnrollment` refuse confirmed Enrollments; the organizer's page no longer offers them.
- Organizer enrollment (`ChampionshipEnrollmentController::create/store`) is refused once the window closes.
- Bring back: `RecordEnrollmentPayment` on a withdrawn Enrollment, before the championship's first race, returns it to submitted, as long as the driver has no other active Enrollment there; its released race numbers are not restored, the driver or the organizer picks one again.
- Race credit: on a withdrawn Enrollment with confirmed payments and no credit yet, the organizer turns the paid amount into a race fee balance Entitlement on the Licence (source: the Enrollment). Once done, the Enrollment can't be brought back.

- Implementation notes:
  - `ChangeEnrollmentOption` takes `byDriver`: the driver needs the window open and an option offered to the licence category (`my-enrollments.option.update`, policy `changeOption`); the organizer neither. It grants the new option's race fee discount when the deposit is reached; one already granted stays.
  - `EnrollmentSettingsData::hasClosed()` blocks organizer enrollment after the period; before it opens the organizer can still enroll.
  - `EnrollmentRevoked` and its wildcard re-evaluation are removed: nothing un-confirms an Enrollment anymore.
  - `RecordEnrollmentPayment` brings a withdrawn Enrollment back (`Enrollment::canBeBroughtBack()`: no race of the championship started, not credited), checking the driver has no other active Enrollment and covering again the open request it waits for.
  - Race credit (`CreditEnrollmentPayment`, route `enrollments.credit`): balance = paid − sum of the discounts recorded on `participant_entitlement` for the Entitlements whose source is the Enrollment; those Entitlements drop to 0 remaining; `enrollments.credited_at` is set (added to the unreleased migration: `migrate:fresh`). It needs a Licence and the championship to use race fee discounts.
  - Overpayment after a cheaper option shows ":amount to refund" on both enrollment pages; race credit leaves it out (at most the Enrollment's amount).
  - A withdrawn Enrollment that waited for a request emitted meanwhile moves to the new Licence when it comes back.
  - Known limits: a withdrawn Enrollment already paid in full can only be credited (no payment left to record); rejected Enrollments are not credited; a covered licence renewal is not deducted from the credit.

## Out of scope

Refunds, which stay outside the platform.

## Acceptance criteria

- Silver with €200 paid switched to Gold shows €750 due, by the driver while the window is open and by the organizer afterwards; Gold with €950 paid can't be switched.
- A Gold Enrollment with only the deposit paid can't be confirmed; once paid in full it can, and it can't be withdrawn or rejected afterwards.
- A withdrawn Enrollment with the deposit paid comes back to submitted when the organizer records the balance before the first race.
- A withdrawn Enrollment with €300 paid gives a €300 race fee balance to the driver's Licence once the organizer turns it into credit, and can't come back afterwards.
- Neither the driver nor the organizer enrolls a new Licence after the window closes.
