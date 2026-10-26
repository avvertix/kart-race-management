# C1. Entitlements setting

## Goal

A championship chooses Entitlements instead of Bonuses; each race can opt out.

## Docs

- [Championship settings: Race fees and discounts](../../docs/reference/championship-settings.md#race-fees-and-discounts)
- [ADR 0003](../../docs/adr/0003-entitlements-separate-from-bonuses.md)

## Scope

- `championships.entitlements_enabled` (or a field in a settings Data object), default off; enabling requires `bonus_enabled = false`.
- `races.entitlements_enabled` nullable override, like `races.bonus_enabled`, with `Race::isEntitlementEnabled()`.
- Organizer UI on championship and race settings. The race page tells drivers when promo codes don't apply.

## Acceptance criteria

- Enabling Entitlements with bonuses on is refused.
- Existing championships and races are unaffected.
