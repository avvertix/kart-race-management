# F4. Balance reminders and overdue Enrollments

## Goal

Drivers are reminded of the balance before it is due, and the organizer sees who is late. Nothing happens automatically when the balance is late.

## Scope

- A daily scheduled command notifies the User of each active Enrollment with a balance due 7 days before `balance_due_on`, once.
- The organizer's enrollment list marks Enrollments past their due date with a balance, and can filter them.
- The organizer dashboard counts overdue Enrollments.

## Acceptance criteria

- One reminder per Enrollment, none once paid in full.
- An Enrollment unpaid after its due date shows as overdue and stays active.
- These notifications can be disabled from the profile
