# Process a licence request

> The steps of the current organizer process are still being collected. This guide uses the generic sections of a request.

You are notified of each new request and each resubmission after a change request. The applicant is notified when the request is received, when you ask for changes and when the licence is emitted. See [Notifications](../../reference/notifications.md).

Every request is a renewal: it carries last year's licence number. First licences are arranged with you outside the platform.

Reviewing a request works like a pull request review: you mark the documents as viewed and comment on what is wrong, then finish the review with one outcome. The steps at the top of the request (Review, Payment, Licence) show where it is, and the main button is always the next thing to do.

1. **Open the request** from **Licence requests** in the menu, or from **Requests to process** on **All licences**. The list shows the requests waiting for you, oldest first; pick a status to see the others. A draft shows only what the driver filled in so far. The request shows the uploaded documents and signed forms, the Licence Type, whether the competitor licence is included, last year's licence number, the holder's details and the parents' details (underage holders only). Choose a document to show it beside the list. A warning appears when last year's number belongs to a licence managed by another user.
2. **Review each document.** The documents are the same for every Licence Type: check they fit the one requested, e.g. the right medical certificate. Tick **Viewed** on each document that is fine. A form the provider publishes has **Original from <provider>**, which opens the provider's file to compare. If something is wrong, e.g. an unreadable identity card, an expired medical certificate or a wrong birth place, choose **Comment…** on that document or on the Licence, Holder or Parents section. Comments stay with you until you finish the review, and you can remove them.
3. **Finish the review**, choosing one outcome. While comments are pending the button reads **Request changes (n comments)**; it opens the same dialog, where you confirm them:
   - **Request changes** sends your comments to the applicant, who fixes only those parts and submits again. When the request comes back, files uploaded since you asked for changes are marked **New file** and listed first, with your previous comment.
   - **Mark as verified** needs every document viewed and no comment left. Enter the amount to pay for the Licence Type requested; a request covered by an Enrollment needs none. The applicant is asked to pay. You can change the amount until a payment proof is uploaded.
4. **Check the payment proof** and choose **Confirm payment**, or **Reject proof** to ask for a new one. If the applicant pays by cash or credit card at your office, choose **Paid at the office** and pick how they paid. If they already uploaded a proof, confirm or reject it first.
5. **Emit the licence** once the payment is confirmed or covered. Download each document and send them to the Licence Provider. When it replies, choose **Emit licence**, upload the temporary licence PDF, check the licence number (pre-filled with last year's for ACI, which keeps it across years; ASI assigns a new one) and the validity year (pre-filled: requests from December onwards default to the next year). The Licence appears in the applicant's account, ready to download and use for enrollment.

   If the provider takes a while, choose **Handed to the provider, waiting for the licence** first: the request shows as **With the provider** and the applicant sees it was sent. This step is optional.

You can **Reject request…** with a reason, shown to the applicant, until the licence is emitted.

## Keeping documents

Uploaded documents, signed forms and the payment proofs of a request are deleted once they are no longer needed:

- for an emitted licence, after 31 December of the year after its validity year, e.g. on 1 January 2029 for a 2027 licence;
- for a rejected request, 12 months after the rejection.

Requests still in progress or verified keep their documents. The temporary licence PDF is kept as long as the Licence, and so are the request's details and history. Once deleted, the request shows **Documents deleted on** with the date in place of the documents, for you and the applicant.

The deletion runs every night (`licence-requests:prune-documents`). Run it with `--dry-run` to list the requests it would delete documents of.
