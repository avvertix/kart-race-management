# Statuses and lifecycles

## Licence Request

| Status | Meaning | Next |
|---|---|---|
| Draft | Being filled in; kept for as long as needed | Submitted |
| Submitted | Sent with the payment proof | In review |
| In review | The organizer checks documents and payment | Changes requested, Forwarded, Rejected |
| Changes requested | The organizer flagged documents or sections to redo | Submitted |
| Forwarded | Sent to the Licence Provider | Emitted, Rejected |
| Emitted | The temporary licence PDF is uploaded and the Licence is created | — |
| Rejected | Closed without a licence | — |

## Licence

| Status | Meaning |
|---|---|
| Emitted | Created from a Licence Request |
| Declared | Entered by the User, not yet verified |
| Declared, verified | Entered by the User and checked by the organizer |

## Enrollment

| Status | Meaning | Next |
|---|---|---|
| Draft | Being filled in | Submitted |
| Submitted | Waiting for the organizer. When the licence for the championship year is not valid yet, it also waits for the licence | Confirmed, Rejected, Withdrawn |
| Confirmed | Accepted by the organizer. Confirms the race number reservation | Withdrawn |
| Rejected | Not accepted | — |
| Withdrawn | Cancelled by the driver or the organizer | — |

The organizer always confirms an Enrollment by hand. Free enrollments only skip the payment step. Refunds for withdrawn or rejected paid enrollments, and for rejected licence requests, are settled by email with the organizer, outside the platform.

## BIB Reservation

| Status | Meaning | Next |
|---|---|---|
| Pending | Picked by a driver during enrollment; blocks the number for others. The driver can change their pick | Confirmed, Released |
| Confirmed | Confirmed with the Enrollment, or created by the organizer. Only the organizer can change it | Released |
| Released | Freed by the organizer | — |

Pending reservations don't expire. Reservations created by the organizer may have an expiry date.

## Order

| Status | Meaning | Next |
|---|---|---|
| Awaiting payment | Created; no payment proof yet | Payment submitted |
| Payment submitted | A payment proof is uploaded | Paid, Awaiting payment |
| Paid | The organizer confirmed the payment; what was bought is granted | — |

## Promo Code

| Status | Meaning |
|---|---|
| Unclaimed | Issued, not yet claimed |
| Claimed | Claimed for one Licence or licence number; has become an Entitlement |
