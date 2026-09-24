# Financial integration test plan

Run this suite against a disposable MySQL database after migrations and seeders.

- Deposits: country restriction, required dynamic field, valid/invalid file, private owner/admin receipt access, cancel, reject with reason, approve once and replay approval.
- Wallet: credit/debit, two concurrent debits, reservation/release/consume, immutable ledger references and reconciliation.
- Investments: whole/fractional quote, insufficient funds, inventory, holding cap, idempotent purchase, historical snapshots, licence guard, wrong-Country-Pack denial, sale rejection/approval once, maturity rerun and sale/maturity race.
- KYC: independent Global configuration, required/off withdrawal gate, draft/submit/reject/resubmit/approve, owner/admin document access and cross-user denial.
- Withdrawals: KYC-required and KYC-off gates, method country restriction, fee quote, concurrent double reservation, cancellation/rejection releasing funds, PAID once and replay PAID.

Also run authorization tests for cross-user deposits, transaction records, investment positions, sale requests, KYC documents, withdrawal requests, saved accounts and notifications; run Global fallback checks for Nigeria, Kenya, Ghana, Argentina, Egypt, Qatar, Malaysia and an unknown code; and run user/admin CSRF-negative tests for every state-changing endpoint.

The source-level tests in `tests/run.php` exercise exact money and pure calculation rules. The database cases intentionally require MySQL locking and cannot be truthfully run in this workspace without a configured database.
