# Sprint 02 — PulseDesk Must-Tier Backend Foundation

Goal: Build the multi-tenant data model, Sanctum token auth, and the ticket/comment API. Backend only — no frontend, no SLA, no notifications.

Models: Hermes=delegator/coordinator, OpenClaw=implementer

## Issues
- [x] #1 Multi-tenant data model + Sanctum auth — Organization model, User with org_id + role, Sanctum token auth (register/login/me/logout)
- [x] #2 Ticket + Comment API (tenant-scoped) — Full CRUD with tenant isolation (404), visibility rules, filters/search/pagination, Form Requests
- [x] #3 Seeders, tenant-isolation tests, sprint doc

## Outcome
- Shipped: Sanctum bootstrapped; Organization/User/Ticket/Comment models + migrations; auth endpoints; tenant-scoped ticket API with filters/search/pagination; comment API with public/internal visibility rules; comprehensive seed; 28 tests passing (74 assertions)
- Slipped: None
- PRs: sprint-02-backend-foundation branch ready for PR — human merges

## Demo Login Credentials (from seeder)
All passwords are `password`.
- **Admin:** admin@pulsedesk.test
- **Agents:** agent1@pulsedesk.test, agent2@pulsedesk.test
- **Customers:** customer1@pulsedesk.test, customer2@pulsedesk.test
