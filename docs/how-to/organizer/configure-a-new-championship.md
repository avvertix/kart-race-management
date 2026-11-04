# Configure a new championship

Use this guide when creating a championship that uses enrollment, a specific licence provider or promo codes. Championships that don't need them can skip those steps and work as before.

## Before you start

- Decide whether drivers enroll for a race number, for points, or both.
- If enrollment is paid, create its Product and Price first ([Set up products and prices](set-up-products-and-prices.md)).

## Steps

1. **Create the championship** with its title and dates.
2. **Choose the licence provider** (ACI or ASI) when creating it, or later from **Accepted licences**, and, if needed, restrict the accepted driver and competitor licences. Without a choice the championship uses the platform-wide provider, as before.
3. **Choose the registration form.** Use *complete* for kart, which asks for vehicle and mechanic. Use *standard* or *minimal* for moto.
4. **Set up enrollment** from **Enrollment** on the championship page, if drivers should enroll:
   - turn enrollment on and set when it opens and closes, in the races' timezone;
   - pick the enrollment Product, or leave it empty for free enrollment;
   - turn on *grants race number reservation* if enrolled drivers pick their number;
   - keep *competitor required when underage* on unless the rules say otherwise.
5. **Set up wildcards**, if only some participants score points: turn wildcards on and pick *based on enrollment*.
6. **Choose how race fees are discounted:**
   - for promo codes and packages, turn Entitlements on, which turns bonuses off;
   - otherwise leave bonuses as they are.
7. **Add categories, tyres and races** as usual.

## Check

- Open the public championship page, linked from the enrollment settings. It shows enrollment as open in the configured period, and the price. While in early access, only users with early access can open it.
- Start an enrollment with a test account and check that the race number picker appears, if you turned it on.

See [Championship settings](../../reference/championship-settings.md) for every setting and its default.
