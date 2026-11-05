# D5. Competitor rules and changes after submitting

## Goal

An underage Driver always gets a valid Competitor, which can be set later. Nothing a driver changes after submitting can corrupt an Enrollment.

## Docs

- [GLOSSARY: Enrollment, Competitor](../../GLOSSARY.md)
- [Lifecycles: Enrollment](../../docs/reference/lifecycles.md#enrollment) (update)

## Scope

- `Licence::scopeCompetitorIn` and `CreateEnrollment` refuse:
  - the Driver's own Licence;
  - a holder who is underage at the championship's start.
- When no competitor Licence is available, the competitor step explains how to add one instead of hiding the field.
- The driver (on the Enrollment page) and the organizer (on the review page) can set or change the Competitor until the Enrollment is confirmed. This also covers packaged Enrollments created without one.
- `LicencePolicy::update/delete` refuse a declared Licence while an active Enrollment references it.
- Withdrawing is refused while a payment proof is waiting for review. A refund after withdrawing a paid Enrollment happens outside the platform, and the Enrollment page says so.
- Picking or changing the race number follows the enrollment window. The organizer can still change it at any time.

## Out of scope

Organizer review layout (D6).

## Acceptance criteria

- An underage Driver can't be their own Competitor.
- A packaged Enrollment missing a Competitor can get one and then be confirmed.
- An enrolled declared Licence can't be edited or deleted.
- An Enrollment can't be withdrawn while its payment proof is under review.
