# Database architecture

## Core context

`countries`, `languages`, and country-language assignments define the Country Pack model. `users.country_id` and the optional Super Admin `assigned_country_id` determine an authenticated user's effective Country Pack; GeoIP is only a guest resolver.

## Financial records

`wallets` materializes available and reserved minor-unit balances. It is a cache of the append-only `ledger_transactions` table, not an independent source of truth. Money is stored as `BIGINT` minor units; share quantities and percentage terms use `DECIMAL(20,8)`.

Financial mutations must lock the wallet and relevant domain record in one MySQL transaction. `wallet_reservations` move value from available to reserved for pending withdrawals. A paid withdrawal consumes a reservation exactly once; a rejected/cancelled withdrawal releases it.

`deposit_requests`, `investment_positions`, `sale_requests`, and `withdrawal_requests` hold workflow state and link to their resulting ledger rows. They use restrictive foreign keys; financial and compliance history must never be cascade-deleted.

`investment_positions` is a snapshot: price, quantity, profit term, duration, calculated profit, maturity and country/currency are copied at purchase. Later offering changes affect only future purchases.

## Compliance and files

`kyc_configurations` are versioned by Country Pack. `kyc_submissions`, values, events and documents retain the configuration snapshot used at submission. Private file metadata stores randomized paths only; document bytes remain under `storage/private`.

## Integrity checks

Run `php bin/reconcile-wallets.php` against a production-safe read-only database connection after migrations, scheduled-job changes, and incident recovery. It reports non-zero on critical wallet, reservation, deposit, maturity, sale or withdrawal-link discrepancies and never repairs data.

## Operational indexes

High-volume routes rely on indexes for user/date/status/reference access: ledger transactions, deposits, investment maturity/status, sales, KYC, withdrawals, notifications and Country Pack catalogue queries. Before very large-scale deployment, review query plans using production-like data rather than removing integrity indexes.
