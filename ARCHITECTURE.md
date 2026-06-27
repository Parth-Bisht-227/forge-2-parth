# Architecture — PulseDesk

## Multi-Tenancy Approach

Every record is scoped to an `organization_id`. The tenant is derived from the **authenticated user's session** — never from a client-supplied ID.

- On ticket creation, `organization_id` and `requester_id` are forced from the authenticated user (`$request->user()`). The client cannot set or override these.
- Every list query is scoped: `Ticket::where('organization_id', $user->organization_id)`.
- Cross-org access returns **404** (not 403) to avoid leaking ticket existence.
- No global scopes or middleware packages are used — isolation is enforced explicitly in each controller method via a `authorizeTenant` check.

## Visibility Rules

| Role | Can See | Can Create |
|---|---|---|
| **Customer** | Only tickets where `requester_id = own user ID` (within their org) | Tickets, public replies on own tickets |
| **Agent** | All tickets within their org | Tickets, public replies, internal notes |
| **Admin** | All tickets within their org | Tickets, public replies, internal notes |

- Internal comments (`type = 'internal'`): only agents and admins can create them. Customers get 403 from the backend.
- Customers never receive internal comments in any API response — the backend filters them out. The frontend also applies a defense-in-depth filter on the client side.

## Data Model

### Organization (tenant)
- `id`, `name` (string), `slug` (string, unique), timestamps

### User
- `id`, `name`, `email`, `password` (hashed cast), `organization_id` (foreignId → organizations.id, cascadeOnDelete), `role` (string: admin | agent | customer), timestamps
- Original `0001_01_01_000000_create_users_table` migration is unmodified; org_id + role added via a separate migration

### Ticket
- `id`, `organization_id` (foreignId → organizations), `requester_id` (foreignId → users), `assignee_id` (foreignId → users, nullable, nullOnDelete), `subject` (string), `description` (text), `status` (string: open | in_progress | resolved | closed; default open), `priority` (string: low | medium | high | urgent; default medium), `tags` (json, nullable), timestamps
- Relations: `organization()`, `requester()`, `assignee()`, `comments()`

### Comment
- `id`, `ticket_id` (foreignId → tickets, cascadeOnDelete), `user_id` (foreignId → users, cascadeOnDelete), `body` (text), `type` (string: public | internal; default public), timestamps
- Relations: `ticket()`, `user()`

### Personal Access Tokens (Sanctum)
- Sanctum's default `personal_access_tokens` table for bearer-token auth

## API Routes

All under `/api` prefix. Auth routes are public; all others require `auth:sanctum` middleware.

| Method | Path | Auth | Notes |
|---|---|---|---|
| POST | `/api/auth/register` | — | Creates user in first org as customer, returns user + token |
| POST | `/api/auth/login` | — | Returns user + Sanctum bearer token; 401 on failure |
| GET | `/api/auth/me` | Bearer | Returns authenticated user |
| POST | `/api/auth/logout` | Bearer | Revokes current access token |
| GET | `/api/tickets` | Bearer | Tenant-scoped list; filters: `?status= &priority= &assignee_id= &q=`; paginated 15/page |
| POST | `/api/tickets` | Bearer | Creates ticket (org_id + requester_id forced from auth user) |
| GET | `/api/tickets/{id}` | Bearer | Shows ticket + comments (internal comments filtered for customers) |
| PUT/PATCH | `/api/tickets/{id}` | Bearer | Updates ticket fields |
| GET | `/api/tickets/{id}/comments` | Bearer | Lists comments for a ticket |
| POST | `/api/tickets/{id}/comments` | Bearer | Creates comment (public or internal); customers cannot create internal (403) |
| DELETE | `/api/tickets/{id}/comments/{comment}` | Bearer | Deletes comment |

## Validation

Form Request classes enforce validation:
- `StoreTicketRequest`: subject (required string), description (required string), priority (in: low/medium/high/urgent), assignee_id (nullable exists), tags (nullable array)
- `UpdateTicketRequest`: same fields, all optional (sometimes)
- `StoreCommentRequest`: body (required string), type (in: public/internal)

## Frontend Architecture

- **`src/lib/api.js`** — `apiFetch(path, options)`: prepends `VITE_API_URL`, reads bearer token from `localStorage` (key: `pulsedesk_token`), sets JSON Content-Type for POST/PUT/PATCH, throws `Error` with `.status` and `.errors` (Laravel 422 field errors) on non-2xx
- **`src/context/AuthContext.jsx`** — `AuthProvider` exposes `{ user, loading, login, logout }`. On mount: reads token, calls `GET /api/auth/me` to hydrate. `login()` POSTs credentials, stores token. `logout()` revokes token (best-effort), clears state.
- **`src/components/ProtectedRoute.jsx`** — Uses `<Outlet />`; shows loading during `/me` check; redirects to `/login` if unauthenticated
- **`src/components/AppLayout.jsx`** — Nav bar with PulseDesk brand, user name, role badge, logout button; `<Outlet />` for page content
- **Routing** (`src/App.jsx`): `/login` → LoginPage; `/tickets` → TicketsPage (protected); `/tickets/new` → TicketCreatePage (protected); `/tickets/:id` → TicketDetailPage (protected); `/` and `*` redirect to `/tickets`

## Key Decisions

- **404 over 403 for cross-org access** — prevents information leakage about ticket existence
- **Tenant scoping in controllers, not global scopes** — explicit and easy to audit per-route
- **Tailwind v4** (not v3) — uses `@import "tailwindcss"` and `@tailwindcss/postcss` plugin instead of v3's `@tailwind` directives
- **`policy.advisories.block: false`** in `composer.json` — required for Laravel 11.x installs due to Packagist security advisory blocks
- **Plain fetch + React Context** — no axios, Redux, or React Query; constraints required minimal dependencies
