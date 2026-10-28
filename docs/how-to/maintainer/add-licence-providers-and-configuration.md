# Add licence providers and platform configuration

Organizers set up **Licence Types**, **Licence Grades**, required documents, **Products** and **Prices** from the application, see [Set up licence types, products and prices](../organizer/set-up-licence-types-and-products.md). What follows lives in the code or in the environment and needs a maintainer.

## What lives where

| What | Where | Who changes it |
|---|---|---|
| Licence Providers (ACI, ASI) | `App\Enums\LicenceProvider` | Maintainer |
| Licence roles (driver, competitor, mechanic) | `App\Enums\LicenceRole` | Maintainer |
| ACI levels (national, international) and their race registration licence category | `App\Enums\LicenceLevel` | Maintainer |
| Labels of providers, roles and levels | `lang/{en,it}/licences.php` | Maintainer |
| Permissions of each role | `App\Enums\Role::permissions()` | Maintainer |
| Where uploaded licence documents are stored | `licence-documents` disk in `config/filesystems.php` | Maintainer |
| Platform-wide licence provider and country, organizer email | `.env` | Whoever deploys |
| Licence Types, grades, required documents, Products, Prices | Application | Organizer |

## Add a Licence Provider

1. Add a case to `App\Enums\LicenceProvider`. The value is stored in the database, so pick a short lowercase value and never change it later.
2. Add its label under `provider` in `lang/en/licences.php` and `lang/it/licences.php`.
3. Update the tests in `tests/Feature/LicenceEnumsTest.php`, which pin the values and labels.

Foreign licences are not a provider: they are never managed on the platform and stay in race registration only.

## Add a licence role

1. Add a case to `App\Enums\LicenceRole`, with a lowercase value that never changes.
2. Add its label under `role` in both `lang/*/licences.php` files.
3. Update `tests/Feature/LicenceEnumsTest.php`.

## Add an ACI level

Levels exist only for ACI. Each level maps to the driver licence category used in race registration (`App\Models\DriverLicence`), which keeps registration and exports working as they do today.

1. Add a case to `App\Enums\LicenceLevel`.
2. Map it in `LicenceLevel::raceLicence()`. If no `DriverLicence` category fits, adding one changes race registration and exports too: treat it as a separate change.
3. Add its label under `level` in both `lang/*/licences.php` files.
4. Update `tests/Feature/LicenceEnumsTest.php`.

Grades (e.g. *C Junior*, *International B*) are not code: organizers list them per level in the application.

## Give a role a new permission

Permissions are listed one by one in `App\Enums\Role::permissions()` as `resource:action`. Only `*` (everything), `*:create` and `*:update` are understood as wildcards, so `licence-type:*` grants nothing: list each action instead. Policies call `$user->hasPermission('resource:action')` and, for drivers, also check that the record's `user_id` is theirs.

Update `tests/Unit/RoleTest.php` with the new permission, and the [roles and permissions](../../reference/roles-and-permissions.md) table if the change is visible to users.

## Store licence documents

Documents uploaded with a Licence Request go to the private `licence-documents` disk, a folder of the `local` disk (`storage/app/licence-documents`). Files there are never served directly; the application streams them after checking permissions.

The files are not encrypted by the application. Put the storage volume on an encrypted disk when deploying, and include it in backups.

## Platform settings

| Variable | Meaning | Default |
|---|---|---|
| `RACE_LICENCE_PROVIDER` | Provider named in race registration texts | `ACI Sport` |
| `RACE_LICENCE_COUNTRY` | Country of the provider, as a two letter code | `it` |
| `RACE_ORGANIZER_EMAIL` | Where organizer notifications are sent, e.g. a submitted Licence Request | — |
