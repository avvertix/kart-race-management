# F5. Enrollment Option next to the driver

## Goal

At the track, staff see which option a driver enrolled with (e.g. Gold includes Saturday free practice), without the platform modelling what the option includes beyond its description.

## Scope

- Race participant list and participant page show the option title of the driver's active Enrollment in the championship, linking to the Enrollment for those allowed to see it.
- Participants without an Enrollment show nothing.

- Implementation notes:
  - A participant is matched to the active Enrollment (submitted or confirmed) of the championship whose Licence has the same number hash, with `Enrollment::activeByLicenceHash()`: one query for the whole list. Enrollments still waiting for a Licence Request have no number yet and show nothing.
  - Everyone who sees the participant sees the option title; it links to the Enrollment only for users who review enrollments and have early access.

## Acceptance criteria

- A Gold driver shows "Gold" in the participant list of each race of the championship.
