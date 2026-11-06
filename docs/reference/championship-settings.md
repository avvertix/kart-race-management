# Championship settings

Settings of a championship, chosen when creating it or later from its page. Settings marked **new** are off by default and stay off for championships created before they existed. While licences and enrollments are in early access, only users with early access see the new settings and the public enrollment page.

## Licences

| Setting | Values | Default |
|---|---|---|
| Licence provider (**new**) | ACI, ASI | The platform-wide provider |
| Accepted driver licences | Any of: national, international, foreign, easy race, daily | All |
| Accepted competitor licences | Any of: local, foreign | All |

The licence provider only decides the wording of local licences on the registration form and rules; without a provider, the championship uses the platform-wide one, as before. It doesn't restrict which licences can be enrolled: licences from every provider are accepted, e.g. ACI and ASI in the same championship. The verified drivers export is offered on national and international races whatever the provider.

## Enrollment (**new**)

| Setting | Meaning | Default |
|---|---|---|
| Enabled | Drivers can enroll in the championship; needs at least one enrollment option offered | Off |
| Opens / closes | Period in which enrollment is accepted, in the races' timezone. Both are required to turn enrollment on | — |
| Competitor required when underage | An underage Driver must name a Competitor to enroll | On |

### Enrollment options

Each championship lists the options drivers enroll with, in the order set by the organizer. An inactive option is no longer offered.

| Field | Meaning |
|---|---|
| Title, description | Shown to drivers when choosing |
| Amount | Fixed, in euro; 0 is free. The same whatever the licence |
| Covers the licence renewal | The amount also pays the Licence Request the Enrollment waits for |
| Reserves a race number | The Driver picks a Championship BIB while enrolling |
| Race fee discount | Race fees or a balance granted with the Enrollment; only when the championship uses Entitlements instead of bonuses |
| Licence categories | Offered only to licences of these categories; none means any |

## Wildcards

| Setting | Values | Default |
|---|---|---|
| Enabled | On, off. When off, nobody is marked as a wildcard | Off |
| Rule | Based on race number reservation, based on bonus, based on enrollment (**new**) | — |
| Required bonus amount | Only for "based on bonus" | 1 |

## Race fees and discounts

| Setting | Meaning | Default |
|---|---|---|
| Registration price | Race fee, unless the category sets its own | Platform-wide price |
| Bonuses enabled | Legacy Bonuses apply to race fees | On |
| Entitlements enabled (**new**) | Entitlements from promo codes and enrollment options apply to race fees. Requires bonuses to be off | Off |
| Bonus mode, fixed bonus amount | Legacy Bonus settings | Credit |

Each race can override whether bonuses or Entitlements apply to it. By default a race follows the championship.

## Race numbers and registration form

| Setting | Meaning | Default |
|---|---|---|
| Allow different race numbers | A Driver can use different numbers in different races | Off |
| Registration form | Complete (with vehicle and mechanic), standard or minimal. Moto championships use standard or minimal | Platform-wide form |
