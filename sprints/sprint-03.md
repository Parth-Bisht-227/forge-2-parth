# Sprint 03 — PulseDesk Frontend

Goal: Build the React frontend — auth foundation, ticket list with filters/search, ticket create form, ticket detail with conversation/reply system. Frontend only.

Models: Hermes=delegator/coordinator, OpenClaw=implementer

## Waves

### Wave 1 — Auth Foundation + Routing Shell
- `src/lib/api.js` — fetch wrapper: bearer token from localStorage, JSON headers, error with status code
- `src/context/AuthContext.jsx` — AuthProvider: user state, /me hydration on mount, login (POST + token store), logout (revoke + clear)
- `src/pages/LoginPage.jsx` — email/password form, redirect to /tickets on success, redirect if already authed
- `src/components/ProtectedRoute.jsx` — Outlet-based, loading guard, /login redirect if unauthenticated
- `src/components/AppLayout.jsx` — nav bar: PulseDesk brand, user name, role badge, logout button
- `src/App.jsx` — BrowserRouter: /login, /tickets (protected), / redirects to /tickets
- `index.html` — title set to PulseDesk
- Added dependency: react-router-dom

### Wave 2a — Ticket List
- `src/pages/TicketsPage.jsx` — fetches /api/tickets (Laravel paginator), renders ticket table with:
  - Filters: status, priority, assignee dropdowns + debounced text search (400ms)
  - Assignee dropdown built from unfiltered ticket fetch (unique assignees)
  - Colored status/priority badges, tags as chips, requester/assignee columns
  - Loading, empty, and error states (with Retry button)
  - New Ticket button → /tickets/new
- `src/components/Badges.jsx` — StatusBadge + PriorityBadge components

### Wave 2b — Ticket Create + Detail/Conversation
- `src/pages/TicketCreatePage.jsx` — subject/description/priority/tags form, 422 field-level validation errors, navigate to detail on success
- `src/pages/TicketDetailPage.jsx` — ticket header (badges, requester, assignee, tags, timestamps) + conversation (comments oldest-first, internal notes with amber styling + badge, customer defense-in-depth filtering)
- `src/components/CommentForm.jsx` — reply composer: staff see Public/Internal toggle, customers see public-only, disable while posting
- `src/App.jsx` updated with /tickets/new and /tickets/:id routes
- Follow-up fix: enhanced apiFetch to attach error.errors from Laravel 422 responses for per-field validation

## Role Enforcement (matching backend)
- **Customers:** create tickets, see only their own tickets, post public replies only, never see internal notes (UI + defense-in-depth filter)
- **Agents/Admins:** see all org tickets, post public replies AND internal notes, see both types in conversation

## Outcome
- Shipped: Full auth flow (login/logout/token hydration), ticket list with filters/search, ticket create with validation, ticket detail with conversation and role-based reply system
- Slipped: Pagination controls on ticket list (loads page 1 only) — deferred

## Evidence
- Branch: `sprint-03-frontend`
- PR: #3 (human opened, reviewed, merged)
- Merge commit: `6f0f1ec`
- CI: green on branch before merge
- Build: npm run build — 30 modules transformed, 0 errors
