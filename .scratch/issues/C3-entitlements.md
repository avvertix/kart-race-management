# C3. Entitlements: claim, apply and release

## Goal

Claimed codes reduce race fees automatically, for signed-in Users and guests alike.

## Docs

- [Discounts](../../docs/explanation/discounts.md)
- [Use a promo code](../../docs/how-to/driver/use-a-promo-code.md)

## Scope

- `entitlements`: `championship_id`, `licence_id` nullable, `licence_hash`, `mode`, `amount`, `remaining`, `source_type`/`source_id` (promo code or order line). `participant_entitlement` pivot with `amount`, like `participant_bonus`.
- Claim from the account: on the code's page or by typing it; pick a managed Licence valid in the championship year (start date year).
- Claim in race registration: a promo code field for everyone; signed in, it claims for the picked Licence; otherwise for the typed licence number (hash). Claimed in the same transaction as the registration; invalid, revoked, already claimed or wrong-championship codes and races without Entitlements give a validation error.
- Pipeline stage `ApplyEntitlementToParticipant` next to `ApplyBonusToParticipant`, skipped unless `Race::isEntitlementEnabled()`: at most one Entitlement per registration, oldest first, credits before balance; a credit covers the full race fee of the chosen category, a balance up to what's left; never tyres. A matching remove stage releases usage on deletion, race cancellation or fee change. `Participant::calculateParticipationCost()` includes it; `RegistrationCostData` unchanged.
- When a Licence with a matching `number_hash` is emitted or verified, Entitlements claimed by hash move to it.
- Code page shows usage from the pivot.
- Typing a code in the account: a "Have a promo code?" field on My licences leads to the code's page, where it is claimed. Claims in the account need a driver Licence valid in the championship year, of its provider when set.
- Entitlements match participants by `licence_hash` (the same key as `participants.driver_licence`); the pivot `amount` is the discount in cents. Releasing gives back one credit, or the discounted amount for a balance. Race cancellation releases the usage of all its participants.

## Acceptance criteria

- A code can be claimed only once, also under concurrent requests.
- A guest's code works on the next registration with the same licence number, without typing it again.
- Deleting a participant restores the credit or balance.
- Receipts and exports show the discounted fee as bonuses do today.
