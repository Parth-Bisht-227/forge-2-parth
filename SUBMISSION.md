# Submission Checklist — PulseDesk (Forge 2 / Edition 1)

## What Shipped

PulseDesk is a multi-tenant support-desk SaaS with Sanctum token auth, tenant-isolated ticket and comment APIs, and a React frontend. Built across 3 sprints.

### Sprint 01 — Scaffolding (PR #1, merge `34a69fb`)
- Laravel 11 backend scaffolded in `backend/` with Sanctum installed
- React 19 + Vite 8 + Tailwind v4 frontend scaffolded in `frontend/`
- CI pipeline: backend (MySQL 8 + migrate + test) and frontend (npm ci + build)
- CI green on branch before merge

### Sprint 02 — Backend Foundation (PR #2, merge `9dbd0ac`)
- Sanctum bootstrapped (`install:api`)
- Organization model; User with `organization_id` + `role` (admin/agent/customer)
- Sanctum token auth: register, login, /me, logout
- Tenant-scoped ticket API: create, list (filters + search + pagination), show, update
- Comment API: public replies + internal notes with role-based permissions
- Tenant isolation: cross-org returns 404; customers see only own tickets; internal notes hidden from customers
- Seeders: 1 org, 1 admin, 2 agents, 2 customers, 12 tickets, comments (public + internal)
- Tests: 28 passed, 74 assertions (AuthTest, TicketTest, CommentTest)
- Correction loops: schema field-name alignment, CI migration ordering fix
- CI green on branch before merge

### Sprint 03 — Frontend (PR #3, merge `6f0f1ec`)
- Auth foundation: apiFetch wrapper, AuthContext (token hydration, login/logout), LoginPage, ProtectedRoute, AppLayout with nav + role badge + logout
- Ticket list: filters (status/priority/assignee), debounced search (400ms), colored badges, tags as chips, loading/empty/error states
- Ticket create: subject/description/priority/tags form with 422 field-level validation
- Ticket detail: ticket header + conversation thread (oldest-first), internal notes with amber styling, defense-in-depth customer filtering
- Reply composer: staff see Public/Internal toggle; customers see public-only
- npm run build: 30 modules, 0 errors
- CI green on branch before merge

## Orchestration Model

- **Parth (Human / Product Owner):** Planned sprints, opened/reviewed/merged PRs, made final decisions
- **Hermes (Coordinator):** Broke sprints into issues, delegated to OpenClaw via Slack (`#agent-coder`), reviewed code, flagged bugs, sent fix requests
- **OpenClaw (Implementer):** Scaffolded, coded, tested, seeded, committed, pushed, reported back via Slack with standard headers (What I Did / What's Left / What Needs Your Call)
- All communication over Slack. All model calls through EastRouter.

## Demo Login Credentials

All passwords are `password`.

- **Admin:** admin@pulsedesk.test
- **Agents:** agent1@pulsedesk.test, agent2@pulsedesk.test
- **Customers:** customer1@pulsedesk.test, customer2@pulsedesk.test

## Checklist

- [x] Backend = Laravel 11 + MySQL 8; Frontend = React 19 + Vite + Tailwind
- [x] Multi-tenancy: Org A cannot see Org B data (tenant derived from authenticated user, not client-supplied)
- [x] `php artisan migrate --seed` works from a fresh clone
- [x] README has exact run steps
- [x] `.github/workflows/ci.yml` present with green runs on the Actions tab
- [x] PRs (#1, #2, #3) merged by the human; commit authors are the agents
- [x] `agent-log.md` shows the real Parth → Hermes → OpenClaw loop
- [x] `sprints/` has 3 sprint docs
- [ ] Slack proof in `slack-export/` — **PENDING: to be added**
- [ ] App / agents-running / CI screenshots in `evidence/screenshots/` — **PENDING: to be added**
