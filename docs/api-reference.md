# ISO-Forge API Reference

All protected endpoints use Laravel Sanctum bearer tokens.

## Authentication

### Login

`POST /api/auth/login`

```json
{
  "email": "jojo@iso-forge.test",
  "password": "password"
}
```

Returns a bearer token and user profile.

### Current User

`GET /api/user`

Returns the authenticated user with tenant, roles, and permissions.

## Tenant Workspace

All tenant routes use:

`/api/tenants/{tenant:slug}`

### Core

- `GET /snapshot`
- `GET /users`
- `GET /audit-logs`
- `POST /audit-logs`

### Document Control

- `GET /documents`
- `POST /documents`
- `GET /document-approvals`
- `POST /documents/{document}/approvals`
- `POST /document-approvals/{documentApproval}/approve`

### Risk And CAPA

- `GET /risks`
- `POST /risks`
- `PATCH /risks/{risk}`
- `GET /corrective-actions`
- `POST /non-conformances`
- `POST /corrective-actions`
- `PATCH /corrective-actions/{correctiveAction}`
- `GET /workflow-tasks`
- `POST /workflow-tasks/{workflowTask}/complete`

### ISO 9001 QMS

- `GET /qms`
- `POST /qms/objectives`
- `PATCH /qms/objectives/{qualityObjective}`
- `POST /qms/audits`
- `PATCH /qms/audits/{audit}`
- `POST /qms/audits/{audit}/findings`
- `POST /qms/management-reviews`

### ISO 22000 FSMS

- `GET /fsms`
- `POST /fsms/haccp-plans`
- `POST /fsms/haccp-plans/{haccpPlan}/steps`
- `POST /fsms/process-steps/{processStep}/hazards`
- `POST /fsms/hazards/{hazardAnalysis}/ccps`
- `POST /fsms/hazards/{hazardAnalysis}/oprps`
- `POST /fsms/prps`
- `POST /fsms/monitoring-records`

### Supplier Quality And Calibration

- `GET /supplier-quality`
- `POST /supplier-quality/suppliers`
- `POST /supplier-quality/suppliers/{supplier}/evaluations`
- `POST /supplier-quality/suppliers/{supplier}/certificates`
- `POST /supplier-quality/equipment`
- `POST /supplier-quality/equipment/{equipmentAsset}/calibrations`

### Training And Competency

- `GET /training`
- `POST /training/programs`
- `POST /training/requirements`
- `POST /training/programs/{trainingProgram}/assignments`
- `POST /training/assignments/{trainingAssignment}/records`
- `POST /training/awareness-acknowledgements`

## Authorization

- Tenant middleware rejects cross-tenant access.
- `document.*` permissions control document actions.
- `risk.manage` controls risk register actions.
- `capa.create` and `capa.close` control CAPA actions.
- `qms.view` and `qms.manage` control ISO 9001 module actions.
- `fsms.view` and `fsms.manage` control ISO 22000 module actions.
- `supplier.view` and `supplier.manage` control supplier quality and calibration actions.
- `training.view` and `training.manage` control training, competency, and awareness actions.
- `audit.view` controls audit-ledger reads and manual audit entries.
