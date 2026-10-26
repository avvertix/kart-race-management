# Discounts: entitlements, packages and bonuses

## Entitlements

An **Entitlement** is a right to pay less for race fees in one championship. It belongs to one Licence and is held either as:

- **credits**: a number of race fees covered, or
- **balance**: an amount of money to spend on race fees.

It reduces the race fee only, never the tyres. At most one Entitlement is used per race registration: the oldest one, credits before balance. A credit covers the whole race fee of the chosen category, whatever it costs; a balance covers the fee up to what is left. A Licence can collect several Entitlements, mixing credits and balance. Deleting the registration, cancelling the race or changing the fee gives the used amount back.

Whether Entitlements can be used in a race depends on its configuration. By default they can be used in every race of a championship that has them turned on, and each race can override that, as with bonuses today. Where they are allowed, they are always applied: the driver doesn't choose race by race.

Entitlements come from two places:

- a **Promo Code** the organizer issued and someone claimed, e.g. a prize for last season's results or a plain discount;
- a **Promotional Package** bought by the driver, e.g. a licence renewal that includes some race fees.

## Why claiming matters

In past seasons discounts were matched by licence number or fiscal code. That failed when the licence number changed, when it was mistyped, for foreign drivers without a fiscal code, and for drivers who renewed their licence elsewhere. A Promo Code removes the guesswork: it is claimed once, for one Licence, and nobody can claim it again.

- **With an account**, the User claims the code for one of the Licences they manage that is valid in the championship's year. From then on it is applied automatically when that Licence registers for a race. A code for next season stays unclaimed until the holder's licence for that year is emitted or declared.
- **Without an account**, the person types the code into the race registration form. It is claimed for the licence number entered, when the registration is saved; an invalid or already claimed code is refused with an error. When a Licence with that number is later emitted, or declared and verified, on the platform, the Entitlement moves to that Licence.

Each code has a link and a QR code that show the code and how many times it has been used.

## Discounts on licences and enrollments

Licence requests and enrollments are discounted through their **Prices** and through **Promotional Packages**, not through Promo Codes. Promo Codes are only about race fees.

## Bonuses

A **Bonus** is the legacy way to discount race fees: the organizer assigns it to a driver's licence number or fiscal code. Bonuses keep working for the championships that use them, until they are deleted. A Bonus is not an Entitlement, and a championship uses one or the other, never both. See [ADR 0003](../adr/0003-entitlements-separate-from-bonuses.md).
