# Issues: licences, enrollment, promo codes

One file per slice, one PR per slice, tests green before the next one. Behaviour is specified in `docs/` and `GLOSSARY.md`; decisions and data model in `.scratch/design/implementation-plan.md` ("Decisions from review").

Rules for every slice (see [backward compatibility](../../docs/explanation/backward-compatibility.md)):

- New behaviour is opt-in per championship ([ADR 0002](../../docs/adr/0002-opt-in-per-championship-settings.md)); existing championships keep working unchanged.
- Race registration keeps its shape: new data only pre-fills it.
- Forward-only, additive migrations; nullable columns on existing tables.
- Personal data stored encrypted; licence numbers also as `sha512` hash.

| Slice | Title | Depends on | Target |
|---|---|---|---|
| [A1](A1-foundations.md) | Foundations | – | December |
| [A2](A2-licence-types-and-grades.md) | Licence Types, Grades, required documents | A1 | December |
| [A3](A3-products-prices-orders.md) | Products, Prices, Orders, payments | A1 | December |
| [A4](A4-licences.md) | Licences: declare, list, verify | A2 | December |
| [A5](A5-licence-request-flow.md) | Licence Request flow (driver) | A3, A4 | December |
| [A6](A6-licence-request-processing.md) | Licence Request processing (organizer) | A5 | December |
| [A7](A7-notifications.md) | Notifications | A5, A6 | December |
| [A8](A8-document-retention.md) | Document retention | A6 | 2027 |
| [B1](B1-championship-settings.md) | Championship licence provider and enrollment settings | A1 | 2027 season |
| [B2](B2-bib-reservation-statuses.md) | BIB Reservation statuses and 1–999 cap | – | 2027 season |
| [B3](B3-enrollments.md) | Enrollments | A4, A5, B1, B2 | 2027 season |
| [B4](B4-race-number-picker.md) | Race number picker | B2, B3 | 2027 season |
| [B5](B5-previous-numbers.md) | Previous numbers and ACI copy | B2, B3 | 2027 season |
| [B6](B6-wildcard-based-on-enrollment.md) | Wildcard based on enrollment | B3 | 2027 season |
| [B7](B7-race-registration-licence-pickers.md) | Licence pickers in race registration | A4 | 2027 season |
| [C1](C1-entitlements-setting.md) | Entitlements setting | – | 2027 season |
| [C2](C2-promo-codes.md) | Promo codes | C1 | 2027 season |
| [C3](C3-entitlements.md) | Entitlements: claim, apply, release | C2, A4, B7 | 2027 season |
| [C4](C4-promotional-package-grants.md) | Promotional Package grants | A5, B3, B4, C3 | 2027 season |

Until C4 ships, packages are sold as plain Products and their extras are applied by hand.
