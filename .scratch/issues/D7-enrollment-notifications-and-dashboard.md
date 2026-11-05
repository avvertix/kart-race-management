# D7. Enrollment notifications and dashboard

## Goal

Drivers learn what happened to their Enrollments without looking for them, and find them all from the dashboard.

## Docs

- [Enroll in a championship](../../docs/how-to/driver/enroll-in-a-championship.md) (update)

## Scope

- Notifications to the Enrollment's User, by email and in the app, like the Licence Request ones:
  - Enrollment confirmed;
  - Enrollment rejected, with the reason;
  - Enrollment needs action, when its request is rejected or a Competitor is missing;
  - payment confirmed or rejected.
  - Each links to the Enrollment page.
- `ConfirmOrderPayment` notifies the buyer. For a Package with an unused renewal, the notification links to My licences.
- The dashboard `DriverEnrollments` component:
  - lists the account's Enrollments in current and future championships, in any status, each linking to its Enrollment page;
  - keeps the "Open for enrollment" list;
  - shows to anyone allowed to enroll.

- Implementation notes:
  - "Needs action" is sent when the request is rejected, and when a missing Competitor shows up: an underage driver enrolled by a Package, or found when the request is submitted. It records which of the two it is about.
  - Rejecting a request sends both its rejection and one "needs action" per waiting Enrollment.
  - A packaged Enrollment that can't be created when the request is submitted is only logged; when the renewal is started, the failure shows as a warning.
  - The dashboard lists up to 10 Enrollments of this season's and future championships, newest first.

## Out of scope

A My orders list and products navigation (later).

## Acceptance criteria

- A driver is notified when their Enrollment is confirmed or rejected.
- A confirmed Enrollment of a closed enrollment period is still reachable from the dashboard.
