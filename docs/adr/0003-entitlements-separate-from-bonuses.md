---
status: accepted
---

# Entitlements are separate from Bonuses

Bonuses and Entitlements both reduce race fees, and the `bonuses` table already has amounts, a licence hash and a usage pivot. We still decided not to evolve Bonuses into Entitlements. A Bonus is an organizer-assigned reduction matched by licence number or fiscal code. An Entitlement is a right that a Licence holds, granted by a claimed Promo Code or a Promotional Package. Bonuses stay as they are for existing championships until they are deleted. A championship uses one or the other: turning Entitlements on requires bonuses to be disabled.
