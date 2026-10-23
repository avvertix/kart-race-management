# Jetstream → Livewire Starter Kit migration

Goal: drop `laravel/jetstream`, align with the official Livewire starter kit
(Livewire 4, Flux free, Tailwind CSS 4, Fortify-only auth) using
`../plain-starter-kit` (features already installed) as the reference source.

Strategy: in-place migration in this repo. Copy starter-kit pieces in, never port
the app into the starter kit. One PR per phase, test suite green before moving on.

## Decisions

- Log out other browser sessions: dropped (unused, no starter-kit UI).
- Passkeys: bring in (UI + migration) but keep the Fortify feature disabled for now.
- Appearance / dark mode: bring in the settings page, but force light mode in Phase 5.
- Flux: free tier only, no Flux Pro. Keep own `x-table` and `x-input-date`.
- API tokens / Sanctum: Jetstream API tokens feature is off; replace `auth:sanctum`
  with `auth` and remove Sanctum if nothing else needs it.
- Starter-kit tests are Pest: convert to PHPUnit when copying. The suite stays on PHPUnit
  until after Phase 6; moving to Pest is a possible Phase 7.
- Migrations: forward-only, never rollback/fresh.
- Phase 6 (new skeleton) is documented but deferred.

## Phases

- [x] [Phase 1 – Decouple roles/permissions from Jetstream](phase-1-roles.md)
- [x] [Phase 2 – Tailwind CSS 4 + Vite](phase-2-tailwind.md)
- [x] [Phase 3 – Livewire 4 + Flux](phase-3-livewire.md)
- [x] [Phase 4 – Starter-kit auth & settings, remove Jetstream](phase-4-auth.md)
- [ ] [Phase 5 – App layout & shared components on Flux](phase-5-layout.md)
- [ ] [Phase 6 – Laravel 11+ application skeleton (deferred)](phase-6-skeleton.md)

## Notes / log

<!-- Add findings, surprises and follow-ups here as we go. -->
