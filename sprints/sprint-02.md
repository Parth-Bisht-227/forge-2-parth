# Sprint 02 — PulseDesk Must-Tier Backend Foundation

Goal: Build the multi-tenant data model, Sanctum token auth, and the ticket/comment API. Backend only — no frontend, no SLA, no notifications.

Models: Hermes=delegator/coordinator, OpenClaw=implementer

## Issues
- [x] #1 Multi-tenant data model + Sanctum auth — Organization, User (with org_id + role), Sanctum token auth (register/login/me/logout)
- [x] #2 Ticket + Comment API (tenant-scoped) — Full CRUD for tickets, comments scoped to tickets, tenant isolation enforced
- [ ] #3 (Spec was truncated in delegation — pending clarification if there was a third issue)

## Outcome
- Shipped: Sanctum bootstrapped (`install:api`); Organization model/migration; User with `organization_id` + `role`; auth endpoints (register/login/me/logout); Ticket CRUD with tenant scoping; Comment CRUD scoped to tickets; 21 tests passing (50 assertions); DatabaseSeeder with demo org
- Slipped: Issue #3 (if any) — task text was truncated
- PRs: sprint-02-backend-foundation branch ready for PR — human merges
