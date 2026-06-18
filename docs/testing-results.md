# ISO-Forge Testing Results

## Test Run

- Date: 2026-06-18 08:50:55 +07:00
- Branch: `main`
- Tested commit: `e255f40`
- Scope: Phase 2 core backend APIs, RBAC middleware, tenant isolation, audit logging, and build verification

## Summary

All verification checks passed.

| Check | Command | Result |
| --- | --- | --- |
| PHP feature/unit tests | `php artisan test` | Passed: 11 tests, 42 assertions |
| PHP formatting | `vendor/bin/pint --test` | Passed |
| npm security audit | `npm audit --audit-level=critical` | Passed: 0 vulnerabilities |
| Frontend production build | `npm run build` | Passed |
| Live authenticated API smoke test | Login then `GET /api/tenants/angkor-quality-foods/snapshot` | Passed |

## PHPUnit Result

```text
Result: passed
Tests: 11
Passed: 11
Assertions: 42
Duration: 605 ms
```

Covered behavior includes:

- Dashboard renders seeded ISO-Forge data.
- Sanctum login returns a token and allows tenant snapshot access.
- Cross-tenant API access is rejected.
- Documents can be created and approved.
- Document approval creates an electronic signature and audit event.
- Risk creation requires `risk.manage` permission.
- Risk scores and residual scores are calculated automatically.
- CAPA creation starts a workflow task.
- Assigned workflow users can complete their tasks.
- Assigned verifier can complete verification without a CAPA-manager role.
- Audit hash chain extends correctly after a Phase 2 write.

## Live API Smoke Test

Command flow:

```powershell
$loginBody = @{ email = 'jojo@iso-forge.test'; password = 'password' } | ConvertTo-Json
$login = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/auth/login -Method Post -ContentType 'application/json' -Body $loginBody
$snapshot = Invoke-RestMethod -Uri http://127.0.0.1:8000/api/tenants/angkor-quality-foods/snapshot -Headers @{ Authorization = "Bearer $($login.token)" }
```

Result:

```text
tenant=angkor-quality-foods; documents=2; open_capas=1; audit_events=4
```

## Build Result

`npm run build` completed successfully with Vite 8.0.16. Production assets were generated under `public/build`, which remains ignored by Git.

## Security Notes

- `.env` is ignored.
- `vendor` and `node_modules` are ignored.
- `database/database.sqlite` is ignored.
- Critical npm audit found 0 vulnerabilities.
