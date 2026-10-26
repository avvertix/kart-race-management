# A6. Licence Request processing (organizer)

## Goal

The organizer reviews requests, sets prices, asks for changes, forwards them to the provider as one PDF, and emits or rejects them.

## Docs

- [Process a licence request](../../docs/how-to/organizer/process-a-licence-request.md)
- [Lifecycles: Licence Request](../../docs/reference/lifecycles.md#licence-request)
- Prototype: `.scratch/wireframes/index.html`, "Review a request", variant A (split view with document viewer)

## Scope

- Queue `/m/licence-requests`, filter by status, oldest submitted first; shows the payment state of each Order.
- Review page, split view: sections and documents on one side with accept/flag and note, the selected document (image or PDF) on the other; the Order with set price (A3) and proof confirm/reject.
- The first organizer action on a `Submitted` request sets `InReview`.
- Actions: `RequestLicenceRequestChanges`, `ForwardLicenceRequest` (requires a `Paid` Order), `EmitLicence` (uploads the temporary licence PDF, number, validity year; creates an `Emitted` Licence from the request's holder, type, level and grade), `RejectLicenceRequest` (from `Submitted`, `InReview`, `Forwarded`, with a reason; cancels an unpaid Order).
- Validity year default: the request's `submitted_at` year, or the next year when submitted in December.
- Single PDF download: a summary page (dompdf) plus the uploaded PDFs and images, in section order.
- **Decide here** the PDF merge dependency: `setasign/fpdi` can't read PDF 1.5+ with compressed xref streams; alternatives are `qpdf`/`ghostscript` in the Docker image, or a ZIP fallback. Needs approval before adding.

## Out of scope

Notifications (A7), packaged enrollments on rejection (C4).

## Acceptance criteria

- Forward is impossible without a paid Order.
- Emit creates the Licence with the number hashed and the holder encrypted, and links it to the request.
- Requests from 1 December default to the next validity year.
- The merged PDF opens and contains every uploaded document, including phone-scanned PDFs.
- Drivers get 403 on every organizer route.
