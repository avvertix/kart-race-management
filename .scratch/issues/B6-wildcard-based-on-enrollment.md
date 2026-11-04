# B6. Wildcard based on enrollment

## Goal

In championships using it, only Participants with a confirmed Enrollment score points; everyone else races as a Wildcard.

## Docs

- [Enrollment, points and wildcards](../../docs/explanation/enrollment-and-wildcards.md)
- [Championship settings: Wildcards](../../docs/reference/championship-settings.md#wildcards)

## Scope

- `WildcardStrategy::BASED_ON_ENROLLMENT = 40`, its action in `app/Actions/Wildcard/`, the `resolve()` case and translations.
- Matching by licence number hash of the Participant against confirmed Enrollments' Licences.
- On `EnrollmentConfirmed` / `EnrollmentRevoked`, re-evaluate that Driver's Participants in races without results.
- "Recalculate wildcards" button on the race page when wildcards are on, sharing `RecalculateWildcards` with `participants:recalculate-wildcard`.
- The rule is offered in the wildcard settings only to users with early access to licences and products.

## Acceptance criteria

- Confirming an Enrollment mid-season turns future and current-without-results participations into non-wildcards; races with results are untouched until recalculated.
- Existing strategies behave as before.
