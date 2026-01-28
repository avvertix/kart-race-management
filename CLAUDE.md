# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Kart Race Management (KRM) is a Laravel-based web application designed to help race organizers manage kart racing championships and events. The application handles participant registrations, race number assignments, category management, tire tracking, transponder assignments, payment tracking, and exports data for MyLaps Orbits timekeeping systems.

**Stack:**
- Laravel 12.0 (PHP 8.3+)
- Laravel Jetstream 5.0 (authentication and team management)
- Livewire 3.4 (dynamic components)
- TailwindCSS (UI styling)
- Vite (frontend build tool)
- MariaDB 10.8+ (database)

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

# Rollback migrations
php artisan migrate:rollback

# Fresh database with migrations
php artisan migrate:fresh

# Seed database
php artisan db:seed
```

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

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.29
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v4
- livewire/livewire (LIVEWIRE) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v11
- alpinejs (ALPINEJS) - v3
- tailwindcss (TAILWINDCSS) - v3

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `livewire-development` — Develops reactive Livewire 3 components. Activates when creating, updating, or modifying Livewire components; working with wire:model, wire:click, wire:loading, or any wire: directives; adding real-time updates, loading states, or reactivity; debugging component behavior; writing Livewire tests; or when the user mentions Livewire, component, counter, or reactive UI.
- `tailwindcss-development` — Styles applications using Tailwind CSS v3 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.

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

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use strict typing at the head of a `.php` file: `declare(strict_types=1);`.
- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd and will be available at: `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs for the user.
- You must not run any commands to make the site available via HTTP(S). It is always available through Laravel Herd.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel file structure.
- This is perfectly fine and recommended by Laravel. Follow the existing structure from Laravel 10. We do not need to migrate to the new Laravel structure unless the user explicitly requests it.

## Laravel 10 Structure

- Middleware typically lives in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration happens in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule register in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces using only PHP — no JavaScript required.
- Instead of writing frontend code in JavaScript frameworks, you use Alpine.js to build the UI when client-side interactions are required.
- State lives on the server; the UI reflects it. Validate and authorize in actions (they're like HTTP requests).
- IMPORTANT: Activate `livewire-development` every time you're working with Livewire-related tasks.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.
</laravel-boost-guidelines>
