# B5. Previous numbers and ACI copy of reservations

## Goal

Organizers keep returning drivers on their number with little work.

## Docs

- [Race number ranges: Previous numbers, for organizers](../../docs/reference/race-numbers.md#previous-numbers-for-organizers)
- [Confirm enrollments: Keep last season's numbers](../../docs/how-to/organizer/confirm-enrollments-and-race-numbers.md#keep-last-seasons-numbers)

## Scope

- "Previous numbers" panel on each Enrollment: Participants in past championships matched by licence number hash, by driver name and birth date, or by the claiming/adding User; shows number, championship, race count. Actions: confirm the pending number, or change it to one found (if free).
- ACI-only "Copy reservations from championship X": extend the existing `CopyChampionshipBibReservations` to create confirmed reservations by licence hash, only for 1–999. A Licence that later enrolls with the same number hash sees "Number N is reserved for you" in the picker.
- Hidden for ASI championships.

## Acceptance criteria

- The copy skips numbers already reserved in the target championship and reports them.
- Matching by name and birth date works when the licence number changed.
- The panel never shows other drivers' personal data beyond name and number.
