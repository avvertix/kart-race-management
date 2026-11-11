# F3. Change option and reject an active Enrollment

**Blocked:** the rules marked "to agree with the organizer" below.

## Goal

The organizer moves an Enrollment between options (Silver to Gold or back) after the deposit, which only changes what is still due, and can reject an Enrollment whose balance was never paid.

## Scope

- `ChangeEnrollmentOption` accepts submitted and confirmed Enrollments, also with confirmed records. A submitted record must be reviewed first. The amount follows the new option; renewal coverage and race numbers follow as today.
- When the paid amount exceeds the new amount, the Enrollment shows the difference to refund outside the platform.
- `RejectEnrollment` accepts confirmed Enrollments too, with a reason; race numbers are released as today. A renewal already emitted stays.
- To agree with the organizer:
  - the race fee discount already granted when the option changes: kept, or replaced while unused ("it depends");
  - unused race credits when rejecting for non-payment.

## Acceptance criteria

- Silver with €200 paid moved to Gold shows €750 due; Gold with €950 paid moved to Silver shows €350 to refund.
- A confirmed Enrollment with an unpaid balance can be rejected and its race numbers are released.
