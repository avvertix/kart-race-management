# A2. Licence Types, Grades and required documents

## Goal

Organizers configure which licences they process, their common Licence Grades, and the documents and forms each request needs.

## Docs

- [Set up licence types, products and prices](../../docs/how-to/organizer/set-up-licence-types-and-products.md)
- Glossary: Licence Type, Licence Grade

## Scope

- `licence_types`: ulid, `provider`, `role`, `name`, `description`, `race_licence_category` (nullable; used for ASI, ACI derives it from the level), `active`, timestamps.
- `licence_grades`: `licence_type_id`, `level` (nullable, ACI only), `name`, `sort`. The applicant always also has "Other, specify".
- `licence_type_documents`: `licence_type_id`, `name`, `description`, `kind` (`upload` | `signed_form`), `template` (nullable Blade view for the pre-filled form), `scope` (`holder` | `each_parent` | `once`), `underage_only`, `required`, `sort`. `each_parent` documents are asked once per parent or guardian entered.
- Organizer pages under `/m/licence-types` (list, create, edit, deactivate), following `ChampionshipCategoryController`. Grades and documents are edited inside the Licence Type.
- Seeder with the ACI driver, competitor and mechanic types and documents from the brief, including the always-required self-declaration, and a few common grades per level.

## Out of scope

Products and prices (A3), the Blade templates of the signed forms (they wait for the real paperwork; use a placeholder template).

## Acceptance criteria

- An organizer can create an ACI driver Licence Type with national and international grades and an ASI type without levels.
- An ACI type cannot have grades without a level; an ASI type cannot have grades with one.
- Deactivated types are not offered for new requests but stay on existing ones.
- Drivers get 403 on every `/m/licence-types` route.
