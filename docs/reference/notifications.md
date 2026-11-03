# Notifications

Only the notifications added for licence requests and their orders are listed here. Race registration notifications don't change.

Each notification is kept in the app and also sent by email to the User's account email, which is verified at sign-up. Users turn the email of each notification off from **Settings → Notifications**; the copy in the app is always kept, so nothing that needs their attention is missed. Every email ends with a link to those settings. Emails are in the language the User last used on the platform, queued and sent only once the change is saved. They never contain documents or the fiscal code.

In the app, the bell next to the account menu shows how many notifications are unread and opens **Notifications**, newest first. Opening one marks it as read and goes to the request, licence or order it is about; **Mark all as read** clears the count. The button in the email opens the same notification, so following it also marks it as read. Opening that request, licence or order in any other way, e.g. from **Licence requests**, keeps its notifications unread, so a request sent again while the organizer is already looking at it is not missed. A notification about something since deleted stays in the list as *No longer available*. Read notifications are deleted 12 months after being read (`notifications:prune-read`, every night); unread ones are kept. An old email whose notification was deleted opens **Notifications**.

| When | Who is notified |
|---|---|
| A Licence Request is submitted | The applicant (request received) and the organizers |
| An applicant submits again after a change request | The organizers |
| The organizer asks for new documents or changes | The applicant, with a link to the comments |
| The organizer verifies the request | The applicant, with the amount and how to pay, or that a package covers it |
| The organizer rejects the request | The applicant, with the reason |
| The organizer sets the final price of a range-priced order | The applicant, with the amount, payment details and a link to upload the proof |
| The Licence is emitted | The applicant, with a link to the Licence |

The organizers are the users with the Organizer role and early access to licences and products. Admins receive the organizers' notifications, by email and in the app, only after turning their email on in their notification settings. The organizer email in the configuration (`RACE_ORGANIZER_EMAIL`) receives no notifications: give at least one organizer early access, or submitted requests reach nobody until someone opens **Licence requests**.

Payment confirmations, enrollments, promo codes and orders other than range-priced ones send no notifications for now.
