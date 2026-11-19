---
status: accepted
---

# New behaviour is opt-in per championship setting

Enrollment, licence providers, Entitlements and BIB self-reservation are built while championships are running on the live platform. Each new behaviour is turned on by its own championship setting, chosen when a championship is created. Every setting defaults to today's behaviour, and existing championships never get it set, so they keep working exactly as before.

## Considered Options

- **A single version marker on Championship** (e.g. `model_version = 2`) switching everything on together: rejected. It forces all features to ship and be adopted at once, and kart and moto championships need different combinations.
