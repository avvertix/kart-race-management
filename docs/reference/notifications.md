# Notifications

Only the notifications added for licence requests and enrollments are listed here. Race registration notifications don't change.

Each notification is kept in the app and also sent by email to the User's account email, which is verified at sign-up. Users turn the email of each notification off from **Settings → Notifications**; the copy in the app is always kept, so nothing that needs their attention is missed. Every email ends with a link to those settings. Emails are in the language the User last used on the platform, queued and sent only once the change is saved. They never contain documents or the fiscal code.

In the app, the bell next to the account menu shows how many notifications are unread and opens **Notifications**, newest first. Opening one marks it as read and goes to the request, licence or enrollment it is about; **Mark all as read** clears the count. The button in the email opens the same notification, so following it also marks it as read. Opening that request, licence or enrollment in any other way, e.g. from **Licence requests**, keeps its notifications unread, so a request sent again while the organizer is already looking at it is not missed. A notification about something since deleted stays in the list as *No longer available*. Read notifications are deleted 12 months after being read (`notifications:prune-read`, every night); unread ones are kept. An old email whose notification was deleted opens **Notifications**.

| When | Who is notified |
|---|---|
| A Licence Request is submitted | The applicant (request received) and the organizers |
| An applicant submits again after a change request | The organizers |
| The organizer asks for new documents or changes | The applicant, with a link to the comments |
| The organizer verifies the request | The applicant, with the amount and how to pay, or that the enrollment covers it |
| The organizer rejects the request | The applicant, with the reason |
| The Licence is emitted | The applicant, with a link to the Licence |
| The organizer confirms the payment of an enrollment | The User who enrolled, with a link to the Enrollment |
| The organizer can't accept the payment proof of an enrollment | The User who enrolled, with a link to upload a new one |
| The organizer confirms an enrollment | The User who enrolled, with a link to the Enrollment |
| The organizer rejects an enrollment | The User who enrolled, with the reason |
| An enrollment needs something before it can be confirmed: another licence after its request was rejected, or the Competitor of an underage driver once the licence request shows the age | The User who enrolled, with a link to the Enrollment |

The organizers are the users with the Organizer role and early access to licences and enrollments. Admins receive the organizers' notifications, by email and in the app, only after turning their email on in their notification settings. The organizer email in the configuration (`RACE_ORGANIZER_EMAIL`) receives no notifications: give at least one organizer early access, or submitted requests reach nobody until someone opens **Licence requests**.

Promo codes send no notifications for now.
