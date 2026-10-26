# A5. Licence Request flow (driver)

## Goal

A User requests or renews a licence for themselves or someone they act for, at their own pace, and fixes only what the organizer flags.

## Docs

- [Request or renew a licence](../../docs/how-to/driver/request-or-renew-a-licence.md)
- [Lifecycles: Licence Request](../../docs/reference/lifecycles.md#licence-request)
- Prototype: `.scratch/wireframes/licence-request-form.html`, variant 1 (accordion stepper)

## Scope

- `licence_requests`: ulid, `user_id`, `product_id`, `licence_type_id`, `level` nullable, `licence_grade_id` nullable, `grade_other` nullable, `status` (`Draft` | `Submitted` | `InReview` | `ChangesRequested` | `Forwarded` | `Emitted` | `Rejected`), `holder` (encrypted `HolderData`), `parents` (encrypted list of `HolderData`, 1–2, underage only), `order_id`, `licence_id`, `submitted_at`, `forwarded_at`, `emitted_at`, `rejected_at`, `rejection_reason`, `reviewed_by`.
- `licence_request_documents`: `licence_request_id`, `licence_type_document_id`, `parent_index` nullable, `path`, `original_name`, `mime`, `size`, `hash`, `status` (`Pending` | `Accepted` | `Flagged`), `note`.
- `licence_request_change_requests`: `licence_request_id`, `section` (`licence` | `holder` | `parents` | `documents` | `forms` | `payment`), `note`, `resolved_at`.
- Starting a request = picking a licence Product (plain or Package) from the public page; requires a verified account; places the Order (A3). Switching Product in a Draft cancels that Order and places a new one.
- Livewire `LicenceRequestForm`, accordion stepper: one section open at a time; "Save and continue" saves and opens the next incomplete one; closed sections show status and a one-line summary. Sections: licence (ACI level + grade from the list or "Other, specify"; ASI grade only), holder, parents (only when underage: one required, second optional), documents (per holder, per parent entered, once), forms to sign (download pre-filled PDF via dompdf, upload signed), payment (range or amount, proof upload only once `AwaitingPayment`), review and submit.
- Underage is computed from the holder's birth date at the request date.
- Actions: `SubmitLicenceRequest` (completeness; payment not required), `ResubmitLicenceRequest` (only flagged parts editable, all change requests resolved), `DeleteLicenceRequestDraft` (cancels its Order).
- Request detail page: status timeline, open change requests, order status.
- After a change request: flagged sections open first, accepted ones are read-only.

## Out of scope

The race number section for packages with an enrollment (C4), notifications (A7).

## Acceptance criteria

- A draft survives indefinitely and across devices; each section saves on its own.
- An adult holder never sees the parents section; an underage one cannot submit without one parent and that parent's documents.
- A request can be submitted unpaid; a ranged Order shows the range and no upload.
- After a change request, only flagged sections and documents can be changed, and resubmission is blocked until all are fixed.
- Works at 360px width.
