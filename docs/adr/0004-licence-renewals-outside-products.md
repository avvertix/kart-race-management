---
status: accepted
---

# Licence renewals are not Products, and Licence Types are fixed in code

The first design sold licences as Products and let organizers configure Licence Types, grades and required documents. With more than 30 ACI licences, that left a lot of configuration to maintain and made renewals hard to follow. We decided that a Licence Request is not bought through an Order. It carries its own payment: the organizer sets the amount during review, and the requester uploads a proof that the organizer confirms. The exception is a Promotional Package that includes a renewal, whose paid Order covers it. Products are left for championship enrollments, race fees and special offers.

Licence Types are a fixed list per Licence Provider, maintained in the code by developers. Each driver type records the race licence category it counts as in race registration. Required documents are a fixed set chosen by the holder's age, and the organizer checks that they fit the chosen Licence Type. Organizers cannot add Licence Types; a missing one is a code change.

## Considered Options

- Keep Licence Types as organizer-managed records with a simpler shape. Rejected: the list rarely changes and is the same for every organizer of a provider.
- One Product per Licence Type. Rejected: 30+ Products whose prices the organizer settles per request anyway.
