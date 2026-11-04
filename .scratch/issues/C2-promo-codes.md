# C2. Promo codes: issue, revoke, public page

## Goal

Organizers issue codes for a championship and share them by link and QR code.

## Docs

- [Issue promo codes](../../docs/how-to/organizer/issue-promo-codes.md)
- [Lifecycles: Promo Code](../../docs/reference/lifecycles.md#promo-code)

## Scope

- `promo_codes`: ulid, `code` (unique), `championship_id`, `mode` (`Credit` | `Balance`), `amount`, `status` (`Unclaimed` | `Claimed` | `Revoked`), `claimed_at`, `claimed_by_user_id`, `licence_id` nullable, `licence_hash` nullable, `created_by`.
- Organizer: create one (random 8 characters or custom) or N at once (random); list per championship with status and usage; revoke an unclaimed code.
- Public page per code (unguessable URL): code, QR (BaconQrCode, as in `Participant::qrCodeSvg`), championship, what it gives, used or remaining. No personal data. Usage comes with C3.
- `mode` is `EntitlementMode`, shared with Entitlements (C3). Credits store a number of race fees, balances cents. Codes are stored uppercase; custom codes are 4–20 letters, digits or dashes. Everything is in early access.

## Out of scope

Claiming and applying (C3), wallet passes.

## Acceptance criteria

- Codes are case-insensitive and unique across the platform; ambiguous characters (0/O, 1/I) are not generated.
- A revoked code's page says so and it can't be claimed.
- Available only in championships with Entitlements enabled.
