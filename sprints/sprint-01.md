# Sprint 01 — PulseDesk Scaffolding

Goal: Stand up the full-stack skeleton (Laravel 11 backend + React 19/Vite/Tailwind frontend) with working CI and docs. No domain features this sprint.

Models: Hermes=delegator/coordinator, OpenClaw=implementer

## Issues
- [x] #1 Scaffold Laravel 11 backend in `backend/` — Laravel 11 + Sanctum installed, default tests green
- [x] #2 Scaffold React 19 + Vite + Tailwind frontend in `frontend/` — Vite + React 19 + Tailwind v4, `npm run build` passes
- [x] #3 Fix CI + add frontend job — DB_PASSWORD sed corrected, frontend `npm ci && npm run build` job added, sprint doc filled

## Outcome
- Shipped: Laravel 11 backend (Sanctum, MySQL config, key:generate, tests passing); React 19 + Vite + Tailwind v4 frontend (build passing); CI with backend (MySQL + migrations + tests) and frontend (npm ci + build) jobs; sprint-01.md documentation
- Slipped / moved to next sprint: PulseDesk domain features (tickets, tenants, auth flows) — intentionally out of scope for Sprint 1

## Evidence
- Branch: `sprint-01-scaffolds`
- PR: #1 (human opened, reviewed, merged)
- Merge commit: `34a69fb`
- CI: green on branch before merge
- Stack versions: Laravel 11, PHP 8.2, MySQL 8, Laravel Sanctum ^4.3, React 19.2.7, Vite 8, Tailwind v4.3.1
- Key commands verified: `composer install --no-interaction` ✅, `php artisan key:generate` ✅, `php artisan test` ✅ (2 passed), `npm install` ✅, `npm run build` ✅
