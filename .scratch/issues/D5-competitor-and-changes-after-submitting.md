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

- Implementation notes:
  - The adult and not-the-driver rules are checked in `CreateEnrollment` and `SetEnrollmentCompetitor` (`Enrollment::isValidCompetitor`), not in `Licence::scopeCompetitorIn`, because holders are encrypted.
  - The driver picks among the Licences they manage, the organizer among all. The current Competitor is always listed, so saving never clears it by accident. Removing the Competitor is allowed; confirmation requires it when the driver is underage.
  - While a payment proof waits for review, the organizer can't reject the Enrollment either, so a proof confirmed later can't pay a rejected one.
  - An enrolled declared Licence shows "ask the organizer to correct it" instead of the Modify button.

## Out of scope

Organizer review layout (D6).

## Acceptance criteria

- An underage Driver can't be their own Competitor.
- A packaged Enrollment missing a Competitor can get one and then be confirmed.
- An enrolled declared Licence can't be edited or deleted.
- An Enrollment can't be withdrawn while its payment proof is under review.
