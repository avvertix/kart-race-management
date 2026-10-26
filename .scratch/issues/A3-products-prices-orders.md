# A3. Products, Prices, Orders and payments

## Goal

The organizer sells things at fixed or ranged prices; Users place Orders, upload payment proofs, and the organizer sets final prices and confirms payments.

## Docs

- [Set up licence types, products and prices](../../docs/how-to/organizer/set-up-licence-types-and-products.md)
- [Lifecycles: Order](../../docs/reference/lifecycles.md#order)
- Glossary: Product, Price, Order

## Scope

- `products`: ulid, `kind` (`LicenceEmission` | `Enrollment` | `Package`), `title`, `description`, `championship_id` nullable, `licence_type_id` nullable, `active`. A licence Product names exactly one Licence Type; an enrollment Product one championship.
- `prices`: `product_id`, `amount` nullable, `amount_min`/`amount_max` nullable (a range), `currency`, `starts_at`, `ends_at` nullable. Exactly one of fixed or range. `Product::currentPrice()` picks the Price valid now.
- `orders`: ulid, `user_id`, `status` (`AwaitingPrice` | `AwaitingPayment` | `PaymentSubmitted` | `Paid` | `Cancelled`), `total` nullable, `currency`, `price_override_reason` nullable, `paid_at`, `confirmed_by`, `priced_by`, `priced_at`.
- `order_lines`: `order_id`, `product_id`, `price_id` (the locked Price), `description`, `amount` nullable.
- `payments`: add nullable morph `payable_type`/`payable_id`, make `participant_id` nullable. Existing rows and race payment routes unchanged.
- Actions:
  - `PlaceOrder(User, Product)`: requires a verified email; locks the current Price; status `AwaitingPrice` for a range, `AwaitingPayment` for a fixed amount.
  - `SetOrderPrice`: organizer; pre-filled with the range's lower bound; outside the range requires a reason; allowed until a proof is uploaded. Fires `OrderPriced`.
  - `SubmitOrderPayment`: upload proof, only from `AwaitingPayment`.
  - `ConfirmOrderPayment` and `RejectOrderPayment` (back to `AwaitingPayment`). Confirm fires `OrderPaid`.
  - `CancelOrder`.
- Organizer pages: Products with their Prices (CRUD), orders list filterable by status, order detail with set price, proof preview, confirm or reject.
- Public licences page lists active Products with their current Price ("€180" or "from €230 to €310, final price after review").

## Out of scope

Package grants (C4), refunds (settled by email), online payment.

## Acceptance criteria

- An Order placed during an early-bird Price keeps it after the Price period ends.
- A guest or a User with an unverified email cannot place an Order.
- A ranged Order cannot receive a proof until the organizer sets its price; the reason is required only outside the range.
- The price cannot change after a proof is uploaded.
- Existing participant payments keep working; the existing payment tests pass.
