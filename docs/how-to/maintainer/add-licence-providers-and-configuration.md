# Add licence providers and platform configuration

Organizers set up the **Enrollment Options** of each championship from the application, see [Configure a new championship](../organizer/configure-a-new-championship.md). The Licence Types, the documents a renewal asks for and everything below live in the code or in the environment and need a maintainer ([ADR 0004](../../adr/0004-licence-renewals-outside-products.md)).

## What lives where

| What | Where | Who changes it |
|---|---|---|
| Licence Types of each provider, with their race registration licence category | `config/licences.php` | Maintainer |
| Licence Providers (ACI, ASI) | `App\Enums\LicenceProvider` | Maintainer |
| Licence roles (driver, mechanic) | `App\Enums\LicenceRole` | Maintainer |
| Which providers allow the competitor licence | `App\Data\LicenceTypeData::allowsCompetitor()` | Maintainer |
| Documents a renewal asks for, and the forms to sign | `App\Enums\LicenceDocument` and `resources/views/licence-forms/` | Maintainer |
| Forms to sign published by a provider | `config/licences.php`, under `forms` | Maintainer |
| Labels of providers, roles and documents | `lang/{en,it}/licences.php` | Maintainer |
| Permissions of each role | `App\Enums\Role::permissions()` | Maintainer |
| Where uploaded licence documents are stored | `licence-documents` disk in `config/filesystems.php` | Maintainer |
| Platform-wide licence provider and country, organizer email | `.env` | Whoever deploys |
| Licence Types the instance handles, renewal price | `.env` | Whoever deploys |
| Enrollment Options | Application | Organizer |
| Who sees licences, licence requests and enrollments (early access) | User page, *Early access to licences and enrollments* | Admin |
| Who enters promo codes from the dashboard (early access) | User page, *Early access to promo codes* | Admin |

## Add or change a Licence Type

A Licence Type is one licence a provider issues, e.g. ACI *NAZ E* or *Daily*. Drivers pick it when renewing or declaring a licence. Each provider's list is in `config/licences.php`, under `types`, keyed by the provider value (`aci`, `asi`) and then by the type's **code**:

```php
'aci' => [
    'naz-e' => [
        'name' => 'NAZ E',
        'role' => LicenceRole::Driver,
        'race_licence_category' => DriverLicence::LOCAL_NATIONAL,
    ],
    'mechanic' => [
        'name' => 'Mechanic',
        'role' => LicenceRole::Mechanic,
    ],
],
```

To add one:

1. Pick a **code**: short, lowercase, with dashes, e.g. `int-f`. It is stored with every Licence and Licence Request of that type, so never change it or reuse it for another licence later.
2. Write the **name** as printed on the licence. It is shown as is, not translated.
3. Set the **role**: `LicenceRole::Driver` or `LicenceRole::Mechanic`. There is no competitor-only type: an ACI driver licence includes the competitor licence unless drivers tick *The licence is for the driver only*. ASI driver licences never include it.
4. For drivers, set **race_licence_category**: the `App\Models\DriverLicence` value the licence counts as in race registration and exports. National ACI licences are `LOCAL_NATIONAL`, international ones `LOCAL_INTERNATIONAL`, *Daily* is `DAILY` and *EasyRace* is `EASYRACE`. Mechanics have none. If no category fits, adding one changes race registration and exports too: treat it as a separate change.
5. List the types in the order drivers should see them: the list keeps it.
6. Run `php artisan test --compact tests/Feature/LicenceCatalogueTest.php` and, on a deployed instance, `php artisan config:cache` if the configuration is cached.

To stop offering a type, set `'active' => false` instead of deleting it: Licences and requests that already have it keep showing its name, and a request or declared licence that already picked it can keep it. Changing the name or the race licence category affects new Licences only, as each Licence keeps the role and category it was saved with.

## Add a Licence Provider

1. Add a case to `App\Enums\LicenceProvider`. The value is stored in the database, so pick a short lowercase value and never change it later.
2. Add its label under `provider` in `lang/en/licences.php` and `lang/it/licences.php`.
3. Add its list of Licence Types in `config/licences.php`, keyed by the same value. A provider without types is not offered.
4. If its driver licences can include the competitor licence, change `LicenceTypeData::allowsCompetitor()`.
5. Update the tests in `tests/Feature/LicenceEnumsTest.php`, which pin the values and labels.

Foreign licences are not a provider: they are never managed on the platform and stay in race registration only.

## Add a licence role

1. Add a case to `App\Enums\LicenceRole`, with a lowercase value that never changes.
2. Add its label under `role` in both `lang/*/licences.php` files.
3. Update `tests/Feature/LicenceEnumsTest.php`.

## Change the documents a renewal asks for

The documents are the same for every Licence Type of a provider; organizers check that they fit the requested one. They are the cases of `App\Enums\LicenceDocument`:

- `kind()` says whether it is a file to upload or a form given pre-filled to sign;
- `isAskedBy()` decides which providers ask for it, e.g. ASI needs no delegation;
- `isUnderageOnly()` and `isAlsoForEachParent()` decide who it is asked for;
- `description()` reads the hint under `document_description` in `lang/*/licences.php`.

To add a document, add a case with a lowercase value that never changes, its label under `document` in both `lang/*/licences.php` files, and update `tests/Feature/LicenceEnumsTest.php` and `tests/Feature/LicenceRequestActionsTest.php`.

Forms to sign are rendered by the Blade view returned by `LicenceDocument::formView()`. The parental authorisation uses `licence-forms.parental-authorisation`, the parents' self-declaration headed with the organizer's name, address and VAT number (`RACE_ORGANIZER_NAME`, `RACE_ORGANIZER_ADDRESS`, `RACE_ORGANIZER_VAT`). Forms without their paperwork yet use `licence-forms.placeholder`, which prints the holder's and parents' details.

When a provider publishes a form in a format the platform cannot generate, link it instead in `config/licences.php`, under `forms`, keyed by the provider value and then by the document value:

```php
'forms' => [
    'aci' => [
        'privacy' => 'https://www.acisport.it/…/informativa_privacy_licenziati_.pdf',
    ],
],
```

For requests to that provider, the form shows *Download from <provider>* with an external link icon instead of *Download pre-filled*: applicants fill it in by hand and upload it as usual. Organizers see *Original from <provider>* next to the upload to compare them. Only forms to sign can be linked, and links must use `https`: `tests/Feature/LicenceCatalogueTest.php` checks both. Check the link still works each season, as providers move their files.

## Give a role a new permission

Permissions are listed one by one in `App\Enums\Role::permissions()` as `resource:action`. Only `*` (everything), `*:create` and `*:update` are understood as wildcards, so `licence:*` grants nothing: list each action instead. Policies call `$user->hasPermission('resource:action')` and, for drivers, also check that the record's `user_id` is theirs.

Update `tests/Unit/RoleTest.php` with the new permission, and the [roles and permissions](../../reference/roles-and-permissions.md) table if the change is visible to users.

## Early access

Licences, licence requests and enrollments are being prepared for a future championship, so they sit behind the `App\Features\LicencesAndEnrollments` feature flag ([Laravel Pennant](https://laravel.com/docs/pennant), stored in the `features` table under its former name `App\Features\LicencesAndProducts`, so the users enabled before the rename keep it). For everyone else their pages answer 404 and their menu items are hidden. Admins always have them; an admin enables other users from the user page with *Early access to licences and enrollments*. Guests never have them, so the public enrollment pages stay hidden.

To go live, remove the flag: delete the feature class, its `EnsureFeaturesAreActive` route middleware and the `@feature` blocks in the navigation, the user page checkbox and the `EnablesLicencesAndEnrollments` test trait.

The promo code field on the dashboard sits behind its own flag, `App\Features\PromoCodes`, with the same rules: off by default, always on for admins, enabled per user with *Early access to promo codes*. It only hides the field: the link of a code still opens it and lets a signed-in user claim it, and the race registration form still accepts codes. To go live, delete the feature class, its `@feature` block in `dashboard.blade.php` and the user page checkbox.

## Store licence documents

Documents uploaded with a Licence Request go to the private `licence-documents` disk, a folder of the `local` disk (`storage/app/licence-documents`). Files there are never served directly; the application streams them after checking permissions.

The files are not encrypted by the application. Put the storage volume on an encrypted disk when deploying, and include it in backups.

## Platform settings

| Variable | Meaning | Default |
|---|---|---|
| `RACE_LICENCE_PROVIDER` | Provider named in race registration texts | `ACI Sport` |
| `RACE_LICENCE_COUNTRY` | Country of the provider, as a two letter code | `it` |
| `RACE_ORGANIZER_EMAIL` | The organizer's contact address, shown to drivers asking for a first licence. Notifications go to the organizers' accounts instead, see [Notifications](../../reference/notifications.md) | — |
| `LICENCE_TYPES` | Licence Types the instance handles, as `provider.code` separated by commas, e.g. `asi.ma,asi.mechanic`. See [Limit an instance to some Licence Types](#limit-an-instance-to-some-licence-types) | every type |
| `LICENCE_RENEWAL_PRICE` | Price of a renewal outside enrollment, in euro: a price (`50`) or a range (`50-80`). Shown to requesters before they submit; a price also pre-fills the amount the organizer sets during review. Renewals covered by an enrollment show none | — |

## Limit an instance to some Licence Types

Each championship family can run on its own deployment, e.g. one for kart and one for moto. `LICENCE_TYPES` lists the types a deployment works with; providers follow, so a provider with none of its types listed is not offered. Licences and requests of other types are not offered for renewal, declaration, enrollment, competitor or race registration, and championships can only name the providers left. They keep showing their type name. An entry that is not in `config/licences.php`, or a renewal price that cannot be read, makes every page using licences fail with an error naming the variable: open the licence pages once after deploying.
