# F6. Licence types and renewal price per instance

## Goal

Each deployment declares which licences it handles and the renewal price, so the moto instance only works with its ASI licence types and shows €50 for a renewal outside enrollment. Kart and moto run as separate deployments.

## Docs

- [Add licence providers and configuration](../../docs/how-to/maintainer/add-licence-providers-and-configuration.md)
- `deploy/.env.example`

## Scope

- `LICENCE_TYPES`: comma-separated `provider.code` list (e.g. `asi.ma,asi.lkm`); empty means every type, as today. It sets `active` in `config/licences.php`, so `LicenceCatalogue::providers()` follows. It restricts every licence usage: requests, declared licences, enrollment, race registration pickers. Existing Licences of other types keep their name.
- `LICENCE_RENEWAL_PRICE`: a price (`50`) or a range (`50-80`), in euro, optional. Shown to the requester before submitting a renewal; a fixed price pre-fills the amount at verification, which the organizer can still change. Not applied to requests covered by an Enrollment.

- Implementation notes:
  - `config('licences.enabled_types')` holds the parsed `LICENCE_TYPES`; `LicenceCatalogue::types()` only returns enabled types, `find()` still returns every listed type (inactive when not enabled) so names stay. An entry not in the catalogue, or a renewal price that cannot be read, throws `InvalidArgumentException` naming the variable.
  - `Licence::scopeOfEnabledType()` narrows `enrollableIn`, `renewableFor`, `competitorIn`, `SetEnrollmentCompetitor` and the race registration pickers; it adds nothing when every type is enabled. Licence Requests are narrowed by `forDriverLicence()`, which loops over every provider, so retired driver types still count and a provider without driver types matches nothing. A draft whose type is not enabled has an incomplete Licence section, so it cannot be submitted.
  - Renewing a Licence whose provider is not offered keeps only the holder and parents: the requester picks provider, type and number again (the only offered provider is pre-selected). Championships can name the offered providers plus the one already chosen.
  - The renewal price shows on the "Review and submit" step and in the organizer's payment line; a fixed price pre-fills the amount unless the request already has one or is covered by an Enrollment.

## Acceptance criteria

- With `LICENCE_TYPES=asi.ma`, only ASI MA can be requested, declared or enrolled, and ACI is not offered.
- With `LICENCE_RENEWAL_PRICE=50`, the renewal form shows €50 and verification is pre-filled with 50.
- With neither variable set, everything works as today.
