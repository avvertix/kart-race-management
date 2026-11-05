# Race Management

Organizing motorsport championships (kart and moto): licences, championship enrollment, race participation, race numbers and fees.

## Language

### People and accounts

**User**:
An account on the platform. One User can act on behalf of several people, e.g. a parent for a child or a team manager for their drivers.
_Avoid_: Profile, customer

**Driver**:
The natural person who drives the vehicle in a race.
_Avoid_: Racer, pilot, conduttore

**Competitor**:
The person or company that is responsible for a Driver's entry in a race; either an individual or a company. A Driver without a separate Competitor acts as their own Competitor.
_Avoid_: Team, concorrente, entrant

**Mechanic**:
A licensed assistant who supports a Driver at a race.
_Avoid_: Assistant, meccanico

### Licences

**Licence**:
A sporting licence issued to one person or company by a Licence Provider, with a number, a type, a validity year and the holder's personal details. It is managed by one User, who need not be the holder. Licences of different years are not linked to each other.
_Avoid_: Permit, card

**Emitted Licence**:
A Licence obtained through a Licence Request processed by the organizer.

**Declared Licence**:
A Licence the User entered themselves, obtained from a Licence Provider outside the platform; the organizer may mark it verified. Foreign licences are never declared: foreign Drivers only type them into the race registration form.

**Licence Provider**:
The sporting body that issues a Licence, e.g. ACI or ASI. A championship accepts licences from exactly one Licence Provider, plus foreign licences.
_Avoid_: Federation, issuer

**Licence Type**:
One specific licence in a Licence Provider's fixed list, e.g. ACI "NAZ E", "INT F", "Daily" or a mechanic licence, or a licence on the ASI list. Each is either a driver or a mechanic licence (the rulebooks call this its category). An ACI driver licence can include the competitor licence, under the same number; ACI has no competitor-only licence.
_Avoid_: Licence grade, qualification, category (for this meaning)

**Licence Request**:
An application submitted through the platform to have a Licence renewed by the organizer on behalf of a Licence Provider, for one validity year chosen when it is created: the next year can be requested from December, or earlier while a championship of that year is open for enrollment. First emissions are arranged with the organizer outside the platform. The organizer reviews it like a pull request: comments on what to fix, then either requests changes or marks it **Verified**, meaning every document was viewed and nothing needs fixing. Verified is the organizer's check, not the licence: the Licence Provider emits it.
_Avoid_: Licence application, renewal form; Approved, for a verified request

**Renewal**:
A Licence Request for a holder who had a Licence the previous year, identified by that Licence's number whether or not the Licence is on the platform. The Licence Type may change on renewal. ACI keeps the same number across years; ASI assigns a new one.

### Championships and races

**Participant**:
A Driver's registration to a single race.
_Avoid_: Entry, registration (when referring to the record)

**Draft Participant**:
A race registration form that was started but not yet submitted.

**Enrollment**:
A Driver's Licence enrolled in a championship, optionally with a Competitor (required for underage Drivers, who cannot be their own Competitor). A Driver has at most one active Enrollment per championship, while one User can enroll each Driver whose Licence they manage. It can wait for the Licence Request that will give the Licence; when that request is rejected, the User picks another Licence for it. It is free or costs a fixed Enrollment Fee, the same whatever the Licence; a Promotional Package including the Enrollment replaces the fee. It is optional for racing and does not fix the category or the Competitor, which a Participant can change between races. Each championship is isolated: enrollments do not carry over to other championships.
_Avoid_: Championship registration, subscription, promotional registration

**Enrollment Fee**:
The fixed amount a championship charges for an Enrollment. The championship may make it cover the Licence Request renewing the Driver's Licence for its year; a Driver who already holds a valid Licence pays the same.
_Avoid_: Subscription fee

**Wildcard**:
A Participant who races but does not score championship points, as decided by the championship's wildcard configuration (e.g. because they are not enrolled).

**BIB Reservation**:
A Championship BIB held for a Driver's Licence in a championship. It is pending until the organizer confirms it or releases it; first confirmed, first served. The organizer may confirm it before the Enrollment it belongs to, which releases it when withdrawn or rejected.
_Avoid_: Booking, hold

**Championship BIB**:
A race number in the 1–999 range that can be reserved for a Driver for a whole championship.
_Avoid_: Reserved number

**Race-only BIB**:
A race number of 1000 or above, used for Participants of special categories that take part in only one or two races and cannot be reserved.

### Commerce

**Product**:
Something the organizer sells, such as an Enrollment Fee, a race fee or a Promotional Package. An Enrollment Fee, or a Package including an Enrollment without a Licence renewal, is bought only while enrolling. Licence renewals are not Products: a Licence Request is not bought through an Order.

**Price**:
The amount charged for a Product in a given period, either fixed or a range whose final amount the organizer sets for each Order, e.g. when a package's cost depends on what the buyer picks.

**Order**:
A request by a User to buy one or more Products. It is paid by uploading a payment proof that the organizer confirms. When it pays for an Enrollment, the User sees it only as the Enrollment's payment.
_Avoid_: Purchase, transaction

**Entitlement**:
A right to a reduction of race fees, held as a number of credits or as a monetary balance, granted by a promo code or by a promotional package. It belongs to one Licence.
_Avoid_: Voucher

**Bonus**:
A legacy, organizer-assigned reduction of race fees for a Driver in a championship, matched by licence number or fiscal code. It is kept only for existing championships and is not an Entitlement.

**Promo Code**:
A code issued by the organizer that is claimed for one Licence, turning it into an Entitlement. It is claimed either by a User for a Licence they manage, or by typing it into a race registration without an account, against the licence number entered. Once claimed, it cannot be claimed for another Licence.
_Avoid_: Discount code, coupon

**Promotional Package**:
A Product that grants several things at once when its Order is confirmed, e.g. a Licence emission, an Entitlement and the right to reserve a Championship BIB.
_Avoid_: Bundle
