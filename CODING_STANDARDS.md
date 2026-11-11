# Coding standards

Judgement rules for reviewing a diff. Mechanical rules are checked by tools: Pint (`composer lint:check`), Larastan (`composer types:check`, with `phpstan-baseline.neon`), and `tests/Unit/ItalianTranslationsTest.php` for missing Italian strings.

## Consistency under concurrency

- **Lock order**: lock the championship first, then the Licence Request or Enrollment it touches, ordered by id when locking several. A new action follows the order of `CreateEnrollment` and `ChangeEnrollmentLicence`.
- **Recheck under the lock**: what was validated before the transaction (a request still open, an Enrollment still submitted) is checked again after locking, as `CreateEnrollment` rechecks `enrollableIn`.
- **Side effects inside**: changes that must hold together with the main change (rejecting a duplicate, moving Enrollments to a new request) run in the same transaction. Notifications extend `AccountNotification`, queued after commit, so sending them inside a transaction is fine.

## Payments

- A record covered by another counts as paid only once the covering one unlocks it: an Enrollment's deposit, or its full amount without a deposit (see `LicenceRequest::isPaymentSettled`).
- An action that cancels, rejects or withdraws something with money attached says what happens to a payment proof waiting for review.

## Server and form agree

- The server accepts exactly what the form offers: when a form filters choices (licences not enrolled, renewals not covered, adult competitors), the validation rule applies the same filter, usually from one shared method such as `EnrollmentSubjects::for`.
- A hidden step or field that still posts a value is either disabled or ignored by the server.

## Identity

- People are matched by `HolderData::identityKey()` (fiscal code, else name and birth date) and Licences by `number_hash`. Holders are encrypted, so identity matches run in PHP over a query narrowed in SQL first.

## Tests

- A fixture is changed only when the rule under test is unchanged; the test's name and assertions still describe the behaviour.
- Factory data that a rule reads (holder identity, birth date, provider) is set explicitly in tests about that rule.
