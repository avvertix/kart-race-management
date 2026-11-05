# D4. Renew while enrolling, and rejected requests

## Goal

A driver without a valid Licence chooses "renew" in the enrollment form. The Enrollment then exists from that moment, waiting for the request. A rejected request never leaves an Enrollment stuck or a paid renewal lost.

## Docs

- [GLOSSARY: Licence Request, Enrollment](../../GLOSSARY.md)
- [Lifecycles: Enrollment, Licence Request](../../docs/reference/lifecycles.md) (update)

## Scope

- In the licence step, "Renew for {year}" creates, in one transaction:
  - the draft Licence Request for the championship's year (D1);
  - the Enrollment waiting for it;
  - the Order, when the fee or a Package is due.
- The renewal pre-fills from the previous year's managed Licence when there is one.
- Draft requests count as requests an Enrollment can wait for.
- Afterwards the driver completes the request form, which links back to the Enrollment page. The Enrollment page shows "Licence request to complete".
- Packages including an Enrollment and a renewal are offered here. Those without a renewal are offered for an existing Licence.
- `EnrollPackagedRequest` keeps creating Enrollments for packages bought on the products page, when the request is started from the paid Order. A failure is shown to the user when starting the request, instead of only being logged.
- When a request is rejected:
  - waiting Enrollments stay `Submitted`, flagged as needing a Licence;
  - the driver can pick another Licence or start a new request for them, which keeps the Order and the number.
- A covering Order (Package or fee) is freed for a new request once the request it covered is rejected. `Order::canStartLicenceRenewal` ignores rejected requests.
- My licences shows a paid Package with an unused renewal as "Renewal covered by {package}: start it".

## Out of scope

Notifications (D7).

## Acceptance criteria

- Choosing renew shows the Enrollment on the page before the request is submitted.
- After a rejection, the driver switches the Enrollment to another Licence without paying again.
- A rejected covered request lets the driver start a new covered one.
