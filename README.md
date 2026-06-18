# ISO-Forge

ISO-Forge is a Laravel prototype for a developer-centric ISO compliance framework. The current build follows the local project documents: multi-tenant data isolation, RBAC, document control, workflow tasks, ISO 9001 risk/CAPA records, ISO 22000 HACCP controls, supplier quality evidence, training competency evidence, incident response and emergency drill evidence, operational trend analytics, management review evidence packets, electronic signatures, and a hash-chained audit ledger.

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
- ISO 22000 HACCP plans, hazard analysis, CCP/OPRP controls, PRPs, and monitoring records
- Supplier approvals, evaluations, certificates, equipment assets, and calibration records
- Training programs, competency requirements, assignments, completion records, and awareness acknowledgements
- Incident reports, containment actions, emergency response plans, and emergency drill records
- Trend analytics for incidents, CAPA ageing, training competency, and supplier risk
- Exportable management review packets across QMS, training, incident, supplier, CAPA, and audit evidence
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

## Phase 5 Testing And Refinement

- Audit-log payload snapshots for stronger ledger verification
- `php artisan iso-forge:verify-audit-chain` command
- Regression tests for unauthenticated access, cross-tenant validation, audit-chain verification, and tamper detection
- Expanded test suite across backend APIs, frontend workspace route, QMS module, workflow actions, and audit integrity

## Phase 6 Documentation And Deployment

- GitHub Actions CI workflow template for tests, formatting, npm audit, and frontend build
- Deployment preparation guide in `docs/deployment-preparation.md`
- API reference in `docs/api-reference.md`
- Release checklist in `docs/release-checklist.md`

## Phase 7 ISO 22000 FSMS

- HACCP plans with process steps and hazard analysis records
- Automatic food-safety risk scoring from likelihood and severity
- CCP and OPRP controls with monitoring frequencies and responsible users
- Prerequisite programs for sanitation, supplier, and facility controls
- Monitoring records for CCP/OPRP checks, with deviation records opening CAPA automatically
- FSMS browser tab for HACCP plans, CCPs, hazard analysis, PRPs, and monitoring records
- FSMS API actions protected by `fsms.view` and `fsms.manage` permissions

## Phase 8 Supplier Quality And Calibration

- Approved supplier list with owner, category, status, risk level, and linked risk records
- Supplier evaluations with score-based risk updates and linked evidence documents
- Supplier certificates with expiry status tracking
- Equipment asset register for food-safety critical devices
- Calibration records linked to evidence documents, equipment status, and CAPA when checks fail
- Supplier Quality browser tab for suppliers, evaluations, certificates, equipment, and calibration records
- Supplier Quality API actions protected by `supplier.view` and `supplier.manage` permissions

## Phase 9 Training And Competency

- Training programs with ISO clause, delivery method, owner, and refresher interval
- Role-based competency requirements linked to QMS/FSMS responsibilities
- Training assignments with due dates and required role context
- Training records with trainer, evidence document, score, result, and competency status
- Failed competency checks automatically open CAPA for coaching and verification
- Awareness acknowledgements linked to controlled documents and users
- Training browser tab for programs, requirements, assignments, completion records, and awareness evidence
- Training API actions protected by `training.view` and `training.manage` permissions

## Phase 10 Incident Response And Emergency Preparedness

- Incident reports linked to FSMS controls through CCP, OPRP, or PRP source references
- Incident containment/action records with responsible users, due dates, completion status, and audit events
- Major and critical incident reports automatically open CAPA for verification
- Emergency response plans with scenario, owner, review cadence, optional document link, and response steps
- Emergency drills with participants, effectiveness score, result, and CAPA creation for poor outcomes
- Incidents browser tab for reports, actions, emergency plans, and drill evidence
- Incident Response API actions protected by `incident.view` and `incident.manage` permissions

## Phase 11 Trend Analytics

- Read-only Analytics API for tenant-level trend summaries
- Incident distributions by status and severity with recent incident evidence
- CAPA ageing buckets for overdue, next 7 days, next 30 days, future, and missing due dates
- Training competency summaries for assignment status, completion result, competency status, pass rate, and expiring records
- Supplier risk summaries for risk level, approval status, expiring certificates, due calibrations, and calibration failures
- Analytics browser tab with compact summary metrics and four trend panels
- Analytics API action protected by `analytics.view` permission

## Phase 12 Management Review Evidence Packets

- Management review packet index for seeded and created QMS review records
- Exportable JSON packet detail with packet id, generated timestamp, tenant metadata, review inputs, decisions, actions, and evidence summaries
- Evidence packet sections for QMS objectives/audits/findings, training programs/records, incident reports/drills, supplier evidence, open CAPA, and audit chain events
- Deterministic packet hash for exported JSON evidence integrity
- Packet download endpoint with JSON attachment headers
- Packets browser tab with summary metrics, preview, and download actions
- Packet API actions protected by `review_packet.view` permission

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
- `GET /api/tenants/{tenant:slug}/analytics`
- `GET /api/tenants/{tenant:slug}/management-review-packets`
- `GET /api/tenants/{tenant:slug}/management-review-packets/{managementReview}`
- `GET /api/tenants/{tenant:slug}/management-review-packets/{managementReview}/download`
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
- `GET /api/tenants/{tenant:slug}/fsms`
- `POST /api/tenants/{tenant:slug}/fsms/haccp-plans`
- `POST /api/tenants/{tenant:slug}/fsms/haccp-plans/{haccpPlan}/steps`
- `POST /api/tenants/{tenant:slug}/fsms/process-steps/{processStep}/hazards`
- `POST /api/tenants/{tenant:slug}/fsms/hazards/{hazardAnalysis}/ccps`
- `POST /api/tenants/{tenant:slug}/fsms/hazards/{hazardAnalysis}/oprps`
- `POST /api/tenants/{tenant:slug}/fsms/prps`
- `POST /api/tenants/{tenant:slug}/fsms/monitoring-records`
- `GET /api/tenants/{tenant:slug}/supplier-quality`
- `POST /api/tenants/{tenant:slug}/supplier-quality/suppliers`
- `POST /api/tenants/{tenant:slug}/supplier-quality/suppliers/{supplier}/evaluations`
- `POST /api/tenants/{tenant:slug}/supplier-quality/suppliers/{supplier}/certificates`
- `POST /api/tenants/{tenant:slug}/supplier-quality/equipment`
- `POST /api/tenants/{tenant:slug}/supplier-quality/equipment/{equipmentAsset}/calibrations`
- `GET /api/tenants/{tenant:slug}/training`
- `POST /api/tenants/{tenant:slug}/training/programs`
- `POST /api/tenants/{tenant:slug}/training/requirements`
- `POST /api/tenants/{tenant:slug}/training/programs/{trainingProgram}/assignments`
- `POST /api/tenants/{tenant:slug}/training/assignments/{trainingAssignment}/records`
- `POST /api/tenants/{tenant:slug}/training/awareness-acknowledgements`
- `GET /api/tenants/{tenant:slug}/incident-response`
- `POST /api/tenants/{tenant:slug}/incident-response/reports`
- `POST /api/tenants/{tenant:slug}/incident-response/reports/{incidentReport}/actions`
- `PATCH /api/tenants/{tenant:slug}/incident-response/actions/{incidentAction}`
- `POST /api/tenants/{tenant:slug}/incident-response/emergency-plans`
- `POST /api/tenants/{tenant:slug}/incident-response/emergency-plans/{emergencyResponsePlan}/drills`
- `GET /api/tenants/{tenant:slug}/audit-logs`
- `POST /api/tenants/{tenant:slug}/audit-logs`

## Quality Checks

```bash
php artisan test
vendor/bin/pint
npm audit
npm run build
```

Current status: 43 tests passing and npm audit clean.

## Verification Command

```bash
php artisan iso-forge:verify-audit-chain
```

Expected result:

```text
Audit chain valid. Checked 26 entries; legacy entries: 0.
```

## Next Development Targets

- Add file upload/storage for controlled document versions
- Add request classes/resources for stricter API contracts
- Add edit screens and validation summaries for Phase 3 forms
- Add PDF rendering for management review packets
