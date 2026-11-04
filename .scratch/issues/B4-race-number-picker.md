# B4. Race number picker

## Goal

During enrollment a driver picks a free Championship BIB, helped by the numbers they used before.

## Docs

- [Race number ranges: What the race number picker shows](../../docs/reference/race-numbers.md#what-the-race-number-picker-shows)
- Prototype: `.scratch/wireframes/index.html`, "Race number picker", variant C (hundreds overview, zoom in)

## Scope

- Livewire picker (`RaceNumberPicker`) using `GenerateRaceNumber` for the first free numbers; states shared with `PickRaceNumber` through `RaceNumberAvailability`. Fix the `driver_name` label bug in `FreeRaceNumbers`.
- Hundreds overview (1–99, 100–199, …, 900–999) with free counts; zoom into a hundred; a text input to jump to a number.
- States: free, reserved (pending or confirmed), already used (raced in this championship by another Driver). The public view shows states only; organizers also see holders.
- "Numbers you used before": from Participants the User claimed or added (`claimed_by`, `added_by`), with number, championship and driver; pickable when free.
- Shown in enrollment when `grants_bib_reservation` is on; creates or changes a `Pending` reservation for the Enrollment; confirmed with it; released on rejection or withdrawal. The pick is optional at submit; the driver can pick or change it from their enrollment while submitted, the organizer from the enrollment review.
- A licence that already holds a reservation in the championship (e.g. copied from last season) can't pick another number.
- A number picked while waiting for a Licence Request is bound to the licence number when the Licence is emitted; until then race registration can't check it against the licence.

## Acceptance criteria

- Two drivers can't hold the same number: the second pick fails with a clear message (lock as in race registration).
- A driver can change their pick until confirmation, not after.
- Usable at 360px width; every cell reachable by keyboard.
