# Project Progress — Prompt 5 (partial catalogue foundation)

## Company catalogue foundation

- Added `005_create_company_catalogue.php` with separate factual company, Country Pack assignment, offering, price-history, term-history, source, image and image-attribution tables. Financial prices use `BIGINT` minor units; share quantities and projection values use exact `DECIMAL` columns.
- Added country-scoped public catalogue and detail routes. A requested offering is selected using the active `CountryContext`, so a Germany visitor/user cannot retrieve a UK-only offering by manipulating a URL. Global remains a separate catalogue.
- Added server-side `OfferingValidator` and `OfferingCalculator`, covering percentage terms, fixed profit per share, fixed profit per investment, fraction rules and a calculated maturity date. The JavaScript calculator is illustrative only.
- Added Super Admin catalogue list/create/edit/archive endpoints and audited company/offering modifications. Archive preserves the factual company record and prevents public catalogue display.
- Added indexed search/filter/sort/pagination repository queries with an allowlisted `ORDER BY` map and no in-memory filtering of the intended catalogue size.

## Research and media

- Added idempotent `CompanySeeder` with current source-backed examples for Singapore (AEM Holdings / AWX), Philippines (D&L Industries / DNL), Trinidad and Tobago (Guardian Holdings / GHL), plus a separate Global offering.
- Added `COMPANY_SEED_REPORT.md` and `IMAGE_SOURCES.md`. No external logos or covers are copied/hotlinked without a documented reuse basis; the current neutral monogram is intentionally not presented as a company logo.
- The requested approximately 340 issuer catalogue and locally licensed company/country imagery are not complete. The project intentionally does not invent company identities, tickers, listings, or asset licences. The per-country seed structure, source tables and asset tracking are ready for the required verified import.

## Verification

- PHP syntax validation across all PHP files, Composer optimized autoload generation and the frameworkless tests pass, including new offering validation/calculator tests.
- MySQL migration/seed execution is blocked because this workspace has no reachable configured MySQL service. A PHP built-in-server smoke test could not bind the requested local port in the execution environment.

## Prompt 6 — wallet and deposit foundation

- Added migration `006_create_wallets_deposits_forms.php`: materialized wallets, immutable ledger transactions, reservations, reusable dynamic-form definitions/options, country-assigned deposit methods, deposit requests, submission values and private file metadata.
- Added centralized `WalletService` with transactional row locking, exact minor-unit credits/debits, negative-balance protection, reservations and financial-exposure detection.
- Added `DepositService` with country-restricted method lookup, min/max and exact fee calculation, request snapshots, idempotent approval, and required rejection reason handling.
- Admin country reassignment now blocks when financial history or pending deposits exist; it never converts balances between Country Pack currencies.
- Added `bin/reconcile-wallets.php`, a report-only reconciliation check for materialized wallet balances against completed ledger entries.

### Prompt 6 known limitations

- User/admin deposit and dynamic-form controllers/views, controlled private receipt streaming, CSV export, reversal UI and full database-backed integration tests remain to be completed before this is production-ready.
- The execution environment lost the PHP executable before final syntax/test verification, and no MySQL service is configured for migration/approval/reconciliation integration checks.

## Prompt 7 — investment position foundation

- Added `007_create_investment_positions.php`, with immutable investment snapshots, linked purchase/maturity ledger references, indexed maturity processing, and sale-request persistence.
- Added `InvestmentService` for Country Pack-scoped purchase validation, exact offering calculations, immutable snapshots, wallet debit, maturity batching, and separate principal/profit credits.
- Added `bin/process-maturities.php` with `--dry-run`; row locking and position-status checks prevent repeat maturity crediting.

### Prompt 7 known limitations

- Portfolio, purchase confirmation, user investment routes, sale-request approval/rejection controllers/views, chart UI, notifications, inventory/holding limit enforcement, and database-backed concurrency tests still require completion.
- PHP/MySQL verification remains unavailable in this execution environment.

## Prompt 8 — KYC and withdrawal foundation

- Added `008_create_kyc_withdrawals.php` for versioned country-specific KYC configuration/submissions/events/private document metadata and country-scoped withdrawal methods/requests.
- Added `KycService` and `WithdrawalService`: KYC is only enforced for withdrawals when configured for the assigned Country Pack; withdrawal submission reserves exact spendable funds through the existing locked wallet service.
- Added `SECURITY.md` with private-file, sensitive-logging, reservation, idempotency and secret-configuration rules.

### Prompt 8 known limitations

- KYC/withdrawal controllers, private document streaming, admin queues/decisions, email-template management, saved payout accounts, notifications, and full integration tests remain to be implemented.

## Prompt 9 — visual review foundation

- Documented the existing responsive shared component/theme system and Country Pack visual inheritance in `UI_REVIEW.md`.
- Added deployment review criteria for Country Pack/Global identity, mobile/RTL/CJK/German layout, accessibility, fallbacks, public imagery and financial-flow screens.
- No unlicensed or fabricated imagery was added. The existing `IMAGE_SOURCES.md` remains the source-of-truth record for assets.

## Country Pack engine implemented

- Request-scoped `CountryContext`, `CountryResolver`, `CountryService`, country repository abstraction, GeoIP service and country access service.
- 32 national Country Packs plus one distinct Global pack; Singapore, Philippines, Trinidad and Tobago, Jamaica and Barbados are included. Nigeria and Kenya are explicitly excluded/disabled if historical rows exist.
- Mandatory Global fallback for unsupported, disabled, failed and unknown GeoIP results.
- Resolution priority: admin-assigned country, stored account country, Global for signed-in users; debug preview, trusted GeoIP, then Global for guests.
- Cloudflare `CF-IPCountry` support is opt-in and requires a trusted proxy; optional local MaxMind support is available without an external API dependency.

## Localization, currencies and design

- 17 language resources and country-language associations, with selected → country default → English translation fallback.
- A CSRF-protected language selector persists the chosen session preference and writes the authenticated user preference server-side when authentication supplies a user ID.
- Country currency, symbol position, locale and timezone configuration are seeded; JPY and KRW display without fractional units.
- Validated theme JSON injects only safe CSS custom properties through a CSP nonce. Each pack has a professional, distinct theme while retaining one application UX.
- Country page content and future visual asset metadata are stored per country/language. Flag assets are included with source attribution; Global uses a neutral globe.

## Database changes

- `002_create_country_pack_engine.php` extends countries and users, introduces country content and assets tables, and represents Global with `slug=global` and null ISO codes.
- Country seed is idempotent and seeds languages, themes, country content, enabled state and supported-language links.
- `003_create_authentication_security.php` extends `users` for profile, Country Pack assignment provenance, terms/privacy acceptance, account status, Super Admin role, and encrypted 2FA metadata. It adds one-time token, remember-token, user-session, security-event, recovery-code, and terms-acceptance tables.
- `AuthSettingsSeeder` seeds the configurable `require_email_verification` setting and creates one environment-defined Super Admin only when no Super Admin exists.
- `004_create_admin_content_legal_license.php` adds country branding overrides, FAQ records, immutable/versioned legal documents, country licences and settings, and private contact messages.

## Super Admin and country administration

- Super Admin overview now uses live user/Country Pack counts when MySQL is configured, with recent registrations.
- Country Pack manager supports safe core settings, enable/disable behaviour, saved validated themes, brand/contact overrides, image slots, local content, FAQ entries, legal versions and licence settings.
- Disabling a Country Pack preserves assigned user records and routes new visitors to Global; country-specific account actions are blocked until reassignment. Global remains enabled.
- Session-scoped, Super-Admin-only Country Pack preview is prepared without changing the administrator’s account country.
- Sensitive administration and contact-status changes write immutable audit entries. The Audit Log and contact-message views are read-only aside from message state.

## Public pages, branding, legal and licences

- Homepage now provides Country Pack-driven CTA, intro, catalogue placeholder, trust, support, FAQ and licence teasers.
- Public About, How It Works, FAQ accordion, Contact form, Terms, Privacy, Risk Disclosure and Licence routes inherit the active Country Context.
- Country branding follows override → Global → app fallback. Contact/social/footer information is independently configurable.
- Legal documents are versioned per country/language/type and sanitized before storage. A new published version never removes historic versions.
- Licence status calculation, expiry warning settings, independent Global licensing and future `canInvest` eligibility guard are implemented. No fictitious licence documents or regulatory claims are seeded.

## Authentication and dashboard implemented

- Registration, login, logout, intended-route protection, brute-force cooldown, secure remember-me tokens, password rehashing, password reset and password-change flows.
- Registration always uses the resolved Country Context: Germany/Singapore/Trinidad and Tobago receive their supported packs; Nigeria, Kenya, Ghana and every other unsupported or failed lookup receives Global. The browser cannot submit a country ID to change this outcome.
- Account language preference is saved separately and only allowed if linked to the assigned Country Pack. It never changes country, currency, licence or investment eligibility.
- Optional admin-controlled email verification, expiring hashed verification tokens, resend flow, and server-side verification checks on sensitive placeholder routes.
- Authenticator-app TOTP with locally generated QR code, AES-256-GCM encrypted secrets, initial confirmation before activation, and hashed one-time recovery codes.
- Session/device history, user-directed session revocation, security events, notifications, and non-secret structured security logging.
- User profile/security/dashboard foundation with zero-state financial metrics, Country Pack currency/branding, account status, verification, KYC, notifications and future navigation.
- Strict Super Admin middleware, user list/search/detail, Country Pack reassignment with audit log/session invalidation, account activation/restriction/suspension controls, and email-verification setting control.

## Tests

- Supported GeoIP: Germany, Singapore, Philippines, Trinidad and Tobago.
- Global fallback: Nigeria, Kenya, Ghana, Argentina, unknown and failed GeoIP.
- Disabled packs, authenticated country override, Global-assigned user, language-vs-country separation, untrusted headers, cross-country authorization, translation fallback and theme-injection rejection.
- Registration-rule validation, secure password hashing, encrypted credential handling, TOTP verification, random token hashing, CSRF, and restricted/suspended account access checks.
- Legal sanitizer, licence status/eligibility rules, Country Pack fallback/security and public informational-page runtime checks.

## Known limitations

- MySQL-backed end-to-end tests (registration persistence, reset/verification token consumption, remember-token rotation and admin actions) require a configured MySQL development database; the workspace does not contain one.
- SMTP delivery needs a focused mail adapter if native PHP `mail()` is not suitable for the target HestiaCP host. Development mail is logged safely.
- Database-backed end-to-end country/branding/legal/licence upload verification still requires a reachable MySQL development database; none is available in this workspace.
- Company/investment records, financial ledger, deposits, withdrawals, KYC processing and production country imagery remain future work.
- MaxMind requires an optional local GeoLite2 database and package configuration.
- Country hero imagery is a reusable neutral placeholder until a curated licensed image library is added.

## Next prompt

Implement country-scoped company catalogue, immutable financial ledger, investments, deposits/withdrawals and KYC, calling the Country and Licence eligibility services before sensitive financial operations.
# Recovery completion pass — Prompts 6–9

- Replaced the dashboard’s financial/KYC placeholders with substantive deposit, transaction, investment purchase, portfolio, KYC, withdrawal and notification routes/views.
- Expanded the wallet service to support idempotent ledger movements, transaction history, reservations, release and reservation consumption.
- Connected deposit and withdrawal queues, review transitions, user-facing rejection reasons and financial notifications.
- Added purchase review/confirmation, position snapshots, portfolio metrics, sale requests/approval/rejection and maturity processing integration points.
- Added versioned KYC configurations, draft/submission/review flow and private-document metadata handling.
- Added reusable dynamic-form snapshots plus basic Super Admin payment-method/form-builder interfaces.

### Runtime verification status

Implementation was completed statically. PHP syntax checks, migrations, MySQL integration/concurrency tests, browser rendering and mail delivery were not executable in this workspace because no PHP/MySQL/browser runtime is available. See `IMPLEMENTATION_GAP_REPORT.md` for exact remaining partial items.

## Final engineering hardening pass — Prompt 10 (static)

- Protected financial dashboard routes with configured verification, account-state and Country Pack availability checks while retaining CSRF protection.
- Corrected read-only reconciliation for available/reserved wallet balances and added linked deposit, maturity, sale and withdrawal consistency checks.
- Added authorization-checked private deposit proof and KYC document retrieval routes, stricter upload inspection, content-disposition safety and structured log redaction.
- Added `DATABASE.md`, `DEPLOYMENT.md` and `bin/health.php`; production now refuses an empty APP_KEY.
- Runtime verification remains required in a PHP/MySQL/browser-enabled staging environment; this workspace does not provide those executables/services.
