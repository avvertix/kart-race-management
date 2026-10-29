# A5b. Licence catalogue in code, renewals without Products

## Goal

Licence configuration disappears from the application: each Licence Provider has a fixed list of Licence Types in the code, required documents are a fixed set chosen by age, and a Licence Request is a renewal with its own payment instead of an Order. See [ADR 0004](../../docs/adr/0004-licence-renewals-outside-products.md) and `GLOSSARY.md` (Licence Type, Licence Request, Renewal, Product).

A1–A5 have not shipped, so their migrations are rewritten in place rather than patched with new ones. Local databases need `php artisan migrate:fresh --seed` once.

## Docs

- [Add licence providers and platform configuration](../../docs/how-to/maintainer/add-licence-providers-and-configuration.md): rewritten, with a step-by-step "Add or change a Licence Type" section a maintainer can follow to fill in the real lists.
- [Request or renew a licence](../../docs/how-to/driver/request-or-renew-a-licence.md): renewals only, started from My licences.
- [Set up licence types, products and prices](../../docs/how-to/organizer/set-up-licence-types-and-products.md): licence types removed; renamed to "Set up products and prices".
- [Process a licence request](../../docs/how-to/organizer/process-a-licence-request.md), [Lifecycles](../../docs/reference/lifecycles.md), [Roles and permissions](../../docs/reference/roles-and-permissions.md): updated for the request payment and the removed permissions.

## Scope

### Licence Types in code

- One list per provider in `config/licences.php`, read through a small catalogue class returning a typed Data object per entry: `code` (stored, never changed, e.g. `naz-e`), `name` as printed on the licence (not translated), `role` (`Driver` | `Mechanic`), `race_licence_category` (`DriverLicence` value, drivers only), `active` (retired types stay listed so existing Licences keep their name).
- ACI driver types allow "also competitor licence"; ASI types never do. This is a provider rule, not per type.
- Ship with placeholder entries (a few ACI national and international types, Daily, EasyRace, mechanic; one ASI driver and one ASI mechanic). The real lists are added later by following the maintainer doc.
- Validation accepts only active codes of the chosen provider; Declared Licences and requests pick from the same list, no free text.

### Removed

- `LicenceType`, `LicenceGrade`, `LicenceTypeDocument` models, tables, factories, `LicenceTypeController` and views (`/m/licence-types`), `LicenceTypePolicy`, `LicenceTypeSeeder`, the `licence-type:*` permissions and the navigation item.
- `LicenceLevel` (the type implies national or international), `LicenceDocumentScope`, the self-declaration and car driving licence documents.
- Products: the `LicenceEmission` kind, `products.licence_type_id`, `Product::sellsLicence()`, starting a request from the public product page, `SwitchLicenceRequestProduct`.
- `licence_requests.product_id`, `order_id`, `licence_type_id`, `level`, `licence_grade_id`, `grade_other`.

### Data

- `licences`: `licence_type` (code), `includes_competitor` (bool), `role` and `race_licence_category` copied from the type when saved, so removing a type from the list never breaks an existing Licence.
- `licence_requests`: `provider`, `licence_type` (code), `includes_competitor`, `previous_licence_number` (encrypted) and `previous_licence_number_hash` (sha512), plus the payment: `amount` nullable (cents), `currency`, `payment_status` (`AwaitingPrice` | `AwaitingPayment` | `PaymentSubmitted` | `Paid` | `Covered`), `priced_by`, `priced_at`, `paid_at`, `payment_confirmed_by`, `covering_order_id` nullable (set by C4).
- `licence_request_documents`: `document` (enum `LicenceDocument`) and `parent_index` instead of the type document reference, name and kind.
- `LicenceDocument`: `IdentityDocument` (holder, and each parent when underage), `MedicalCertificate`, `Privacy` (signed form), `Delegation` (signed form), `ParentalAuthorisation` (signed form, underage only). Signed forms keep the dompdf pre-filled download.
- Payment proofs attach to the request through the existing `payments` morph.

### Driver flow

- My licences: a **Renew licence** button, which starts an empty renewal asking for the previous licence number; and a **Renew** button on the latest Licence of each holder the User manages, which pre-fills provider, Licence Type, competitor answer, holder, parents and previous number.
- Pre-filling uses only the User's own Licences. A typed previous number that matches another User's Licence is shown to the organizer only (A6).
- First emissions are not requested on the platform: the start page explains to contact the organizer by email.
- Licence section: provider, Licence Type from the list, and for ACI driver types "Do you also need the competitor licence?". The type can differ from last year's.
- Payment section: "the organizer sets the amount after checking your request", then the bank transfer details and proof upload once the amount is set, as in A3. A request covered by a paid package shows "Covered by your order" instead.
- Submit stays possible before payment.

### Kept as they are

Request statuses, sections, change requests, the accordion stepper, the request detail page, `HolderData`, the `licence-documents` disk, Products and Orders for enrollments and packages.

## Out of scope

Organizer review, pricing and payment confirmation of requests (A6), notifications (A7), package grants and the "Start your licence renewal" link after a paid package (C4).

## Acceptance criteria

- No organizer page configures Licence Types or documents; adding a Licence Type is a change to `config/licences.php` plus its maintainer doc steps.
- A Licence keeps showing its type name after the type is deactivated.
- "Also competitor licence" is offered only for ACI driver types.
- An adult holder is asked for identity document, medical certificate, privacy and delegation; an underage holder also for parental authorisation and each parent's identity document.
- Renew on an existing Licence pre-fills the request; Renew licence without one asks for the previous number.
- A request is never linked to a Product or an Order, except `covering_order_id`.
- Licence products cannot be created anymore; the public products page shows no licences.
- Works at 360px width.
