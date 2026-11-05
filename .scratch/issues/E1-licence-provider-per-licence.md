# E1. Licence provider per licence

## Goal

The Licence Provider belongs to each Licence, not to the championship. Fourstroke accepts ACI and ASI licences with no setting. The championship's provider is kept only for the wording of the licence label.

## Docs

- [GLOSSARY: Licence Provider](../../GLOSSARY.md) (update: "a championship accepts licences from any provider, plus foreign licences")
- [Championship settings](../../docs/reference/championship-settings.md) (update)
- [Lifecycles](../../docs/reference/lifecycles.md) (update the provider mentions)

## Decisions

- Licence numbers are unique across providers, so Licences are matched by number whatever the provider.
- The championship's provider setting no longer restricts enrollment; it only names who issues local licences in `DriverLicence::description()` and the race rules (local versus foreign).

## Scope

- Enrollment no longer filters by provider:
  - `Licence::scopeEnrollableIn`, `scopeRenewableFor` and `LicenceRequest::scopeEnrollableIn` drop the provider condition;
  - `SubmitEnrollment` and `ChangeEnrollmentLicence` no longer pass the championship's provider to `StartLicenceRenewal`.
- Renewing a licence not in the account: the driver chooses the provider in the Licence Request form, as for any request. `StartLicenceRenewal` drops its `$provider` parameter.
- Race numbers from past seasons match by licence number for every provider:
  - `Championship::allowsReservationCopy()` is removed and the copy is always offered;
  - `FindPreviousNumbers` keeps matching by licence number and by holder.
- The "ACI promotion export" is renamed the verified drivers export (class, controller, route, labels), and is offered on national and international races whatever the provider. `Championship::offersAciExport()` is removed.
- Label wording: where `licenceProviderName()` is shown, a championship without a provider shows the platform-wide one as today.
- A submitted request still has to fit the championship's year and driver role (D1); only the provider check goes.

## Out of scope

Enrollment options (E2 onwards).

## Acceptance criteria

- An ASI and an ACI licence can both be enrolled in a championship with no provider chosen, and in one with ACI chosen.
- Copying reservations works for an ASI championship, matching by licence number.
- The verified drivers export is offered for a national race of an ASI championship.
