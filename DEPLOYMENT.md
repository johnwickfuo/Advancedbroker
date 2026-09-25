# HestiaCP / Ubuntu deployment

1. Create the domain in HestiaCP and use PHP 8.2 or newer. Point the web root to the repository's `public/` directory. If Hestia requires `public_html`, keep the application one directory above it and expose only the contents of `public/` through a symlink or Hestia document-root setting.
2. Upload or clone the repository outside the public root. Never expose `.env`, `app`, `config`, `database`, `resources`, `tests`, `storage/private`, or reports through the web server.
3. Copy `.env.example` to `.env`; set `APP_ENV=production`, `APP_DEBUG=false`, a unique high-entropy `APP_KEY`, HTTPS URL, database credentials, mail configuration, trusted proxy configuration, GeoIP configuration if used, and one-time Super Admin bootstrap values. Do not reuse example credentials. Remove `SUPER_ADMIN_PASSWORD` after the initial seeded account is confirmed.
4. Create a MySQL 8 database and least-privilege application user. Back up the database before every migration. Run `composer install --no-dev --optimize-autoloader`, `php bin/migrate.php`, then only the documented safe reference seeder (`php bin/seed.php`). Do not run demo data seeders in production.
5. Make only `storage/cache`, `storage/logs`, `storage/private`, and `storage/uploads` writable by the web-service group. Use least privilege (for example directories `0750`, files `0640`); do not use `chmod 777`.
6. Enable HTTPS with Hestia/Let’s Encrypt. Set `SESSION_SECURE_COOKIE=true`. Confirm the reverse proxy supplies `X-Forwarded-Proto` only from a trusted proxy.
7. Enable rewrites. Apache uses the supplied `public/.htaccess`. Nginx equivalent: `try_files $uri $uri/ /index.php?$query_string;`. Static assets may be served directly; all application routes go through `public/index.php`.
8. Ensure PHP extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `json`; add `gd` for local TOTP QR generation and `intl` for enhanced locale support. `curl` is needed only if enabled dependencies require it.
9. Configure mail credentials in `.env` only. Send a controlled test email, then inspect the delivery log without exposing sensitive content.
10. Add Hestia cron (adjust path): `*/10 * * * * /usr/bin/php /home/USER/web/DOMAIN/app/bin/process-maturities.php >> /home/USER/logs/upgradedbroker-maturity.log 2>&1`. The job is row-lock/idempotency protected; do not run overlapping copies intentionally. Run reconciliation periodically: `php /home/USER/web/DOMAIN/app/bin/reconcile-wallets.php`.
11. Verify `/health` or the health command if enabled, Global guest fallback, login, a non-financial page, writable storage, a safe mail configuration, and cron invocation before accepting real money.

## Backups and monitoring

Back up the MySQL database, `storage/private` (KYC and receipts), public uploads/licences, and the encrypted `.env` backup off-server daily with retention appropriate to policy. Never store backups under the public root. Test a database-plus-private-file restore regularly. Monitor disk capacity, PHP/application logs, failed cron output, reconciliation failures, and expiring licences. Configure log rotation through the host OS/Hestia so logs cannot exhaust disk.

## Production acceptance

Run migrations in a staging clone first, perform the full financial and authorization test suite with MySQL, set `APP_DEBUG=false`, and verify that no stack trace or private file is web-accessible. This source checkout has not performed those runtime checks because the supplied workspace has no PHP executable, MySQL service, or browser renderer.


## Guest location and browser market locking

- An unsigned browser does not persist a country. Its market is resolved again from the current request IP on every request, so a VPN/IP change can change the public market immediately.
- After successful signup/login, a signed HttpOnly market cookie is created for that browser. The account country remains authoritative and later IP/VPN changes do not move that browser to a different market. Logout intentionally leaves this market cookie in place.
- `GEOIP_DRIVER=auto` resolves in this order: trusted Cloudflare `CF-IPCountry`, local MaxMind (when configured), then the HTTPS ipwho.is fallback. Successful remote lookups are cached by IP, so a new IP is resolved immediately while repeat requests from the same IP do not consume one API request each.
- The free ipwho.is endpoint permits commercial use but has a 1,000-request/day limit. For sustained production traffic, prefer Cloudflare IP Geolocation or a local/licensed MaxMind database and disable the remote fallback if appropriate.


## Offline translation

Public and authentication pages use locally installed Argos Translate models. No Google API key, browser translation widget, remote translation API, or visible translation bar is required at runtime.

Install once on the server:

```bash
python3 -m venv /home/admin/argos-venv
/home/admin/argos-venv/bin/pip install --upgrade pip argostranslate
/home/admin/argos-venv/bin/python bin/install-translation-models.py
```

Then set `TRANSLATION_PYTHON=/home/admin/argos-venv/bin/python` and `OFFLINE_TRANSLATION_ENABLED=true`. Rendered text fragments are cached under `storage/cache` after their first local translation.

## HTTPS and sessions

Production `APP_URL` should use HTTPS and `SESSION_SECURE_COOKIE=true`. The application redirects plain HTTP traffic to the configured HTTPS origin before starting a session. This prevents login/register CSRF failures caused by Secure cookies being unavailable over HTTP.
