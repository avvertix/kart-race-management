# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Kart Race Management (KRM) is a Laravel-based web application designed to help race organizers manage kart racing championships and events. The application handles participant registrations, race number assignments, category management, tire tracking, transponder assignments, payment tracking, and exports data for MyLaps Orbits timekeeping systems.

## Development Commands

### Installation & Setup
```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install

# Create and configure .env file
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate

# Create admin user
php artisan user:add --email your@email.com --role admin
```

### Development Workflow
```bash
# Start Laravel development server
php artisan serve

# Build frontend assets in development mode
npm run dev

# Build frontend assets for production
npm run build

# Run tests
composer test
# OR
vendor/bin/phpunit

# Run specific test
vendor/bin/phpunit --filter TestClassName

# Format code using Laravel Pint
composer format
# OR
vendor/bin/pint
```

### Database & Migrations
```bash
# Run migrations
php artisan migrate

# Seed database
php artisan db:seed
```

When executing tests and output shows error of missing table, remove `storage/app/migration-checksum_testing.txt and re-run the test.

### Artisan Commands
```bash
# Add a new user
php artisan user:add --email user@example.com --role admin|user

# Synchronize race time
php artisan race:sync-time
```

## Architecture Overview

### Core Domain Models

The application is organized around three primary entities:

1. **Championship** - A racing season/championship containing multiple races
   - Has categories (racing classes)
   - Has championship-specific tires
   - Has BIB (race number) reservations
   - Has bonus settings and wildcard settings
   - Has payment configuration

2. **Race** - An individual racing event within a championship
   - Belongs to a Championship
   - Has registration windows (opens_at/closes_at)
   - Has event dates (event_start_at/event_end_at)
   - Has participants
   - Can be zonal or regular type
   - Has participant limits
   - Can be cancelled, active, scheduled, or concluded

3. **Participant** - A racer registered for a specific race
   - Belongs to a Race and Championship
   - Has driver information (licence, name, etc.)
   - Has competitor information (team/mechanic details)
   - Has vehicle information
   - Assigned a BIB (race number)
   - Assigned a Category
   - Has tire assignments
   - Has transponder assignments
   - Has payment tracking
   - Can have bonuses applied
   - Uses ULIDs for identification

### Key Subsystems

**Registration System** (`app/Actions/RegisterParticipant.php`, `app/Actions/UpdateParticipantRegistration.php`)
- Handles participant registration with validation
- Uses cache locks to prevent duplicate BIB assignments
- Generates race numbers via `GenerateRaceNumber` action
- Fires `ParticipantRegistered` event on successful registration

**Wildcard System** (`app/Actions/Wildcard/`, `app/Listeners/CheckParticipantForWildcard.php`)
- Automatic wildcard assignment based on:
  - First race participation (`AttributeWildcardBasedOnFirstRace`)
  - Bonus points (`AttributeWildcardBasedOnBonus`)
  - BIB reservations (`AttributeWildcardBasedOnBibReservation`)
- Strategy pattern implementation with `WildcardStrategy` enum

**Bonus System** (`app/Listeners/ApplyBonusToParticipant.php`)
- Automatically applies bonuses to participants on registration
- Configured per championship
- Can trigger wildcard eligibility

**Export System** (`app/Exports/`)
- `RaceParticipantsForTimingExport` - MyLaps Orbits format
- `RaceParticipantsExport` - General participant list
- `ChampionshipParticipantsExport` - Championship-wide export
- `AciParticipantPromotionExport` - ACI promotion export
- `PrintRaceReceipts` - PDF receipts generation
- Uses Laravel Excel (Maatwebsite/Excel) package

**Tire & Transponder Management**
- Tire assignment and verification via signed URLs
- Transponder assignment and tracking
- Controllers: `ParticipantTiresController`, `ParticipantTransponderController`, `RaceTiresController`, `RaceTranspondersController`

**Communication System** (`app/Models/CommunicationMessage.php`)
- Broadcast messages to participants
- Supports notifications and announcements

### Data Transfer Objects

Uses Spatie Laravel Data for typed DTOs:
- `WildcardSettingsData` - Wildcard configuration
- `PaymentSettingsData` - Payment configuration
- `BonusSettingsData` - Bonus configuration
- `AliasesData` - Participant aliases

### Livewire Components

Located in `app/Livewire/`:
- `ParticipantListing` - Display and filter participants
- `ParticipantSelector` - Select participant from list
- `CategorySelector` - Category selection
- `RaceNumber` - Race number display/input
- `ChangeParticipantAlias` - Modify participant aliases
- `ChangeParticipantNotes` - Modify participant notes
- `ChangeParticipantPaymentChannel` - Update payment method
- `WildcardSettings` - Configure wildcard rules

### Authentication & Authorization

- Uses Laravel Jetstream with Fortify for authentication
- Role-based access control via `HasRole` trait
- Roles: `admin` and regular `user`
- Two-factor authentication support

### Activity Logging

- Uses Spatie Laravel Activity Log
- Logs participant changes and important events
- Encrypts sensitive participant data in logs via `EncryptSensibleParticipantData`

### Model Routing

All primary models (Championship, Race, Participant, Category, etc.) use ULIDs instead of auto-incrementing IDs:
- Route parameter: `uuid` (via `getRouteKeyName()`)
- Generated via `HasUlids` trait

### Testing

- PHPUnit-based feature and unit tests in `tests/`
- Uses `CreatesApplication` trait
- Helper traits for creating test data: `CreateCompetitor`, `CreateDriver`, `CreateMechanic`, `CreateVehicle`
- Database: Uses `plannr/laravel-fast-refresh-database` for faster test database refreshing

## Key Configuration Files

- `config/races.php` - Race-specific configuration (timezone, etc.)
- Routes defined in `routes/web.php` (main application routes)
- `routes/api.php` - API routes (if applicable)

## Important Implementation Notes

### BIB (Race Number) Assignment
- BIB assignment uses cache locks to prevent race conditions
- Generated via `GenerateRaceNumber` action
- Can be reserved via BibReservation model
- Reservation affects wildcard eligibility

### Participant Registration Flow
1. Validate input (driver, competitor, vehicle, category)
2. Acquire cache lock for race to prevent duplicate BIBs
3. Generate BIB number
4. Create participant record
5. Fire `ParticipantRegistered` event
6. Event listeners apply bonuses and check wildcard eligibility

### Event-Driven Architecture
- `ParticipantRegistered` event triggers:
  - `ApplyBonusToParticipant` listener
  - `CheckParticipantForWildcard` listener
- `ParticipantUpdated` event for tracking changes

### Signed URLs for Security
- Tire verification uses signed URLs (`ParticipantTireVerificationController`)
- Participant signature notifications use signed URLs
- Prevents unauthorized access to participant actions

### Localization
- Multi-language support (participants have `locale` preference)
- Participant implements `HasLocalePreference` interface
- Translations in `resources/lang/`

### Database Schema Conventions
- ULIDs for primary models (Championship, Race, Participant)
- Timestamps on all models
- Soft deletes for participants (TrashedParticipant model for recovery)

## Docker Deployment

Application is containerized for production deployment:
- Example Docker Compose configuration in `deploy/docker-compose.yml`
- Environment variables in `.env` (see `.env.example` in deploy folder)
- Critical env vars: `APP_KEY`, `APP_URL`, `DB_*`, `RACE_ORGANIZER_*`

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `php artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

=== spatie/laravel-activitylog/core rules ===

# spatie/laravel-activitylog

Activity logging package for Laravel. Logs model events and manual activities to a database table.

## Key Concepts

- **Activity**: An Eloquent model (`Spatie\Activitylog\Models\Activity`) storing log entries with subject, causer, event, attribute_changes, and properties.
- **Subject**: The model being acted upon (polymorphic `subject_type`/`subject_id`).
- **Causer**: The model that caused the action, typically the authenticated user (polymorphic `causer_type`/`causer_id`).
- **LogOptions**: Fluent configuration object returned by `getActivitylogOptions()` on models using the `LogsActivity` trait.
- **ActivityEvent**: Enum with cases `Created`, `Updated`, `Deleted`, `Restored`.
- **`attribute_changes`** column: stores `{"attributes": {...}, "old": {...}}` for tracked model changes.
- **`properties`** column: stores custom user data set via `withProperties()`.

## Traits

### `LogsActivity`

Add to models to automatically log create/update/delete events. Optionally implement `getActivitylogOptions()` to configure which attributes to track (defaults to logging events without attribute changes).

```php
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Article extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
```

### `CausesActivity`

Add to user/causer models. Provides `activitiesAsCauser()` relationship.

### `HasActivity`

Combines `LogsActivity` and `CausesActivity`. Provides `activities()`, `activitiesAsSubject()`, and `activitiesAsCauser()`.

## Manual Logging

```php
activity()
    ->performedOn($article)
    ->causedBy($user)
    ->event(ActivityEvent::Updated)
    ->withProperties(['key' => 'value'])
    ->log('Article was updated');
```

## LogOptions Methods

| Method | Description |
|--------|-------------|
| `logFillable()` | Log all fillable attributes |
| `logAll()` | Log all attributes |
| `logOnly(array)` | Log specific attributes |
| `logExcept(array)` | Exclude attributes |
| `logOnlyDirty()` | Only log changed attributes |
| `dontLogEmptyChanges()` | Skip logging when no tracked attributes changed |
| `dontLogIfAttributesChangedOnly(array)` | Ignore updates that only change these attributes |
| `useLogName(string)` | Set custom log name |
| `setDescriptionForEvent(Closure)` | Custom description per event |
| `useAttributeRawValues(array)` | Store raw (uncast) values |

## Querying Activities

```php
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Enums\ActivityEvent;

Activity::forEvent(ActivityEvent::Created)->get();
Activity::causedBy($user)->get();
Activity::forSubject($article)->get();
Activity::inLog('orders')->get();
```

## Setting the causer

Override the causer for a block of code:

```php
use Spatie\Activitylog\Facades\Activity;

Activity::defaultCauser($admin, function () {
    // all activities here are caused by $admin
});

// or set globally for the rest of the request
Activity::defaultCauser($admin);
```

## Disabling Logging

```php
activity()->withoutLogging(function () {
    // no activities logged here
});
```

## Accessing Changes and Properties

```php
$activity = Activity::latest()->first();

// Tracked model changes (set automatically by LogsActivity)
$activity->attribute_changes; // Collection: {"attributes": {...}, "old": {...}}

// Custom user data (set via withProperties)
$activity->properties; // Collection
$activity->getProperty('key'); // single value
```

## Custom Activity Model

Set `activity_model` in `config/activitylog.php` to a class that extends `Model` and implements `Spatie\Activitylog\Contracts\Activity`. Use a custom model for custom table names or database connections.

## Customizing Actions

The package uses action classes (`LogActivityAction`, `CleanActivityLogAction`) that can be extended and swapped via config:

```php
// config/activitylog.php
'actions' => [
    'log_activity' => \App\Actions\CustomLogActivityAction::class,
    'clean_log' => \App\Actions\CustomCleanAction::class,
],
```

Custom action classes must extend the originals. Override protected methods (`save()`, `beforeActivityLogged()`, `resolveDescription()`, etc.) to customize behavior.

## Configuration

Key config options in `config/activitylog.php`:
- `enabled`: Master on/off switch (env: `ACTIVITYLOG_ENABLED`)
- `clean_after_days`: Days to keep records for `activitylog:clean` command
- `default_log_name`: Default log name (string)
- `default_auth_driver`: Auth driver for causer resolution
- `include_soft_deleted_subjects`: Include soft-deleted subjects
- `activity_model`: Custom Activity model class
- `default_except_attributes`: Globally excluded attributes
- `actions.log_activity`: Action class for logging activities
- `actions.clean_log`: Action class for cleaning old activities

</laravel-boost-guidelines>
