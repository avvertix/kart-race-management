# Rolling out without breaking running championships

The new features are released while championships are running on the live platform. Three rules keep those championships safe:

1. **Opt-in per championship.** Every new behaviour (enrollment, licence provider, Entitlements, race number self-reservation) is turned on by a championship setting chosen when the championship is created. Every setting defaults to today's behaviour, and existing championships are never switched on. See [ADR 0002](../adr/0002-opt-in-per-championship-settings.md).
2. **Race registration keeps its shape.** The registration form, the licence categories it asks for (national, international, foreign…), validation, exports, timing files and receipts don't change. New data, such as a picked Licence, only pre-fills the form.
3. **Old records stay valid.** Existing race number reservations count as confirmed. Bonuses keep applying where they are used. Existing reservations above 999 stay as they are.

## What changes only for new championships

| Area | Running championships | New championships that opt in |
|---|---|---|
| Licence provider | The platform-wide provider | ACI or ASI, chosen per championship |
| Enrollment | Not available | Optional, free or paid |
| Points eligibility | Existing wildcard rules | Can also be "based on enrollment" |
| Race numbers 1–999 | Reserved by the organizer | Also picked by drivers when enrolling |
| Race fee discounts | Bonuses | Entitlements from promo codes and packages |

## Temporary workarounds to remove

The "shared race number" setting was a one-off fix for a wrongly configured reservation this season. It stays until the season ends and is then removed. It plays no part in the new race number picker.
