---
status: accepted
---

# Each Licence carries its holder's identity; no person entity

Licence requests, championship enrollments, promo codes and BIB reservations need to point at a person. We decided not to introduce a person entity (e.g. "Racer"). Each **Licence** stores its holder's personal details and is managed by one **User**, who need not be the holder (a parent, a team manager). Licences of different years are not linked, and each championship is isolated: renewals are filled in again, and anything carried over from a previous season (e.g. discounts for last year's results) is issued as a Promo Code instead. Race registration keeps matching people by the hash of the licence number, so existing participants, bonuses and reservations keep working unchanged.

## Considered Options

- **Person entity holding many yearly Licences**: rejected. Licence numbers can change every year and data is re-checked at every renewal, so linking people across years would add matching and merging work without a feature that needs it.
- **Personal details on the User profile**: rejected. One User manages licences for several people, so the account is not the holder.
