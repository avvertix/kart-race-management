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

## Out of scope

A My orders list and products navigation (later).

## Acceptance criteria

- A driver is notified when their Enrollment is confirmed or rejected.
- A confirmed Enrollment of a closed enrollment period is still reachable from the dashboard.
