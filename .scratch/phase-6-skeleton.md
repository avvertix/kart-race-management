# Phase 6 – Laravel 11+ application skeleton

Working on branch `laravel-skeleton-migration`. Reference: `../plain-starter-kit` (Laravel 13 skeleton).

## Tasks

- [x] `bootstrap/app.php` with `Application::configure()`: routing (web, api, console, health `/up`),
      middleware (`trustProxies` keeps the old header set without `X_FORWARDED_PREFIX`; `Locale` + `TraceAgentMiddleware`
      appended to `web`; `throttleApi()` + `api` limiter), exceptions (signed-link error views ported to `render()`).
- [x] `bootstrap/providers.php` (App + Fortify); `providers`/`aliases` removed from `config/app.php`.
- [x] `app/Console/Kernel.php` removed (schedule was empty; commands auto-discovered; `routes/console.php` via `withRouting`).
- [x] `RouteServiceProvider` removed (`api` limiter → `AppServiceProvider`; `HOME` → `'/dashboard'` in `config/fortify.php`,
      tests use `route('dashboard', absolute: false)`; guest redirect now framework default → `route('dashboard')`).
- [x] `EventServiceProvider` removed. Event discovery **disabled** (`withEvents(discover: false)`): `app/Listeners` holds
      pipeline stages (`ApplyBonusToParticipant`, `CheckParticipantForWildcard`, `RemoveBonusFromParticipantWhenCostChanges`)
      that discovery would register as listeners. `LogNotification` registered in `AppServiceProvider`; email verification
      listener still registered by the framework.
- [x] `BroadcastServiceProvider` + `routes/channels.php` removed (were disabled). `AuthServiceProvider` removed: policies
      auto-discovered (all follow `App\Policies\{Model}Policy`), `drivers:*` gates → `AppServiceProvider`.
- [x] Removed middleware that only extended framework ones with defaults: Authenticate, EncryptCookies,
      PreventRequestForgery, PreventRequestsDuringMaintenance, RedirectIfAuthenticated, TrimStrings, TrustHosts,
      TrustProxies, ValidateSignature. Only `Locale` remains.
- [x] Config: removed `cors`, `view` (identical to framework), `hashing` (default values), `broadcasting` (unused, old env name).
      Other config files kept like the starter kit does (they hold app-specific values).
- [x] `artisan` / `public/index.php` → `handleCommand` / `handleRequest`; `tests/CreatesApplication` removed (framework default).
- [x] `phpunit.xml` env aligned (`CACHE_STORE`, `BROADCAST_CONNECTION`, `APP_MAINTENANCE_DRIVER`); `.env.example` cleaned
      (`CACHE_STORE`, `BROADCAST_CONNECTION`, `MAIL_SCHEME`, Pusher vars removed).
- [x] Composer `post-autoload-dump` / `pre-package-uninstall` aligned with the starter kit.
- [x] `docker/configure.sh` writes `CACHE_STORE` (falls back to `CACHE_DRIVER` for existing deployments, default `file`).
      Production cache moves from `database` (silent default since Laravel 11) to `file`.
- [x] `DB::prohibitDestructiveCommands()` in production (`AppServiceProvider`).
- [x] `Date::use(CarbonImmutable::class)`; `BibReservationFactory::expired()` typed `CarbonInterface`. Full suite green
      (1142 tests).
- [x] Dependencies: removed `spatie/laravel-ignition`; added `larastan/larastan` ^3.13 (`phpstan.neon` level 7 +
      `phpstan-baseline.neon`, 1302 baselined errors) and `laravel/pail` ^1.2.
- [x] Composer scripts from the starter kit: `setup`, `dev`, `lint`, `lint:check`, `types:check`; `test` runs
      `config:clear` + `artisan test` (lint/types not chained yet, CI still calls phpunit); `format` kept as alias;
      `boost:update` on `post-update-cmd`.
- [ ] Not applied: abstract base `Controller` without traits (51 controllers use `authorize`/`validate`), production
      password rules.
- [ ] Optional: reduce the PHPStan baseline; add `types:check` / `lint:check` to `composer test` and CI.
- [ ] Docker image / `deploy/` still boots; full test suite green.
