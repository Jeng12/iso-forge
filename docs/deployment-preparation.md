# ISO-Forge Deployment Preparation

## Purpose

This document prepares ISO-Forge for a simple Laravel deployment after Phase 6. It covers local release verification, environment variables, database setup, asset build, and post-deploy checks.

## Required Runtime

- PHP 8.3 or newer
- Composer 2
- Node 24 or compatible current LTS
- SQLite for local/demo deployment or MySQL for shared environments
- Web server pointed at Laravel `public/`

## Environment

Create `.env` from `.env.example` and set:

```text
APP_NAME=ISO-Forge
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=iso_forge
DB_USERNAME=iso_forge
DB_PASSWORD=secure-password
```

For a local SQLite demo:

```text
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

## Release Commands

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan key:generate --force
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Use seed data only for demos:

```bash
php artisan db:seed --force
```

## Verification Commands

```bash
php artisan test
vendor/bin/pint --test
npm audit --audit-level=critical
npm run build
php artisan iso-forge:verify-audit-chain
```

## Post-Deploy Smoke Checks

- `GET /` returns the server-rendered dashboard.
- `GET /app` returns the browser workspace shell.
- `POST /api/auth/login` accepts a valid account.
- `GET /api/tenants/{tenant}/snapshot` returns tenant metrics with a bearer token.
- `php artisan iso-forge:verify-audit-chain` exits successfully.

## Security Checklist

- Keep `.env` out of Git.
- Run with `APP_DEBUG=false` outside local development.
- Use HTTPS in production.
- Rotate demo passwords before real use.
- Restrict database user permissions to the application database.
- Keep `vendor`, `node_modules`, `public/build`, and local SQLite files ignored.
