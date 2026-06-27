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

## Correction Loops
1. **Schema split-brain fix:** Initial migration/model field names (title, created_by, priority default "normal", no assignee_id/tags) contradicted the full spec (subject, requester_id, assignee_id, tags, priority default "medium"). Fixed in revised commit before merge.
2. **CI migration ordering fix:** Tickets and comments migrations shared the same timestamp (2026_06_27_080727). Comments sorted alphabetically before tickets, causing FK failure on migrate. Fix: renamed comments migration to 2026_06_27_080728. Committed as 9c77edf.

## Evidence
- Branch: `sprint-02-backend-foundation`
- PR: #2 (human opened, reviewed, merged)
- Merge commit: `9dbd0ac`
- CI: green on branch before merge (after migration ordering fix)
- Tests: 28 passed, 74 assertions
- Tenant isolation: 2-org test setup proving cross-org 404s, customer visibility restrictions, internal note permission enforcement

## Demo Login Credentials (from seeder)
All passwords are `password`.
- **Admin:** admin@pulsedesk.test
- **Agents:** agent1@pulsedesk.test, agent2@pulsedesk.test
- **Customers:** customer1@pulsedesk.test, customer2@pulsedesk.test
