# Statuses and lifecycles

## Licence Request

| Status | Meaning | Next |
|---|---|---|
| Draft | Being filled in; kept for as long as needed. The User can delete it | Submitted |
| Submitted | Sent to the organizer | In review, Rejected |
| In review | Set on the organizer's first action (marking a document as viewed, or commenting). The organizer reviews the documents and sections, like a pull request review | Changes requested, Verified, Rejected |
| Changes requested | The organizer finished the review sending the comments to fix | Submitted |
| Verified | The organizer finished the review with every document viewed and nothing to fix, and set the amount to pay | With the provider, Emitted, Rejected |
| With the provider | Optional: handed to the Licence Provider, waiting for the licence. Requires the payment to be paid or covered | Emitted, Rejected |
| Emitted | The temporary licence PDF is uploaded and the Licence is created. Requires the payment to be paid or covered | — |
| Rejected | Closed without a licence | — |

A Licence Request is for one validity year, chosen when it is started: the year renewals are open for (the next one from December), or the next year while a championship of that year is open for enrollment. The emitted Licence is for that year when Enrollments wait for the request.

A Licence Request is a renewal and is paid on its own, unless the Enrollment waiting for it covers it:

| Payment status | Meaning | Next |
|---|---|---|
| Awaiting the amount | The organizer sets the amount when verifying the request. No payment proof can be uploaded yet | To pay |
| To pay | The amount is set; no payment proof yet. The organizer can still change it | Proof uploaded, Paid |
| Proof uploaded | A payment proof is uploaded | Paid, To pay |
| Paid | The organizer confirmed the bank transfer proof, or recorded a cash or credit card payment at the office | — |
| Covered | The Enrollment the request waits for pays the renewal, as its option covers it. It counts as paid once that Enrollment's deposit, or its full amount without a deposit, is paid; if the request is rejected, it covers a new one. Withdrawing or rejecting the Enrollment, or moving it to an option that doesn't cover the renewal, returns the request to its own payment | — |

## Licence

| Status | Meaning |
|---|---|
| Emitted | Created from a Licence Request |
| Declared | Entered by the User, not yet verified |
| Declared, verified | Entered by the User and checked by the organizer |

## Enrollment

| Status | Meaning | Next |
|---|---|---|
| Submitted | Created when the driver submits the form. Waiting for the organizer. When it names an open Licence Request instead of a Licence, it also waits for the licence | Confirmed, Rejected, Withdrawn |
| Confirmed | Accepted by the organizer. Confirms the race number reservation | Withdrawn |
| Rejected | Not accepted | — |
| Withdrawn | Cancelled by the driver while submitted, or by the organizer at any time, but not while a payment proof waits for review | — |

The organizer always confirms an Enrollment by hand. Free enrollments only skip the payment step. A Driver has at most one submitted or confirmed Enrollment per championship, matched by licence number or by the holder (fiscal code, or name and birth date). Only a driver Licence valid in the championship's year, or a driver Licence Request for that year, can be enrolled. Underage is judged on the championship start date; the Competitor of an underage Driver is an adult other than the Driver, and can be named until confirmation. An Enrollment can't be withdrawn or rejected while a payment proof waits for review, and its declared Licences can't be changed or removed while it is active. A Declared Licence must be verified before its Enrollment is confirmed. When the Licence Request an Enrollment waits for is emitted, the Licence must be for the request's year, and the Enrollment moves to the new Licence, unless the Driver is already enrolled in that championship, which rejects it; when it is rejected, the Enrollment stays submitted until the organizer rejects it or the User picks another Licence or starts a new request, keeping its payment and race number; an option that covered the rejected request covers the new one. An Enrollment can also wait for a draft request, started while enrolling. Refunds for withdrawn or rejected paid enrollments, and for rejected licence requests, are settled by email with the organizer, outside the platform.

### Enrollment payment

| Status | Meaning |
|---|---|
| Nothing to pay | A free option, or enrolled by the organizer |
| To pay | The amount of the option chosen, or what is left of it, by bank transfer with the Enrollment as reason |
| Proof uploaded | Waiting for the organizer to confirm it with the amount received, or reject it |
| Deposit paid | The option asks for a deposit and the confirmed Payment Records reach it: the Enrollment gets everything its option includes (confirmation, the covered Licence Request, the race fee discount); the balance is due by the option's date |
| Paid | The confirmed Payment Records reach the amount. Without a deposit, only now a Licence Request the option covers counts as paid, the Enrollment can be confirmed and the option's race fee discount is granted once the Licence exists |

The status follows the Enrollment's Payment Records. The driver uploads one bank transfer proof at a time; the organizer confirms it with the amount read on the bank statement, which may be less than what is due, or rejects it, and rejected proofs are kept. The organizer can also record a payment received by cash, credit card or bank transfer, with its amount and no proof, while no proof waits for review. Payments add up: the Enrollment stays To pay until they reach the deposit, when the option asks for one, then Deposit paid until they reach its amount.

The organizer can move a submitted Enrollment to another option until its payment is confirmed; the amount follows.

## BIB Reservation

| Status | Meaning | Next |
|---|---|---|
| Pending | Picked by a driver during enrollment; blocks the number for others. The driver can change their pick | Confirmed, Released |
| Confirmed | Confirmed with the Enrollment, or created by the organizer. Only the organizer can change it | Released |
| Released | Freed by the organizer | — |

Pending reservations don't expire. Reservations created by the organizer may have an expiry date. Reservations created before statuses existed are confirmed. A released reservation can't be changed.

## Promo Code

| Status | Meaning |
|---|---|
| Unclaimed | Issued, not yet claimed. Anyone with the code can claim it. The organizer can revoke it |
| Revoked | Withdrawn by the organizer before being claimed |
| Claimed | Claimed for one Licence valid in the championship's year, or for a licence number typed in a race registration; has become an Entitlement |

A licence number claim moves to a Licence with that number once the Licence is emitted or verified. The championship's year is the year it starts.
