# Implementation plan: licences, enrollment, promo codes

Source of truth for behaviour: `docs/` (how-to, reference, explanation), `GLOSSARY.md`, `docs/adr/0001-0003`.
Wireframes: `.scratch/wireframes/index.html`, see "Prototype" at the end.

Rules for every phase:

- Opt-in per championship ([ADR 0002](../../docs/adr/0002-opt-in-per-championship-settings.md)): new settings default to today's behaviour.
- Race registration (`RegisterParticipant`, `UpdateParticipantRegistration`, `ParticipantValidationRules`, exports) keeps its shape. New data only pre-fills it.
- Forward-only migrations, additive and nullable on existing tables.
- People's personal data is stored encrypted (same as `participants.driver`). Licence numbers are also stored as `sha512` hashes, matching `participants.driver_licence`, so the existing hash lookups work.
- New code uses typed Spatie Data objects for personal data (e.g. `HolderData`). Converting `Participant` arrays stays postponed.
- One PR per slice, tests green before the next slice.

## Phase A: Licence requests (first, target before renewals open in December)

### A1. Foundations

- `App\Enums\LicenceProvider` (`Aci`, `Asi`) with labels.
- `App\Enums\LicenceRole` (`Driver`, `Competitor`, `Mechanic`).
- Permissions on `App\Enums\Role`:
  - organizer: `licence-type:*`, `product:*`, `licence-request:list|view|update`, `licence:list|view|update`, `order:list|view|update`;
  - driver: `licence-request:create|view`, `licence:create|view|update` (own only, enforced in policies).
- Policies: `LicenceTypePolicy`, `LicenceRequestPolicy`, `LicencePolicy`, `OrderPolicy`, `ProductPolicy`. Driver access is scoped to `user_id`.
- Private disk `licence-documents` (scoped on `local`, like `payments`).

### A2. Licence Types and required documents (organizer)

- `licence_types`: ulid, `provider`, `role`, `name`, `description`, `race_licence_category` (nullable int, the existing `DriverLicence`/`CompetitorLicence` value it maps to), `active`, timestamps.
- `licence_type_documents`: `licence_type_id`, `name`, `description`, `kind` (`upload` | `signed_form`), `template` (nullable view name for the pre-filled form), `underage_only` (bool), `required` (bool), `sort`.
- Organizer CRUD pages under `/m/licence-types`, following the `ChampionshipCategoryController` pattern.
- Seeder with the ACI driver (adult and underage), competitor and mechanic document lists from the brief.

### A3. Products, Prices, Orders, payments

- `products`: ulid, `kind` (`LicenceEmission` | `Enrollment` | `Package`), `title`, `description`, `championship_id` nullable, `licence_type_id` nullable, `active`.
- `prices`: `product_id`, `amount` (int cents), `currency`, `starts_at`, `ends_at` nullable. `Product::currentPrice()` picks the Price valid now.
- `orders`: ulid, `user_id`, `status` (`AwaitingPayment` | `PaymentSubmitted` | `Paid` | `Cancelled`), `total`, `currency`, `paid_at`, `confirmed_by`.
- `order_lines`: `order_id`, `product_id`, `price_id`, `description`, `amount`.
- `payments`: add nullable morph `payable_type`/`payable_id`, and make `participant_id` nullable if it isn't already. Existing rows and routes stay unchanged.
- Organizer: products and prices CRUD, payment confirmation on the order (`ConfirmOrderPayment` action; it fires `OrderPaid`, where grants hook in later).
- `product_grants` is **not** built here; it arrives with packages in Phase C.

### A4. Licences

- `licences`: ulid, `user_id` (manager), `provider`, `licence_type_id` nullable, `role`, `race_licence_category` nullable, `number` (encrypted), `number_hash` (sha512), `validity_year`, `holder` (encrypted, `HolderData`), `source` (`Emitted` | `Declared`), `verified_at`, `verified_by`, `temporary_licence_path`, `licence_request_id` nullable.
- `HolderData`: first/last name, birth date and place, fiscal code (nullable for foreigners), nationality, sex, residence address, email, phone. It reuses `FiscalCodeFormatRule` and `DateFormat`.
- Driver pages:
  - **My licences** (`/licences`): managed licences, open requests, "Request a licence", "Add a licence I already have".
  - **Declare a licence** form.
  - Licence detail, with the temporary-licence download through a signed URL.
- Organizer: list of licences, plus a "verify" action for declared ones.
- Navigation: add *Licences* to `partials.navigation` for drivers, next to *Drivers and competitors* (LinkedDrivers stays unchanged).

### A5. Licence Request flow (driver)

- `licence_requests`:
  - ulid, `user_id`, `licence_type_id`, `status` (`Draft` | `Submitted` | `InReview` | `ChangesRequested` | `Forwarded` | `Emitted` | `Rejected`);
  - `holder` (encrypted `HolderData`), `parents` (encrypted, list of `HolderData`, underage only), `order_id`, `licence_id`;
  - `submitted_at`, `forwarded_at`, `emitted_at`, `rejected_at`, `rejection_reason`, `reviewed_by`.
- `licence_request_documents`: `licence_request_id`, `licence_type_document_id`, `path`, `original_name`, `mime`, `size`, `hash`, `status` (`Pending` | `Accepted` | `Flagged`), `note`.
- `licence_request_change_requests`: `licence_request_id`, `section` (`licence_type` | `holder` | `parents` | `documents` | `forms` | `payment`), `note`, `resolved_at`.
- Livewire component `LicenceRequestForm` with the generic sections: type, holder, parents (underage only), documents, forms to sign, payment, review. Each section saves the draft, and drafts never expire. Wireframe variant to pick: see Prototype.
- Underage is computed from the holder's birth date against the request date.
- Pre-filled forms rendered with `barryvdh/laravel-dompdf` (already installed) from Blade templates named by `licence_type_documents.template`.
- Actions: `SubmitLicenceRequest` (validates completeness and creates the Order from the Licence Type's product), `ResubmitLicenceRequest`.
- Request detail page with a status timeline and the open change requests.

### A6. Licence Request processing (organizer)

- Queue page `/m/licence-requests`, filterable by status, oldest submitted first.
- Review page: sections and documents with accept/flag, payment proof with confirm. Wireframe variant to pick: see Prototype.
- Actions: `RequestLicenceRequestChanges`, `ForwardLicenceRequest` (requires a paid Order), `EmitLicence` (uploads the PDF, number and validity year; creates the `Licence`), `RejectLicenceRequest`.
- `EmitLicence` default validity year: the request's `submitted_at` year, or the next year when submitted in December.
- Single merged PDF of the request: the summary page (dompdf) plus uploaded PDFs and images. **Needs a dependency** to merge PDFs (e.g. `setasign/fpdi`); approval required.

### A7. Notifications

- To the applicant: `LicenceRequestReceived`, `LicenceRequestChangesRequested`, `LicenceEmitted`.
- To the organizer, sent to `races.organizer.email`: `LicenceRequestSubmitted`, which also covers resubmissions.

### A8. Retention

- Command `licence-requests:prune-documents`, scheduled daily in `routes/console.php`. It deletes uploaded documents 12 months after the end of the licence's validity year, or 12 months after rejection. Temporary licence PDFs are kept with the Licence.

### Open decisions for Phase A

1. ~~Encryption at rest~~ **Decided:** private disk plus volume encryption at deployment, documented in the deploy notes. No `Crypt` on file contents.
2. **PDF merge dependency**, for the single PDF (A6): still open, decided inside the A6 issue. `setasign/fpdi`'s free parser can't read PDF 1.5+ files with compressed xref streams; alternatives are `qpdf`/`ghostscript` in the Docker image, or a ZIP fallback.
3. ~~Organizer recipient~~ **Decided:** `races.organizer.email`.

### Decisions from review (2026-10-26)

- An Order is created as soon as the User picks the Product, locks the current Price, and can be paid later.
- A Licence Request's payment section lists the plain emission Product and every Package that includes its Licence Type. Package grants that need a Licence (Entitlement, BIB right) are applied when the Order is Paid **and** the Licence is Emitted, whichever comes last.
- Licence Request: `InReview` is set on the organizer's first action. Drafts can be deleted (their Order is cancelled). Rejection is allowed from `Submitted`, `InReview` and `Forwarded`. Orders get a `Cancelled` status.
- `HolderData` mirrors the existing Participant competitor fields (first/last name, fiscal code, nationality, email, phone, birth date and place, residence address). Parents use the same DTO. It's a DTO so company holders (VAT, no fiscal code or birth date) can extend it later.
- `LicenceProvider` stays `Aci | Asi`. Foreign licences are never declared or emitted; they only appear in race registrations, as today.
- Declared Licences: same number and validity year can't be declared by two Users. Enrollment confirmation requires verification. Anonymous promo claims move only to emitted or verified Licences.
- Enrollments reference a Licence **or** an open Licence Request of the same User, and relink on emission. Drivers can withdraw while `Submitted`.
- Promo Codes are claimable only for a Licence valid in the championship year (year of the championship start). Guest claims happen in the registration transaction; invalid codes are a validation error.
- A Licence Request starts by picking a Product (plain emission or Package); the Order is created then. Switching Product in a Draft cancels the Order and creates a new one. Submit doesn't require a payment proof; Forward requires a Paid Order.
- ACI requests ask national/international plus a type from an organizer-configured list with "Other, specify"; ASI requests have no level. Prices per type are not modelled: offers are Products.
- `HolderData` includes sex. Underage: one parent or guardian required, a second optional; identity documents are required per parent entered. A self-declaration document is always required.
- A Package's BIB right is used through an Enrollment; the race number can already be picked during the licence order (see round 3).
- Enrollment payment lists the enrollment Product plus Packages that include it. Organizers can enroll on behalf at any time.
- Promo codes: created one or N at a time, random 8 chars or custom for a single code, no expiry, unclaimed codes can be revoked. The public page shows code, QR, championship, the Entitlement and usage; no personal data. Wallet passes stay in Later.
- Issues: one Markdown file per slice in `.scratch/issues/`.
- **Licence Grade**: a Licence Type is provider + role and holds the documents. ACI types have a level (national | international) mapping to the race licence category, plus an organizer list of common Grades per level. Requests and Licences (emitted and declared) store level + grade (from the list or free text). Each licence Product names one Licence Type.
- Packages can include "enrollment in championship X". Picking one adds a Race number section to the request. Submit creates a Submitted Enrollment waiting for the request, with a Pending BIB Reservation. Rejecting the request rejects the Enrollment and releases the number.
- **Public catalogue, account to order.** Active Products and their current Price are listed on a public licences page, and each Product has a public page (what's included, price, required documents, why an account is needed, sign-in/up returning to the page). Placing an Order requires a signed-in User with a verified email (Fortify's `verified` middleware); every applicant notification goes to that account email. No one-time code for holder or parent emails; nothing is sent to them.
- **Slices and cut line.** A1–A8, B1–B7, C1–C4 as written in `.scratch/issues/`. A1–A7 ship before renewals open in December; A8 can follow (nothing is pruned before 2028). B then C target the start of the 2027 season, with C4 (Package grants) last. Until C4, packages are sold as plain fixed/ranged Products and their extras applied by hand. A ranged Price pre-fills the final amount with its lower bound.
- **Prices are fixed or ranged.** `prices` gets `amount` (fixed) or `amount_min`/`amount_max` (range). A ranged Order starts in `AwaitingPrice` and locks the Price valid at placement. The organizer sets the final amount during review, pre-filled from the locked Price; an amount outside the range needs a reason shown to the applicant. The amount is editable until a proof is uploaded. Proof upload is enabled only from `AwaitingPayment`. New notification `OrderPriceSet` to the applicant.
- Previous race numbers: the picker shows "Numbers you used before" from Participants the User claimed or added (suggestion only). The organizer gets a "Previous numbers" panel per Enrollment (match by licence hash, by name + birth date, or by claiming User; confirm or change). ACI-only action "Copy reservations from championship X" creates confirmed reservations by licence hash.
- Race registration for logged-in users: driver, competitor and mechanic Licence pickers, filtered by role and by validity in the race's year. The promo code field is shown to everyone; when logged in it claims for the picked Licence.
- Entitlement selection: oldest first, credits before balance; a credit covers the full category fee, a balance covers up to what is left. Usage is released on participant deletion, race cancellation or fee change.

## Phase B: Enrollment and race number picker (outline)

- `EnrollmentSettingsData` on `championships.enrollment` (JSON): `enabled`, `opens_at`, `closes_at`, `product_id`, `grants_bib_reservation`, `requires_competitor_when_underage`. Organizer UI on the championship edit page.
- `LicenceSettingsData::$provider` (nullable, falls back to `races.licence.provider`). Licence descriptions and the ACI export read the championship's provider.
- `enrollments`: ulid, `championship_id`, `licence_id`, `competitor_licence_id` nullable, `user_id`, `status` (`Draft` | `Submitted` | `Confirmed` | `Rejected` | `Withdrawn`), `order_id` nullable, `confirmed_at`, `confirmed_by`.
- `bib_reservations`:
  - add nullable `status` (null = legacy, treated as confirmed), `enrollment_id`, `licence_id`, `created_by_user_id`;
  - pending reservations block other drivers in `ensureBibNotReservedByOtherDriver`;
  - cap new and edited reservations at 1–999 in `CreateBibReservation`, the controller and the import.
- Race number picker (Livewire), reusing `FreeRaceNumbers`/`GenerateRaceNumber` (also fix the `driver_name` label bug). Public states: free, reserved, already used.
- `WildcardStrategy::BASED_ON_ENROLLMENT = 40`: the action, the `resolve()` case and the translations. Confirming or withdrawing an Enrollment re-evaluates participations in races without results. A "recalculate wildcards" button on the race page reuses the logic of the `participants:recalculate-wildcard` command.
- Race registration for logged-in users: a Licence picker that pre-fills the form, and the Competitor pre-filled from the last race. `participants.licence_id` is a nullable informative link; the licence hash stays the matching key.

## Phase C: Promo codes, Entitlements, packages (outline)

- `championships.entitlements_enabled` (or a Data field), requiring `bonus_enabled = false`. Plus a nullable race-level override, like `races.bonus_enabled`, with `Race::isEntitlementEnabled()`.
- `promo_codes`: ulid, `code` (unique), `championship_id`, `mode` (`Credit` | `Balance`), `amount`, `claimed_at`, `claimed_by_user_id`, `licence_id` nullable, `licence_hash` nullable (anonymous claim).
- `entitlements`: `championship_id`, `licence_id` nullable, `licence_hash`, `mode`, `amount`, `source_type`/`source_id` (promo code or order line). `participant_entitlement` pivot with `amount`, like `participant_bonus`.
- Pipeline stage `ApplyEntitlementToParticipant` next to `ApplyBonusToParticipant`, skipped unless entitlements are enabled for the race. A matching remove stage on cost change. `Participant::calculateParticipationCost()` adds the entitlement discount, and `RegistrationCostData` stays as it is.
- Race registration form gets a promo code field for guests. For logged-in users, Entitlements of the matched licence hash apply automatically.
- When a Licence is created with a matching `number_hash`, anonymous claims move to it.
- Public code page with a QR code (reusing the BaconQrCode setup from `Participant::qrCodeSvg`) showing usage.
- `product_grants` for Promotional Packages (licence emission, Entitlement, race number reservation right), applied on `OrderPaid`.

## Later

Typed `Participant` data, company Competitors, Draft Participants (half-filled race registrations), wallet passes, online payment, ASI export, removing `shared_bib` after this season.

## Prototype

Low-definition wireframes in one static file: `.scratch/wireframes/index.html` (open it in a browser, no build). The yellow bar walks through screens in flow order. Screens with a design question have variants, switched with the bottom bar or the ← → keys:

| Screen | Variants | Verdict |
|---|---|---|
| Fill in a licence request | A wizard · B single page with checklist · C section hub | **Accordion stepper** (from `.scratch/wireframes/licence-request-form.html`, variant 1): one page, one section open at a time, "Save and continue" opens the next incomplete one, closed sections show status and a one-line summary, flagged sections open first after a change request and accepted ones are read-only. Rejected: step screens (overview hidden behind a sheet), long form with autosave (overwhelming on a first visit by a parent on a phone), section hub (too much navigation). |
| Review a request (organizer) | A split view with document viewer · B section checklist | **A** |
| Race number picker | A full 1–999 grid · B type a number, get suggestions · C hundreds overview, zoom in | **C** |
| Register for a race | A logged in · B guest (states, not alternatives) | — |

Once picked, record the verdicts here and fold them into A5/A6/B.
