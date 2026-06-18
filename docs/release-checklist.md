# ISO-Forge Release Checklist

Use this checklist before tagging or deploying a release.

## Code Quality

- [ ] `vendor/bin/pint --test`
- [ ] `php artisan test`
- [ ] `npm audit --audit-level=critical`
- [ ] `npm run build`

## Database

- [ ] `php artisan migrate --force`
- [ ] `php artisan iso-forge:verify-audit-chain`
- [ ] Confirm production seed/demo accounts are disabled or rotated.

## Application

- [ ] `/` dashboard loads.
- [ ] `/app` workspace loads.
- [ ] Login works with a real tenant user.
- [ ] Tenant snapshot endpoint returns metrics.
- [ ] Document approval creates an electronic signature.
- [ ] QMS objective and audit records can be created by a `qms.manage` user.
- [ ] Supplier quality and calibration records can be created by a `supplier.manage` user.

## Security

- [ ] `.env` is not committed.
- [ ] `APP_DEBUG=false`.
- [ ] HTTPS is enabled.
- [ ] Database credentials are least-privilege.
- [ ] Demo password `password` is not used in production.
