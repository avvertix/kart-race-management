# Race Management documentation

> **Status: design.** These pages describe how licences, championship enrollment, race numbers and discounts are meant to work once built. They are the agreed target, not a description of the current release. Terms in **bold** are defined in [GLOSSARY.md](../GLOSSARY.md).

The documentation follows the [Diátaxis](https://diataxis.fr) framework:

- **How-to guides** walk through a task, for organizers and for drivers.
- **Reference** lists the settings, statuses and permissions to look things up.
- **Explanation** says how the parts fit together and why.
- **Decisions** (`adr/`) record the choices behind the design.

Tutorials will be added once the features can be tried end to end.

## How-to guides

For organizers:

- [Configure a new championship](how-to/organizer/configure-a-new-championship.md)
- [Set up licence types, products and prices](how-to/organizer/set-up-licence-types-and-products.md)
- [Process a licence request](how-to/organizer/process-a-licence-request.md)
- [Confirm enrollments and race numbers](how-to/organizer/confirm-enrollments-and-race-numbers.md)
- [Issue promo codes](how-to/organizer/issue-promo-codes.md)

For drivers, parents and team managers:

- [Request or renew a licence](how-to/driver/request-or-renew-a-licence.md)
- [Enroll in a championship and reserve a race number](how-to/driver/enroll-in-a-championship.md)
- [Use a promo code](how-to/driver/use-a-promo-code.md)
- [Register for a race](how-to/driver/register-for-a-race.md)

## Reference

- [Championship settings](reference/championship-settings.md)
- [Statuses and lifecycles](reference/lifecycles.md)
- [Race number ranges](reference/race-numbers.md)
- [Roles and permissions](reference/roles-and-permissions.md)
- [Notifications](reference/notifications.md)

## Explanation

- [Licences, users and identity](explanation/licences-and-identity.md)
- [Enrollment, points and wildcards](explanation/enrollment-and-wildcards.md)
- [Discounts: entitlements, packages and bonuses](explanation/discounts.md)
- [Rolling out without breaking running championships](explanation/backward-compatibility.md)

## Decisions

- [0001 Each Licence carries its holder's identity](adr/0001-licence-as-identity.md)
- [0002 New behaviour is opt-in per championship setting](adr/0002-opt-in-per-championship-settings.md)
- [0003 Entitlements are separate from Bonuses](adr/0003-entitlements-separate-from-bonuses.md)

## Open questions

- The exact steps of the organizer's current licence process. The licence request uses generic sections until they are known.

## Planned later

- Typed driver, competitor, mechanic and vehicle data.
- Companies as Competitors.
- Saving half-filled race registrations (Draft Participants).
- Promo codes in the phone wallet.
- Online payment.
- Refunds through the platform. For now, refunds are settled by email with the organizer.
- Exports for ASI.
