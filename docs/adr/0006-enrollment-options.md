---
status: accepted
---

# Enrollment options replace Products, Packages and Orders for enrollment

ADR 0005 kept enrollment on top of Products, Promotional Packages and Orders. The 2027 championships showed that every case is one Enrollment with a fixed amount and a few extras. Kartsport has a free option, plus two promos by licence category covering the renewal, a race number and the first race. Fourstroke is free. BKQ has a fee covering the renewal. Keeping a general commerce layer for that meant rules about where each Product could be sold, packages enrolling the requests they covered, and Orders moving between requests. We decided:

- A championship offers **Enrollment Options**. Each one is free or has a fixed amount, and can cover the licence renewal, reserve a race number, grant a race fee discount, and be limited to licence categories.
- The Enrollment records the option chosen and carries its own payment, the way a Licence Request does (ADR 0004). An option covering the renewal pays the request the Enrollment waits for.
- Nothing is sold outside enrollment. Promo codes keep handing out race fee discounts on their own.

Products, Prices, Packages and Orders are removed; the migrations of this unreleased branch are rewritten rather than reverted.

## Considered Options

- Keep Products and hide them behind enrollment (ADR 0005). Rejected: most of the complexity remained for cases nobody uses.
- One option with a price range set by the organizer for each Enrollment, for Kartsport's two promos. Rejected: every promo Enrollment would wait for the organizer before paying; options limited to licence categories have known prices.

## Consequences

Price periods (early-bird), organizer-set prices, one payment covering several championships, and selling anything outside enrollment are no longer possible. Dates on options can be added when a championship needs them.

Update: the moto championship sells two options with a deposit and a balance due by a date. An option can ask for a deposit and set the balance due date. The Enrollment's payment is the sum of its confirmed payment records (bank transfer proofs uploaded by the driver, or cash, card and transfers recorded by the organizer, each with the amount the organizer confirmed). Paying the deposit unlocks everything the option includes; the balance is reminded and shown as overdue, never enforced automatically. We considered modelling the deposit as a second payment on the Enrollment, and rejected it: a list of records also covers partial payments, mixed channels and a change of option after paying.
