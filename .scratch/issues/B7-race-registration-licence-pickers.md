# B7. Licence pickers in race registration

## Goal

Signed-in Users fill race registrations from their managed Licences instead of typing everything.

## Docs

- [Register for a race](../../docs/how-to/driver/register-for-a-race.md)
- Prototype: `.scratch/wireframes/index.html`, "Register for a race", variant A (logged in)

## Scope

- Three pickers on the registration form, each listing managed Licences of that role valid in the race's year: driver, competitor (pre-filled with the one used in the User's previous registration of that driver), mechanic.
- Picking fills the matching form fields; the fields stay editable and the form, validation and exports don't change. The pickers are a small form above the registration: choosing *Fill in the form* reloads it pre-filled, through the existing template pre-fill, so no field logic is duplicated in JavaScript.
- Shown only to signed-in Users with early access to licences and products.
- `participants.licence_id` nullable, informative only; the licence number hash remains the matching key.
- `LinkedDrivers` unchanged.

## Acceptance criteria

- A guest sees the form exactly as today.
- Licences of other years are not listed.
- A registration made from a picked Licence produces the same participant data as one typed by hand.
