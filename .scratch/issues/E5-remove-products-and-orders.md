# E5. Remove Products, Packages and Orders

## Goal

Nothing is sold outside enrollment, so the commerce layer goes: drivers and organizers only see enrollment options and the Enrollment's payment.

## Docs

- [ADR 0005](../../docs/adr/0005-enrollment-fee-and-packages.md): mark superseded by ADR 0006.
- [ADR 0004](../../docs/adr/0004-licence-renewals-outside-products.md): note that Products no longer exist; renewals keep their own payment.
- [GLOSSARY: Commerce](../../GLOSSARY.md): remove Product, Price, Order, Promotional Package; keep Entitlement, Promo Code.
- Every doc mentioning products or orders (`grep -ril "product\|order" docs`).

## Scope

- Remove models, actions, controllers, policies, routes, views and tests of: Product, Price, Order, OrderLine, ProductGrant, `ProductKind`, `ProductGrantKind`, `PlaceOrder`, `SetOrderPrice`, `CancelOrder`, `ConfirmOrderPayment`, `RejectOrderPayment`, `GrantPackages`, `EnrollPackagedRequest`, `OrderPaid` with its listener, the public products pages, the products and orders admin, My orders.
- Rewrite the branch's unreleased migrations so these tables and columns are never created (`products`, `prices`, `orders`, `order_lines`, `product_grants`, `orders.licence_id`, `licence_requests.covering_order_id`, `entitlements.product_grant_id`). Note it in the commit: a development database needs `migrate:fresh`.
- Remove the `order:*` and `product:*` permissions from `Role` and their tests; `NotificationType::OrderPriceSet` and its notification.
- Rename the early-access flag `LicencesAndProducts` to `LicencesAndEnrollments`, keeping the stored feature values.
- Navigation: nothing links to products or orders.

## Out of scope

Selling race fees or other items (a later feature would bring its own model).

## Acceptance criteria

- `grep -rn "Product\b\|Order\b" app resources routes` finds nothing about the removed layer.
- The full suite, Larastan and the Italian translations test pass after `migrate:fresh`.
