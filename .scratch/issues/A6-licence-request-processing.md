# A6. Licence Request processing (organizer)

## Goal

The organizer reviews renewal requests, sets their amount, confirms the payment, asks for changes, forwards them to the provider, and emits or rejects them.

## Docs

- [Process a licence request](../../docs/how-to/organizer/process-a-licence-request.md)
- [Lifecycles: Licence Request](../../docs/reference/lifecycles.md#licence-request)
- Prototype: `.scratch/wireframes/index.html`, "Review a request", variant A (split view with document viewer)

## Scope

- Queue `/m/licence-requests`, filter by status, oldest submitted first; shows the payment state of each request. Drafts are listed only when filtered for, to answer "I started a request" emails.
- Review page, split view: sections and documents on one side with accept/flag and note, the selected document (image or PDF) on the other; each document opens or downloads on its own (one merged PDF is [A9](A9-licence-request-single-pdf.md)).
- The licence section shows the Licence Type, the competitor answer and the previous licence number, flagging when that number matches a Licence managed by another User.
- The request payment (A5b): set the amount, confirm or reject the proof. A request covered by a package shows the covering Order and needs no payment.
- The first organizer action on a `Submitted` request sets `InReview`.
- Actions: `RequestLicenceRequestChanges`, `ForwardLicenceRequest` (requires the payment `Paid` or `Covered`), `EmitLicence` (uploads the temporary licence PDF, number, validity year; creates an `Emitted` Licence from the request's holder, provider, Licence Type and competitor answer), `RejectLicenceRequest` (from `Submitted`, `InReview`, `Forwarded`, with a reason).
- Emit form: the number is pre-filled with the previous licence number for ACI, which keeps it across years, and empty for ASI, which assigns a new one.
- Validity year default: the request's `submitted_at` year, or the next year when submitted in December.

## Out of scope

Notifications (A7), packaged enrollments on rejection (C4), the single merged PDF (A9).

## Acceptance criteria

- Forward is impossible without a paid or covered request.
- Emit creates the Licence with the number hashed and the holder encrypted, and links it to the request.
- Requests from 1 December default to the next validity year.
- An ACI emit form starts with the previous number; an ASI one is empty.
- Drivers get 403 on every organizer route.
