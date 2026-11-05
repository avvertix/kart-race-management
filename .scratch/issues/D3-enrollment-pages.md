# D3. Enrollment form and Enrollment page

## Goal

Drivers enroll through a stepped form on the championship enrollment page. Each Enrollment then has its own page with everything about it, payment included, so drivers never open an Order.

## Docs

- [ADR 0005](../../docs/adr/0005-enrollment-fee-and-packages.md)
- [Enroll in a championship](../../docs/how-to/driver/enroll-in-a-championship.md) (update)

## Scope

- A Livewire stepped form on `public.championships.enrollment`:
  1. **Licence:** a managed driver Licence valid in the championship's year, or a matching open Licence Request. Licences already enrolled are not offered. The "renew" option comes with D4.
  2. **Competitor:** only when the Driver is underage (D5 sets the rules).
  3. **What to pay:** free, the fee, or a Package including the Enrollment. This step only shows when there is a choice or something to pay.
  4. **Race number:** only when the settings or the chosen Package reserve one, and only after a Licence is chosen. If the Licence already holds a reservation, that is shown instead of the picker.
  5. **Review and submit.** The button reads "Submit" or "Submit and pay" depending on the choice.
- The championship page lists the account's Enrollments briefly, each linking to `my-enrollments/{enrollment}`.
- `my-enrollments/{enrollment}` shows the Enrollment's details and actions:
  - holder, Licence or request, Competitor and status;
  - race number with its status, and change number;
  - the payment: amount, status, proof upload and rejected proofs, from the Order and `Payment`;
  - withdraw.
- After submitting, the driver lands on this page. The redirect to `my-orders.show` is removed.
- Policy: the Enrollment's User only.

## Out of scope

Rules on competitors and withdrawal (D5). Organizer pages (D6).

## Acceptance criteria

- A driver uploads the payment proof for an Enrollment without visiting an order page.
- An enrolled Licence is not offered again.
- The number step doesn't show before a Licence is chosen.
- The submit button reads "Submit" when the chosen option is free.
