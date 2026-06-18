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

Open `http://127.0.0.1:8000/app` for the Phase 3 browser workspace.

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

## Phase 3 Frontend

- Authenticated browser workspace at `/app`
- Token login/logout against Sanctum API endpoints
- API-backed overview metrics, approvals, workflow tasks, documents, risks, CAPA, and audit ledger
- Create forms for controlled documents, risks, nonconformances, and CAPA records
- Inline actions for document approval and workflow task completion
- Shared user selectors populated from tenant API data

## Phase 4 ISO 9001 QMS

- Quality objectives with targets, current values, owners, and measurement methods
- Internal audit program records with lead auditor, scope, schedule, findings, and status
- Audit findings linked to ISO clauses and optional nonconformance records
- Management review records with structured inputs, decisions, and actions
- QMS browser tab for objectives, audits, findings, and management reviews
- QMS API actions protected by `qms.view` and `qms.manage` permissions

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
- `GET /api/tenants/{tenant:slug}/users`
- `GET /api/tenants/{tenant:slug}/documents`
- `POST /api/tenants/{tenant:slug}/documents`
- `GET /api/tenants/{tenant:slug}/document-approvals`
- `POST /api/tenants/{tenant:slug}/documents/{document}/approvals`
- `POST /api/tenants/{tenant:slug}/document-approvals/{documentApproval}/approve`
- `GET /api/tenants/{tenant:slug}/risks`
- `POST /api/tenants/{tenant:slug}/risks`
- `PATCH /api/tenants/{tenant:slug}/risks/{risk}`
- `GET /api/tenants/{tenant:slug}/corrective-actions`
- `POST /api/tenants/{tenant:slug}/non-conformances`
- `POST /api/tenants/{tenant:slug}/corrective-actions`
- `PATCH /api/tenants/{tenant:slug}/corrective-actions/{correctiveAction}`
- `GET /api/tenants/{tenant:slug}/workflow-tasks`
- `POST /api/tenants/{tenant:slug}/workflow-tasks/{workflowTask}/complete`
- `GET /api/tenants/{tenant:slug}/qms`
- `POST /api/tenants/{tenant:slug}/qms/objectives`
- `PATCH /api/tenants/{tenant:slug}/qms/objectives/{qualityObjective}`
- `POST /api/tenants/{tenant:slug}/qms/audits`
- `PATCH /api/tenants/{tenant:slug}/qms/audits/{audit}`
- `POST /api/tenants/{tenant:slug}/qms/audits/{audit}/findings`
- `POST /api/tenants/{tenant:slug}/qms/management-reviews`
- `GET /api/tenants/{tenant:slug}/audit-logs`
- `POST /api/tenants/{tenant:slug}/audit-logs`

## Quality Checks

```bash
php artisan test
vendor/bin/pint
npm audit
npm run build
```

Current status: 16 tests passing and npm audit clean.

## Next Development Targets

- Add file upload/storage for controlled document versions
- Add request classes/resources for stricter API contracts
- Add edit screens and validation summaries for Phase 3 forms
- Phase 5 testing and refinement: broaden regression coverage, validation boundaries, and release-readiness checks
- Expand ISO 22000 HACCP, CCP, OPRP, and monitoring-record modules
