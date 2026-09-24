# Upgraded Broker

Plain PHP 8.2+ foundation for a multi-country investment platform. It uses PDO, prepared statements and a single public web root.

## Requirements and setup

- PHP 8.2+ with `pdo_mysql`, `mbstring`, `fileinfo`, `json`, `openssl`, and `gd` (for locally generated authenticator QR codes); `intl` is optional for future enhanced locale formatting.
- MySQL 8+ and Composer 2 (recommended for optimized PSR-4 autoloading).
- Copy `.env.example` to `.env`, use a strong random `APP_KEY`, configure MySQL, set all four `SUPER_ADMIN_*` values, then run `composer dump-autoload -o`, `php bin/migrate.php`, `php bin/seed.php`, and `php tests/run.php`.
- Local development only: `php -S localhost:8000 -t public`.

## Country Pack architecture

One application serves all Country Packs concurrently. Normal URLs remain clean (`/`, `/companies`, `/about`); the server resolves the active Country Context per request rather than adding a country prefix or duplicating business logic.

The seeded set is 32 national Country Packs plus **Global**: UK, Germany, France, Italy, Spain, Netherlands, Switzerland, Sweden, Norway, Denmark, US, Canada, Brazil, Mexico, Japan, South Korea, Singapore, Hong Kong, India, Australia, New Zealand, South Africa, UAE, Saudi Arabia, Poland, Austria, Belgium, Ireland, Philippines, Trinidad and Tobago, Jamaica and Barbados.

Nigeria and Kenya are not supported packs. **Any country outside the supported active list automatically receives the Global website.** This includes Nigeria, Kenya, Ghana, Argentina, Egypt, Qatar and any failed/unknown GeoIP lookup. Disabled packs also resolve to Global for normal visitors.

For signed-in users, effective country priority is: Super Admin assignment (`assigned_country_id`), stored account country (`country_id`), then Global. GeoIP never overrides an authenticated account country. For guests, development preview (debug only), valid GeoIP, then Global is used.

## GeoIP

`GEOIP_DRIVER=auto` tries Cloudflare first and then optional MaxMind GeoLite2. Cloudflare country data is trusted only when `TRUST_CLOUDFLARE_COUNTRY_HEADER=true` **and** the request IP appears in `TRUSTED_PROXIES`; never trust a client-supplied `CF-IPCountry` header directly. MaxMind is optional: install `geoip2/geoip2` with Composer and point `GEOIP_DATABASE_PATH` at a GeoLite2 database file. No external GeoIP API is required.

When `APP_DEBUG=true`, append `?preview_country=de` (or a Country Pack slug) to preview a pack locally. This never operates in production and does not override an authenticated account assignment.

## Languages, currencies and themes

Country and language are deliberately separate. Changing language changes UI text only; country-controlled currency, availability, compliance and legal rules remain unchanged. The language picker uses a CSRF-protected session/account preference and only accepts languages linked to the effective Country Pack. Translation files live in `resources/lang/<code>/messages.php`, with fallback: selected language → country default → English.

Currency remains exact in minor units. Country currency symbols and positions are configuration data and are only used for display. Country theme JSON is validated against colour/font/radius allowlists before it becomes CSS custom properties. Local country content, visual asset metadata and themes are database-managed in `country_page_contents`, `country_assets`, and `countries`.

Country flags are saved under `public/assets/images/flags`; their attribution is in `public/assets/images/flags/SOURCES.md`. The Global pack uses an original neutral globe icon.

## Authentication and account security

Registration creates an `ACTIVE` user with the effective guest Country Pack: supported GeoIP assigns that pack; every unsupported or failed lookup assigns **Global**. A user’s selected supported interface language is stored separately from their country. Changing language never changes currency, Country Pack, investment availability, or jurisdiction. A Super Admin can later assign a different Country Pack; that assignment wins over all future IP location and invalidates the user’s active sessions.

Passwords use `PASSWORD_ARGON2ID` when available (otherwise PHP’s secure default), `password_verify`, and automatic rehashing. Password reset and email-verification links use one-time, expiring, hashed tokens. The forgot-password response is intentionally neutral. `MAIL_DRIVER=log` is recommended locally; development email content is recorded in structured logs. Production must configure a functioning mail transport (`mail` is built in; a focused SMTP adapter may be introduced if SMTP is required).

The singleton Super Admin is created only by the environment-driven seeder when no `super_admin` account exists. Never place its credentials in source control. The admin area requires server-side Super Admin middleware; normal users cannot reach it by hiding navigation alone.

Two-factor authentication is standard TOTP, compatible with Google Authenticator, Microsoft Authenticator, Authy, 1Password and similar applications. TOTP secrets are encrypted with authenticated AES-256-GCM derived from `APP_KEY`; recovery codes are random, hashed, one-time, and only shown in raw form once. The QR code is generated locally using `phpqrcode`/GD rather than an external URL.

Persistent “remember me” sign-in uses a selector/token cookie with only a token hash in MySQL. Device/session rows record a session hash, IP address, user-agent label, timestamps, expiry, and revocation state. Security and audit logs deliberately exclude passwords, TOTP secrets, recovery codes, and raw reset/remember tokens.

Account states are `ACTIVE`, `RESTRICTED`, and `SUSPENDED`. Users may reach profile/security settings as appropriate, but restricted and suspended users are blocked server-side from sensitive financial routes. The admin status change flow requires a reason for restrictions and suspensions.

Email verification is a global admin setting in **Admin → Security settings**. When enabled, sensitive account routes require verification; when disabled, an unverified user may continue. Verification emails can always be resent from Dashboard → Security.

## Super Admin, branding and public content

The one Super Admin has a server-protected control room for Country Packs, users, contact messages, audit history and platform settings. Country Pack identifiers remain stable; the manager permits only safe presentation/configuration changes. Disabling a national pack sends new guests to Global. It does not overwrite existing user country references: their country-specific actions are blocked with a clear service-unavailable message until an administrator reassigns them. Global is protected from being disabled because it is the mandatory fallback.

Branding uses inheritance: **Country override → Global Country Pack fallback → application default**. The same pattern applies to support contact details and saved visual configuration. Public images are uploaded as decoded image MIME types with randomized names and are served through a controlled media route; private licence documents are streamed with safe content headers. Do not place executable assets in upload directories.

Country content is stored per Country Pack, page and language. The normal fallback is requested language → Country Pack default language → English. About, How It Works, FAQ, Contact, Licence, Terms, Privacy and Risk Disclosure pages inherit the resolved country, language, theme and branding.

Legal documents are immutable versions. Publishing a replacement creates a new row; prior versions are retained so historic terms/privacy acceptance can continue to reference the version accepted. Legal HTML is sanitized to a small formatting allowlist; scripts, event handlers, embeds and `javascript:` URLs are removed.

Licences are never generated or seeded. Each Country Pack—including Global—has independent records and documents. The public licence page shows only administrator-uploaded actual documents. Licence status is calculated as `MISSING`, `ACTIVE`, `EXPIRING_SOON`, `EXPIRED`, `SUSPENDED`, or `NOT_REQUIRED`. `LicenseService::canInvest($country)` is ready for the future investment-purchase guard.

An authenticated Super Admin can start a session-scoped Country Pack preview, including a permitted preview language. It does not change the administrator’s account assignment or expose public country switching.

## Company catalogue and investment offerings

The catalogue deliberately separates **factual public-company data** from the **platform investment offering**. `companies` stores company identity, exchange/listing fields, factual profile, verification date and research sources. `investment_offerings` is separately scoped to a Country Pack and stores the administrator-controlled investment price, quantity rules, projection type, fixed-profit basis, duration and availability. An offering price is never labelled as a live market price.

Guests and signed-in users only receive the catalogue for their resolved Country Pack. Global is a distinct catalogue; it does not inherit United States or other national offers. Direct company requests are joined to the active Country Context server-side and return 404 when the offer belongs to another Country Pack.

Authoritative monetary values use integer minor units and quantity limits use MySQL `DECIMAL(20,8)`. The illustrative calculator has a server-side equivalent (`OfferingCalculator`) and explicitly handles percentage, fixed-per-share and fixed-per-investment projections. It does not create purchases or trust browser calculations. Price changes write `investment_price_history`; changes to material terms write `investment_offering_history`.

`php bin/seed.php` invokes an idempotent `CompanySeeder`. It intentionally contains only research-backed examples while the full country-by-country verification pass is outstanding; see `COMPANY_SEED_REPORT.md`. Never bulk-fill the catalogue with unverified issuers. `company_sources`, `company_images`, and `image_sources` retain evidence and asset-attribution metadata. Until a properly licensed local image is supplied, cards use a neutral CSS monogram rather than hotlinking or copying a logo.

The public routes are `/companies` and `/companies/{public-id}`. Super Admin company management lives at `/admin/companies`; archival is the safe default and keeps historical references intact. Company media/source editing is modelled in the schema and should be completed alongside the verified data import.

## Wallets, ledger and manual deposits

Wallet balances are materialized only for performance. `ledger_transactions` is the financial source of truth: its rows are append-only, use a positive minor-unit amount plus an explicit `CREDIT` or `DEBIT` direction, and record balances before and after the movement. Controllers must use `WalletService`; they must never edit a wallet balance directly.

Every wallet mutation runs in one MySQL transaction: lock the wallet with `FOR UPDATE`, validate the balance, insert the ledger entry, update the materialized balance, then commit. Normal debits cannot make an available balance negative. Reservations move value from available to reserved until released, ready for future investment and withdrawal workflows. Corrections must use a counter-entry/reversal, not an edit/delete of a completed ledger row.

Manual deposits are intentionally manual. A deposit request has no wallet effect until a Super Admin approves it. Approval locks the request, confirms it is still eligible, creates exactly one `DEPOSIT` ledger credit, updates the wallet, and links the ledger row back to the request. A repeated approval returns the existing linked result rather than crediting again. Deposit method fees are explicitly either deducted from the expected credit or added to the required payment; no automatic gateway is included.

Dynamic forms are reusable for deposits, withdrawals and KYC. Form field definitions and select options are server-owned; submissions must be validated against those definitions and private proof files must be served only after authorization. Deposit methods are assigned to Country Packs. A Global user sees Global methods, including an unsupported-origin user who resolved to Global—never a method inferred from their current IP.

Run `php bin/reconcile-wallets.php` to report materialized wallet/ledger/reservation and linked-workflow mismatches. It deliberately reports only; it does not repair money data automatically. `php bin/health.php` is a safe CLI check for DB connectivity and required writable storage.

## Investment positions and maturity processing

An investment purchase creates one immutable `investment_positions` record. It snapshots the company name, Country Pack, currency, exact quantity, platform share price, principal, projected-profit rule/value, duration, calculated projected profit and maturity date. Later offering edits never change an existing position.

Purchases debit only the available wallet balance through the immutable ledger. Maturity returns the snapshotted principal and credits the snapshotted projected profit as two separate ledger entries. `php bin/process-maturities.php --dry-run` reports eligible positions without writes; without the flag it processes safe batches. Configure HestiaCP to run it every 5–15 minutes. Database row locks and completed-status checks protect overlapping workers.

The system deliberately treats projections as projections, not guaranteed returns. Portfolio labels must use “active principal”, “projected profit”, “projected maturity value”, and “realized profit”—never live market value unless an actual market-data engine is introduced.

## Frontend and visual system

One responsive component system is shared by every Country Pack. Saved Country Pack themes inject validated CSS variables; country content, branding, flags, imagery, currency, language and legal/licence context are resolved server-side. Arabic public pages set RTL direction, while language selection never changes country or currency.

Public imagery must be locally stored, optimized, documented in `IMAGE_SOURCES.md`, and fall back safely to Global/default or a neutral CSS placeholder. `UI_REVIEW.md` is the deployment acceptance checklist for desktop, tablet, 320px mobile, RTL, long localized strings, empty states, accessibility, assets and financial flows.

## KYC and withdrawals

KYC is configured per Country Pack, including the distinct Global pack. It does not block registration, deposits, investments or sale requests. When a Country Pack requires KYC, only withdrawals require an approved, non-expired KYC submission. KYC documents must remain in private storage and are only served after owner or Super Admin authorization.

Withdrawals are manual. A submitted request immediately reserves the requested amount from available wallet funds; it is not yet a completed ledger debit. Super Admin review progresses through under-review, approved, processing and paid. Payment converts the reservation into exactly one `WITHDRAWAL` ledger entry; rejection/cancellation releases it and preserves a user-facing reason without exposing private admin notes.

## Recovery pass: connected financial workflows

Dashboard routes now provide deposit submission/history/detail, immutable transaction history, investment purchase review/confirmation, portfolio and position pages, sale requests, KYC draft/submission/review state, withdrawal submission/history/detail, and notifications. Administrative routes provide deposits, ledger/manual adjustment, investments/sale queue, KYC queue/configuration, withdrawals, payment-method creation and the reusable dynamic-form builder.

All wallet movements use `WalletService`; reservation consumption creates a final ledger debit without deducting the available balance twice. The maturity processor is idempotent at the ledger relationship level and uses a position row lock. These flows require final integration testing in a configured PHP/MySQL environment before deployment.

## HestiaCP deployment

Set the domain document root to `public/`. Keep `.env`, `app`, `config`, `database`, `resources`, and `storage/private` outside the public root. Apache clean URLs use `public/.htaccess`; enable `mod_rewrite` and `AllowOverride All`. For Nginx use `try_files $uri $uri/ /index.php?$query_string;`. Make `storage/cache`, `storage/logs`, `storage/private`, and `storage/uploads` writable by the web user, but never public.

For local TOTP QR rendering on Ubuntu/Hestia, install the distribution packages once: `sudo apt install php-gd phpqrcode`. If they are unavailable, users can still enter the displayed manual TOTP key; the secret never leaves the server.

See [DEPLOYMENT.md](DEPLOYMENT.md), [DATABASE.md](DATABASE.md), [SECURITY.md](SECURITY.md), [UI_REVIEW.md](UI_REVIEW.md), and [IMAGE_SOURCES.md](IMAGE_SOURCES.md) for operational requirements and acceptance criteria.

## Commands

- `php bin/migrate.php` — apply outstanding migrations once.
- `php bin/migrate.php status` — migration status.
- `php bin/seed.php` — idempotently seed Country Packs, languages, themes, auth settings, and the environment-defined Super Admin.
- `php tests/run.php` — foundation, Country Pack, password, crypto, TOTP, validation and account-access tests. Run the MySQL integration cases in `tests/FINANCIAL_INTEGRATION_TEST_PLAN.md` before production deployment.

Do not commit secrets, GeoLite databases, private uploads or private documents.
