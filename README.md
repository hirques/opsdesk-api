<div align="center">

# OpsDesk API

**Laravel 13 · PostgreSQL · Docker · Sanctum · Role-based Access Control · OpenAPI**

A realistic, intentionally small REST API for operational service tickets, designed to demonstrate authentication, authorization, database design, containerization and API testing.

[API reference](public/openapi.yaml) · [Security model](#permissions) · [Quick start](#quick-start)

</div>

---

## Overview

This is a **standalone portfolio project**, not a copy of a company system. It uses synthetic examples and does **not** include company infrastructure, tokens, credentials or proprietary business logic.

**Features**

- API versioning (`/api/v1`) and JSON responses
- Account registration, token login, token revocation (Laravel Sanctum)
- Server-enforced RBAC with `viewer`, `operator`, and `admin`
- Paginated service tickets, priority and status management
- Owner-scoped ticket visibility and explicit ticket policies
- PostgreSQL schema with foreign keys and indexes
- Nginx + PHP-FPM + PostgreSQL using Docker Compose
- OpenAPI 3.0 specification and Swagger UI at `/docs/`
- PHP feature tests and GitHub Actions CI

## Tech stack

| Layer | Technology |
| --- | --- |
| Application | PHP 8.4 · Laravel 13 |
| Authentication | Laravel Sanctum (Bearer tokens) |
| Database | PostgreSQL 17 (SQLite in automated tests) |
| Web | Nginx · PHP-FPM |
| Infrastructure | Docker · Docker Compose |
| Documentation | OpenAPI 3.0 · Swagger UI |
| CI | GitHub Actions · PHPUnit |

## Quick start

**Requirements:** Docker Desktop (Windows, macOS) or Docker Engine with Compose (Linux). The first build downloads dependencies from Packagist and Docker Hub.

**Windows PowerShell:**

```powershell
.\scripts\setup.ps1
```

**Linux / macOS:**

```bash
sh scripts/setup.sh
```

The setup script creates an ignored `.env` with a random application key and database password, builds the containers and runs migrations. It does not overwrite an existing `.env`.

Then visit:

- **API:** http://localhost:8080
- **Swagger UI:** http://localhost:8080/docs/
- **OpenAPI file:** http://localhost:8080/openapi.yaml
- **Health:** http://localhost:8080/up

The Nginx container binds to `127.0.0.1:8080` for local development; this is **not a production deployment configuration**.

## Try it

Register an account using PowerShell:

```powershell
$body = @{
    name = 'Demo User'
    email = 'demo@example.test'
    password = 'correct-battery-horse-staple'
    password_confirmation = 'correct-battery-horse-staple'
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri 'http://localhost:8080/api/v1/register' -Method Post -ContentType 'application/json' -Body $body
$token = $response.token

Invoke-RestMethod -Uri 'http://localhost:8080/api/v1/tickets' -Method Post -ContentType 'application/json' -Headers @{ Authorization = "Bearer $token" } -Body (@{ title = 'VPN connectivity issue'; priority = 'high' } | ConvertTo-Json)
```

New accounts always have the `viewer` role. To bootstrap **your own** admin in a trusted development terminal:

```bash
docker compose exec app php artisan ops:promote-admin demo@example.test
```

Promoting an account revokes its previous tokens. **Log in again** to obtain an admin bearer token.

Use Swagger's **Authorize** control with the bearer token to explore private endpoints. Do not screenshot or publish the token.

## Permissions

| Action | Viewer | Operator | Admin |
| --- | :---: | :---: | :---: |
| Register / login | ✓ | ✓ | ✓ |
| Create ticket | ✓ | ✓ | ✓ |
| Read tickets | Own | All | All |
| Update status / priority | — | ✓ | ✓ |
| Assign a ticket | — | — | ✓ |
| Delete ticket | — | — | ✓ |
| List users / change roles | — | — | ✓ |

**Security notes:** The server sets ticket ownership and initial status. A caller cannot elevate their role during registration. Admins cannot change their own roles via the API. Role changes revoke existing access tokens. Authentication endpoints have rate limiting. Sanitized examples only; never commit `.env`. This is a demonstration architecture, not a security-audited production service.

## Endpoints

| Method | Route | Purpose |
| --- | --- | --- |
| POST | `/api/v1/register` | Create a viewer |
| POST | `/api/v1/login` | Issue bearer token |
| GET | `/api/v1/me` | Authenticated account |
| POST | `/api/v1/logout` | Revoke current token |
| GET | `/api/v1/tickets` | Paginated ticket list |
| POST | `/api/v1/tickets` | Create ticket |
| GET | `/api/v1/tickets/{id}` | Read ticket |
| PATCH | `/api/v1/tickets/{id}` | Update ticket (staff) |
| DELETE | `/api/v1/tickets/{id}` | Delete ticket (admin) |
| GET | `/api/v1/users` | List accounts (admin) |
| PATCH | `/api/v1/users/{id}/role` | Change role (admin) |

## Tests and CI

```bash
docker compose exec app composer test
```

The feature suite covers registration, wrong passwords, token revocation, private routes, ticket ownership, staff-only updates, admin-only deletion and role changes. The GitHub Actions workflow installs dependencies and runs the suite on every push or pull request to `main`.

**Dependency reproducibility:** `composer.lock` is not bundled with this starter archive. Generate it with `composer update` or the first `composer install`, then commit it to Git before using this as a reproducible deployment. The Docker build resolves dependency versions until the lockfile is committed.

## Structure

```text
app/
  Http/Controllers/Api/     # Route handlers
  Http/Middleware/          # Staff role guard
  Http/Resources/           # JSON ticket presentation
  Models/                   # User, Ticket
  Policies/                 # Ticket authorization
bootstrap/                  # Laravel application bootstrap
config/                     # Runtime configuration
database/migrations/        # PostgreSQL schema
docker/nginx.conf           # Nginx frontend
public/docs/                # Swagger UI page
public/openapi.yaml         # Machine-readable API contract
routes/api.php              # Versioned REST endpoints
tests/Feature/              # Authentication and access tests
scripts/                    # Secure local setup
```

## Next improvements

- Ticket status transition audit trail and event log
- Filtering by status/priority and full-text search
- Request IDs and structured telemetry
- Pagination and query tuning under load
- Optional queue-based notifications and dashboards

---

**Maintainer:** [hirques](https://github.com/hirques) · Educational/portfolio use · MIT
