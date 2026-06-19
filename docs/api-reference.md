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
- `GET /analytics`
- `GET /management-review-packets`
- `GET /management-review-packets/{managementReview}`
- `GET /management-review-packets/{managementReview}/download`
- `GET /management-review-packets/{managementReview}/pdf`
- `GET /audit-logs`
- `POST /audit-logs`

### Document Control

- `GET /documents`
- `POST /documents`
- `PATCH /documents/{document}`
- `POST /documents/{document}/versions`
- `GET /documents/{document}/versions/{documentVersion}/download`
- `PATCH /documents/{document}/versions/{documentVersion}/superseded-review`
- `POST /documents/{document}/versions/{documentVersion}/prune`
- `GET /document-approvals`
- `POST /documents/{document}/approvals`
- `POST /document-approvals/{documentApproval}/approve`

`POST /documents` and `POST /documents/{document}/versions` accept either JSON file references with `file_path`, `mime_type`, and `file_size`, or multipart uploads with a `file` field. The API stores uploaded versions on the local disk and returns `current_version.is_stored` so clients can decide whether the protected download endpoint is available.

Document create/update/version and approval actions are validated through FormRequest classes. Validation failures return Laravel's standard `422` response with an `errors` object keyed by field name.

Document versions expose retention and supersession fields: `retention_until`, `superseded_at`, `superseded_by_id`, `superseded_reviewed_at`, `superseded_reviewed_by_id`, `superseded_review_notes`, `pruned_at`, `pruned_by_id`, and `prune_reason`. Superseded versions can be reviewed with optional `notes`, and non-current versions can be pruned once `retention_until` is not in the future.

Management review packet downloads are available as JSON through `/download` and as a generated PDF through `/pdf`. PDF packets include paginated section tables and signature blocks.

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
- `PATCH /fsms/haccp-plans/{haccpPlan}`
- `POST /fsms/haccp-plans/{haccpPlan}/steps`
- `POST /fsms/process-steps/{processStep}/hazards`
- `POST /fsms/hazards/{hazardAnalysis}/ccps`
- `POST /fsms/hazards/{hazardAnalysis}/oprps`
- `POST /fsms/prps`
- `POST /fsms/monitoring-records`

### Supplier Quality And Calibration

- `GET /supplier-quality`
- `POST /supplier-quality/suppliers`
- `PATCH /supplier-quality/suppliers/{supplier}`
- `POST /supplier-quality/suppliers/{supplier}/evaluations`
- `POST /supplier-quality/suppliers/{supplier}/certificates`
- `POST /supplier-quality/equipment`
- `PATCH /supplier-quality/equipment/{equipmentAsset}`
- `POST /supplier-quality/equipment/{equipmentAsset}/calibrations`

### Training And Competency

- `GET /training`
- `POST /training/programs`
- `PATCH /training/programs/{trainingProgram}`
- `POST /training/requirements`
- `POST /training/programs/{trainingProgram}/assignments`
- `PATCH /training/assignments/{trainingAssignment}`
- `POST /training/assignments/{trainingAssignment}/records`
- `POST /training/awareness-acknowledgements`

### Incident Response And Emergency Preparedness

- `GET /incident-response`
- `POST /incident-response/reports`
- `PATCH /incident-response/reports/{incidentReport}`
- `POST /incident-response/reports/{incidentReport}/actions`
- `PATCH /incident-response/actions/{incidentAction}`
- `POST /incident-response/emergency-plans`
- `PATCH /incident-response/emergency-plans/{emergencyResponsePlan}`
- `POST /incident-response/emergency-plans/{emergencyResponsePlan}/drills`

## Authorization

- Tenant middleware rejects cross-tenant access.
- `document.*` permissions control document actions.
- `risk.manage` controls risk register actions.
- `capa.create` and `capa.close` control CAPA actions.
- `qms.view` and `qms.manage` control ISO 9001 module actions.
- `fsms.view` and `fsms.manage` control ISO 22000 module actions.
- `supplier.view` and `supplier.manage` control supplier quality and calibration actions.
- `training.view` and `training.manage` control training, competency, and awareness actions.
- `incident.view` and `incident.manage` control incident response and emergency preparedness actions.
- `analytics.view` controls trend analytics reads.
- `review_packet.view` controls management review packet reads, JSON downloads, and PDF downloads.
- `audit.view` controls audit-ledger reads and manual audit entries.
