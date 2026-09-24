# Security policy and sensitive-data handling

- KYC documents and proof files belong in `storage/private`; never expose their storage path or use public URLs.
- File access must require an authenticated owner or Super Admin authorization. Use safe `Content-Disposition`, no-cache headers and server-inspected MIME types.
- Do not log KYC values, documents, passport/ID numbers, or unmasked withdrawal destinations.
- Wallet mutations, reservations, investment movements and withdrawal state changes must be performed through locked database transactions and immutable ledger entries.
- A withdrawal reserves spendable funds when submitted. Rejection/cancellation must release the reservation; payment must create exactly one linked ledger debit.
- Credentials and mail transport secrets remain environment configuration, never admin-editable plaintext database settings.
# Recovery-pass controls

- Wallet balance fields are materialized caches. `WalletService` owns their mutation under database transaction and `FOR UPDATE` locks.
- Withdrawal reservation consumption removes reserved funds once and writes one `WITHDRAWAL` ledger record; rejection/cancellation releases the reservation.
- KYC paths are private metadata references. User document routes verify ownership; administrator routes remain admin-middleware protected.
- Dynamic form fields are fetched from server-owned definitions. Posted definitions, unexpected field keys and file MIME/size values must never be trusted from the browser.

## Final hardening controls

- Production boot refuses an empty `APP_KEY`; local development may use only a transient key and logs a clear warning. Never commit `.env`.
- Financial dashboard actions use verified-email, account-status and Country Pack availability middleware, plus CSRF protection.
- Private KYC and deposit proof routes re-check ownership or Super Admin authorization on every request. Paths are randomized, basename-validated and returned with `nosniff` and private/no-store headers.
- Uploads use server MIME inspection; images require successful decoding and PDFs require a PDF signature. PHP, HTML, SVG and unknown MIME types are rejected.
- Structured logs redact common credential, KYC, document and payment-account keys. Do not add raw request bodies to logs.
- Reconciliation is read-only. A failed mail/notification action must never reverse a committed financial or compliance decision.
