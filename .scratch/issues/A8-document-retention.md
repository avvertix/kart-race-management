# A8. Document retention

## Goal

Uploaded personal documents are not kept longer than needed.

## Docs

- [Process a licence request: Keeping documents](../../docs/how-to/organizer/process-a-licence-request.md#keeping-documents)

## Scope

- Command `licence-requests:prune-documents`, scheduled daily in `routes/console.php`.
- Deletes the files and rows of `licence_request_documents` 12 months after the end of the Licence's validity year, or 12 months after rejection. Payment proofs of their Orders follow the same rule.
- Temporary licence PDFs are kept with the Licence.
- `--dry-run` option listing what would be deleted.

## Acceptance criteria

- Documents of a 2027 Licence are kept until 31 December 2028 and deleted after.
- Documents of open requests (Draft, Submitted, InReview, ChangesRequested, Forwarded) are never deleted.
- The request keeps a record that documents were pruned.
