# B3. Enrollments

## Goal

Drivers enroll a Licence (or an open Licence Request) in a championship; organizers confirm, reject, withdraw or enroll on behalf.

## Docs

- [Enroll in a championship](../../docs/how-to/driver/enroll-in-a-championship.md)
- [Confirm enrollments and race numbers](../../docs/how-to/organizer/confirm-enrollments-and-race-numbers.md)
- [Enrollment, points and wildcards](../../docs/explanation/enrollment-and-wildcards.md)
- [Lifecycles: Enrollment](../../docs/reference/lifecycles.md#enrollment)

## Scope

- `enrollments`: ulid, `championship_id`, `licence_id` nullable, `licence_request_id` nullable (exactly one set), `competitor_licence_id` nullable, `user_id`, `status` (`Draft` | `Submitted` | `Confirmed` | `Rejected` | `Withdrawn`), `order_id` nullable, `confirmed_at`, `confirmed_by`, `rejection_reason`.
- Driver flow (open period only), on the public enrollment page: pick a managed driver Licence valid in the championship year (start date year), of the championship's provider when set, or a submitted, open Licence Request; name a Competitor Licence (a managed Licence including the competitor licence; required when underage on the championship start date if the setting is on); submit. When not free, submitting places the Order for the enrollment Product and leads to its payment page. Withdraw while `Submitted`.
- No `Draft` status: the Enrollment is created on submit. Packages including the enrollment come with C4.
- One active Enrollment per Licence per championship.
- When the Licence is emitted (`EmitLicence`, there is no event), Enrollments waiting for that request relink to the new Licence. On request rejection they stay `Submitted`.
- Organizer: list per championship filtered by status, oldest first; confirm (requires a verified or emitted Licence, a paid Order when paid, a Competitor when required; verify a declared Licence from the same screen), reject with reason, withdraw; enroll any Licence at any time, without an Order.
- Confirming confirms the pending race numbers picked with the Enrollment; rejecting or withdrawing releases them (picked from B4).
- Rejecting or withdrawing before payment cancels the Order.
- Fire `EnrollmentConfirmed` / `EnrollmentRevoked` for B6.

## Out of scope

The race number picker (B4), packaged enrollments from licence requests (C4), notifications.

## Acceptance criteria

- Championships without enrollment enabled show nothing new.
- An enrollment waiting for a request can't be confirmed until the Licence is emitted.
- An unverified declared Licence can't be confirmed until verified.
- A driver can't withdraw a confirmed enrollment.
