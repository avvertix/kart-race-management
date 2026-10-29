# A9. Licence Request as a single PDF

## Goal

The organizer downloads a whole Licence Request as one PDF to forward to the Licence Provider, instead of opening each document from the review page.

## Docs

- [Process a licence request](../../docs/how-to/organizer/process-a-licence-request.md)

## Scope

- A summary page (dompdf) with the licence, holder and parents, followed by the uploaded PDFs and images in section order.
- A "Download as one PDF" link on the A6 review page, authorized like the review page.
- **Decide here** the merge dependency, needs approval before adding: `setasign/fpdi`'s free parser can't read PDF 1.5+ files with compressed xref streams (common in phone scans); alternatives are `qpdf` or `ghostscript` in the Docker image, or a ZIP of the files as a fallback.

## Out of scope

Changes to the review flow (A6).

## Acceptance criteria

- The PDF opens and contains every uploaded document, including phone-scanned PDFs.
- Drivers get 403 on the download.
