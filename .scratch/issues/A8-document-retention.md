# A8. Document retention

## Goal

Uploaded personal documents are not kept longer than needed.

## Docs

- [Process a licence request: Keeping documents](../../docs/how-to/organizer/process-a-licence-request.md#keeping-documents)

## Scope

- Command `licence-requests:prune-documents`, scheduled daily in `routes/console.php`.
- Deletes the files and rows of `licence_request_documents` 12 months after the end of the Licence's validity year, or 12 months after rejection. The request's own payment proofs follow the same rule; proofs of package Orders are not touched.
- Also deletes notifications read more than 12 months ago (`notifications:prune-read`), daily.
- Temporary licence PDFs are kept with the Licence.
- `--dry-run` option listing what would be deleted.

## Acceptance criteria

- Documents of a 2027 Licence are kept until 31 December 2028 and deleted after.
- Documents of requests not emitted or rejected (Draft, Submitted, InReview, ChangesRequested, Verified, Forwarded) are never deleted.
- The request keeps a record that documents were pruned.
