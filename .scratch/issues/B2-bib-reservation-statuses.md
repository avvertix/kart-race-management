# B2. BIB Reservation statuses and the 1–999 cap

## Goal

Reservations can be pending, confirmed or released, and Championship BIBs stay in 1–999.

## Docs

- [Lifecycles: BIB Reservation](../../docs/reference/lifecycles.md#bib-reservation)
- [Race number ranges](../../docs/reference/race-numbers.md)

## Scope

- `bib_reservations`: add `status` (defaults to confirmed, so existing reservations become confirmed), `enrollment_id`, `licence_id`, `created_by_user_id`, `released_at`. The organizer's creations, imports and copies record who made them.
- Pending and confirmed reservations block other drivers in `ensureBibNotReservedByOtherDriver`; released ones don't.
- Cap new and edited reservations at 1–999 in `CreateBibReservation`, `BibReservationController` and the import (already in place). Existing reservations above 999 stay valid and stay editable while their number doesn't change.
- Released reservations don't hold their number anywhere: registration checks, suggested and free numbers, wildcards, import, copy, and new reservations.
- Organizer actions: confirm, change number (the existing edit), release. Confirm and release are in early access; released reservations can't be edited.

## Acceptance criteria

- Every existing reservation still blocks and matches as before.
- A pending reservation blocks the number in race registration for other licences.
- Creating a reservation for 1000 or above is refused; editing a legacy one above 999 keeps working.
