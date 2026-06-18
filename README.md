# ISO-Forge

ISO-Forge is a Laravel prototype for a developer-centric ISO compliance framework. The current build follows the local project documents: multi-tenant data isolation, RBAC, document control, workflow tasks, ISO 9001 risk/CAPA records, electronic signatures, and a hash-chained audit ledger.

## Stack

- Laravel 13
- PHP 8.3+
- SQLite for local development
- MySQL-ready migrations
- Laravel Sanctum API tokens
- Tailwind CSS 4 with Vite

## Local Setup

```bash
composer install
npm install
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`.

## Demo Account

```text
Email: jojo@iso-forge.test
Password: password
Tenant: angkor-quality-foods
```

## Implemented Foundation

- Tenant-aware users and RBAC roles/permissions
- Document metadata, version history, approval queue, and electronic signature records
- Generic workflow definitions, instances, and user tasks
- ISO 9001 risk register, nonconformance records, and corrective actions
- SHA-256 audit log chaining with `previous_hash`, `payload_hash`, and `entry_hash`
- Dashboard for documents, approvals, risks, CAPA, tasks, and audit ledger
- Sanctum-protected API routes with tenant access checks

## Phase 2 Backend

- Reusable tenant-access and permission middleware
- Document creation, approval request, and approval/signature API actions
- Risk register create/update API actions with automatic score calculation
- Nonconformance and CAPA create/update API actions
- Workflow task completion for assigned users or CAPA closers
- Write-path audit events for documents, risks, CAPA, nonconformances, and workflow tasks
- Tests for RBAC denial, tenant isolation, document approvals, signatures, CAPA workflow, and audit hash-chain extension

## API

Authenticate:

```bash
curl -X POST http://127.0.0.1:8000/api/auth/login ^
  -H "Content-Type: application/json" ^
  -d "{\"email\":\"jojo@iso-forge.test\",\"password\":\"password\"}"
```

Use the returned token:

```bash
curl http://127.0.0.1:8000/api/tenants/angkor-quality-foods/snapshot ^
  -H "Authorization: Bearer YOUR_TOKEN"
```

Available tenant routes:

- `GET /api/tenants/{tenant:slug}/snapshot`
- `GET /api/tenants/{tenant:slug}/documents`
- `POST /api/tenants/{tenant:slug}/documents`
- `POST /api/tenants/{tenant:slug}/documents/{document}/approvals`
- `POST /api/tenants/{tenant:slug}/document-approvals/{documentApproval}/approve`
- `GET /api/tenants/{tenant:slug}/risks`
- `POST /api/tenants/{tenant:slug}/risks`
- `PATCH /api/tenants/{tenant:slug}/risks/{risk}`
- `GET /api/tenants/{tenant:slug}/corrective-actions`
- `POST /api/tenants/{tenant:slug}/non-conformances`
- `POST /api/tenants/{tenant:slug}/corrective-actions`
- `PATCH /api/tenants/{tenant:slug}/corrective-actions/{correctiveAction}`
- `POST /api/tenants/{tenant:slug}/workflow-tasks/{workflowTask}/complete`
- `GET /api/tenants/{tenant:slug}/audit-logs`
- `POST /api/tenants/{tenant:slug}/audit-logs`

## Quality Checks

```bash
php artisan test
vendor/bin/pint
npm audit
npm run build
```

Current status: 11 tests passing and npm audit clean.

## Next Development Targets

- Add browser login/logout screens on top of Sanctum-backed accounts
- Add CRUD screens for documents, risks, CAPA, and workflow tasks
- Add file upload/storage for controlled document versions
- Add request classes/resources for stricter API contracts
- Expand ISO 22000 HACCP, CCP, OPRP, and monitoring-record modules
