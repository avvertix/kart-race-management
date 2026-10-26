# A7. Notifications for licence requests and orders

## Goal

Applicants and the organizer know when something needs their attention.

## Docs

- [Notifications](../../docs/reference/notifications.md)

## Scope

- To the applicant's account email (verified):
  - `LicenceRequestReceived` on submit;
  - `LicenceRequestChangesRequested` with the flagged sections and notes;
  - `OrderPriceSet` with amount, payment details and a link to upload the proof;
  - `LicenceEmitted` with a link to the Licence.
- To `races.organizer.email`: `LicenceRequestSubmitted`, also on resubmission.
- Mails in the User's locale; queued.

## Out of scope

Notifications for enrollments and promo codes; emails to the holder or parents.

## Acceptance criteria

- Each event sends exactly one notification to the right recipient.
- No mail contains documents or the fiscal code.
- Notifications are sent after the transaction commits.
