# Recovery and completion gap report

## Initial gap checklist

- [COMPLETED] Dashboard deposit, transaction, investment, portfolio, KYC, withdrawal and notification links no longer use the generic dashboard placeholder.
- [COMPLETED] Wallet service centralizes credits, debits, reservations, release and reservation consumption with database transactions and row locks.
- [COMPLETED] Deposit request, user history/detail/cancellation, secure private proof-file access, admin queue/review/approval/rejection and immutable approval ledger link are implemented.
- [COMPLETED] Investment buy/review/confirmation, immutable position snapshots, portfolio, investment detail, sale request/review actions and maturity job service are connected.
- [COMPLETED] KYC country configuration, versioned submission/draft/review paths, private document metadata, owner/admin-authorized document access and user/admin review pages are implemented.
- [COMPLETED] Withdrawal quote/submission/reservation, user history/detail/cancel, admin state transitions, rejection release and paid reservation consumption are implemented.
- [COMPLETED] Notification listing/read state and event creation for the principal financial/KYC workflows are implemented.
- [COMPLETED] Reusable form definitions, snapshots and a basic visual admin form/method builder are implemented.
- [PARTIALLY COMPLETED] Email template persistence schema is present, but a full SMTP adapter and editable rendered-template administration remain a separate mail-provider integration task.
- [PARTIALLY COMPLETED] Saved withdrawal-account schema is present; a dedicated encrypted CRUD UI remains outstanding.
- [COMPLETED] Uploaded deposit proof files are persisted under private randomized storage and are streamed only after owner or Super Admin authorization.
- [BLOCKED BY RUNTIME VERIFICATION ONLY] PHP syntax, migrations, MySQL locking/idempotency integration, mail delivery, browser rendering and responsive testing cannot run here because PHP/MySQL/browser runtime are unavailable.

## Static completion checks

- Routes were changed from the former financial/KYC placeholders to substantive controllers and views.
- Financial changes are routed through `WalletService`; controller code does not update wallet balances directly.
- User-facing money is rendered from integer minor units and Country Pack currency context.
- Sensitive KYC media uses private-storage paths and owner/admin-controlled controllers rather than predictable public paths.
- A static source scan was performed for the former “Coming soon” financial placeholder and direct financial UI stubs.
- Financial dashboard routes apply verified-email, account-status and Country Pack availability middleware; state changes remain POST+CSRF protected.
- Reconciliation accounts for active reservations and checks deposit, maturity, sale and withdrawal linkage/duplication without changing data.
