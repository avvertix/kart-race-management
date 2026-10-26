# Licences, users and identity

## A User is not a driver

A **User** is an account. The people who race are identified by their **Licence**, not by an account. One User can manage several Licences:

- a parent manages their own competitor licence and their child's driver licence;
- a team manager manages the licences of several drivers;
- an adult driver manages just their own.

Each Licence stores its holder's personal details (name, birth date and place, fiscal code, nationality, residence, email and phone), so the account itself holds no personal data about drivers.

## What a Licence is

A Licence is issued by a **Licence Provider** (ACI or ASI) and has a number, a **Licence Type** (e.g. ACI driver, competitor or mechanic), a **Licence Grade** (e.g. C Junior; for ACI within the national or international level) and a validity year. Licences reach the platform in two ways:

- **Emitted**: the organizer processed a **Licence Request** made on the platform. The temporary licence PDF returned by the provider is attached to it.
- **Declared**: the User entered an ACI or ASI licence they obtained elsewhere, e.g. renewed directly with the provider. The organizer can mark it verified, and must before confirming an enrollment with it. The same licence number can be declared only once per year.

Foreign licences are not added to the platform: foreign drivers type them into the race registration form, as today.

A Licence is usually valid for the calendar year. Requests arriving in December are usually processed in January so the licence covers the full next year. The organizer sets the validity year when emitting, pre-filled by that rule.

## Each year stands alone

Licences of different years are not linked, and each championship is isolated. A renewal is a new request filled in again; nothing is carried over automatically. When something should carry over, such as a discount for last season's results, the organizer issues a **Promo Code** for it. See [ADR 0001](../adr/0001-licence-as-identity.md).

## How races recognise a driver

Race registration matches drivers by their licence number, as it always has. A registration made by an anonymous visitor, by staff, or by a User picking one of their Licences is recognised the same way. That is how enrollments, reserved race numbers and promo codes reach the race, whoever fills in the form.

The existing "linked drivers" feature, which links past race registrations to an account, stays as it is for this season. Managed Licences are a new, separate way to pre-fill the registration form.
