# F4. Balance reminders and overdue Enrollments

## Goal

Drivers are reminded of the balance before it is due, and the organizer sees who is late. Nothing happens automatically when the balance is late.

## Scope

- A daily scheduled command notifies the User of each active Enrollment with a balance due 7 days before `balance_due_on`, once.
- The organizer's enrollment list marks Enrollments past their due date with a balance, and can filter them.
- The organizer dashboard counts overdue Enrollments.

- Implementation notes:
  - `enrollments:remind-balance` runs every day at 09:00 in the races' timezone; due dates are calendar days there. It reminds active Enrollments not paid in full whose balance is due between today and 7 days from now, and marks `enrollments.balance_reminded_at` (added to the unreleased enrollments migration: `migrate:fresh`). An Enrollment enrolled in the last week before the due date is reminded on the next run; none is reminded after the due date.
  - The reminder is a new notification type, `EnrollmentBalanceDue`: like every account notification, its email can be turned off in the notification settings, while the copy in the app is kept.
  - While a payment proof waits for review the reminder is held back, as the proof may pay the balance; it is sent on a later run if a balance is still due. Changing the option resets the reminder, so a new due date gets its own.
  - The overdue filter lists active Enrollments of any status. The dashboard's championship cards show the overdue count to users who review enrollments and have early access.

## Acceptance criteria

- One reminder per Enrollment, none once paid in full.
- An Enrollment unpaid after its due date shows as overdue and stays active.
- These notifications can be disabled from the profile
