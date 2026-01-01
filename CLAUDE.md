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
