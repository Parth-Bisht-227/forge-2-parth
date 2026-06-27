# PulseDesk

A multi-tenant support-desk SaaS. Built by orchestrating Hermes (coordinator) and OpenClaw (implementer) over Slack, with Parth as product owner.

## What's Shipped

- **Sanctum token auth** — register, login, /me, logout (bearer tokens, not SPA cookies)
- **Multi-tenant data model** — Organization-scoped tickets, comments, and users with role-based visibility
- **Ticket API** — create, list (with filters + search + pagination), show, update; tenant-isolated (cross-org returns 404)
- **Comment / conversation API** — public replies and internal notes; customers never see internal notes; agents/admins can create both
- **React frontend** — login page, ticket list with filter/search, ticket create form, ticket detail with conversation thread and role-aware reply composer

## Stack

Laravel 11 · PHP 8.2 · MySQL 8 · Laravel Sanctum ^4.3 · React 19 · Vite 8 · Tailwind v4

## How to Run

### Backend (Laravel + MySQL on WSL)

```bash
# 1. Install MySQL and PHP MySQL driver
sudo apt install php-mysql mysql-server

# 2. Start MySQL
sudo service mysql start

# 3. Create database + user (throwaway local dev creds — NOT a secret)
sudo mysql -e "
  CREATE DATABASE pulsedesk;
  CREATE USER 'pulsedesk'@'localhost' IDENTIFIED BY 'pulsedesk';
  GRANT ALL PRIVILEGES ON pulsedesk.* TO 'pulsedesk'@'localhost';
  FLUSH PRIVILEGES;
"

# 4. Configure environment
cd backend
cp .env.example .env
#   Edit .env and set:
#     DB_DATABASE=pulsedesk
#     DB_USERNAME=pulsedesk
#     DB_PASSWORD=***

# 5. Install PHP dependencies
composer install

# 6. Generate app key
php artisan key:generate

# 7. Clear cached config (REQUIRED after editing .env — pdo_mysql changes are cached)
php artisan config:clear

# 8. Run migrations + seeders (fresh, not just migrate)
php artisan migrate:fresh --seed

# 9. Start the dev server
php artisan serve             # http://127.0.0.1:8000
```

**Troubleshooting:** If the DB connection fails with a missing driver (`pdo_mysql`), install it with `sudo apt install php-mysql` then re-run `php artisan config:clear`.

### Frontend (React + Vite)

```bash
cd frontend
cp .env.example .env          # VITE_API_URL=http://127.0.0.1:8000
npm install
npm run dev                   # http://127.0.0.1:5173
```

## Demo Logins (from the seeder)

All passwords are `password`.

- **Admin:** admin@pulsedesk.test
- **Agents:** agent1@pulsedesk.test, agent2@pulsedesk.test
- **Customers:** customer1@pulsedesk.test, customer2@pulsedesk.test

## Live URL

Runs locally per the steps above. Not deployed to a public URL.

## Repository Structure

```
backend/              Laravel 11 application
  app/Models/         Organization, User, Ticket, Comment
  app/Http/Controllers/Api/  Auth, Ticket, Comment controllers
  app/Http/Requests/  StoreTicket, UpdateTicket, StoreComment form requests
  database/migrations/  Organizations, users (org_id+role), tickets, comments, personal_access_tokens
  database/seeders/   DatabaseSeeder (1 org, 5 users, 12 tickets, comments)
  routes/api.php      Auth + ticket + comment routes
  tests/Feature/Api/  AuthTest, TicketTest, CommentTest (28 tests, 74 assertions)
frontend/             React 19 + Vite + Tailwind
  src/lib/api.js      fetch wrapper (bearer token, JSON headers, error.status)
  src/context/        AuthContext (user state, /me hydration, login/logout)
  src/pages/          LoginPage, TicketsPage, TicketCreatePage, TicketDetailPage
  src/components/     ProtectedRoute, AppLayout, Badges, CommentForm
sprints/              Sprint docs (sprint-01.md, sprint-02.md, sprint-03.md)
agent-log.md          Parth → Hermes → OpenClaw orchestration log
.github/workflows/ci.yml  Backend (MySQL + migrate + test) + frontend (npm ci + build)
```

## Where Evidence Lives

- `agent-log.md` — the Parth → Hermes → OpenClaw loop across all 3 sprints
- `sprints/` — one doc per sprint with goals, issues, outcomes, evidence
- `slack-export/` — Slack proof (screenshots/export) — **PENDING: to be added**
- `evidence/screenshots/` — app/CI screenshots — **PENDING: to be added**
