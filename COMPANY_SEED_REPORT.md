# Company Seed Verification Report

This repository contains the catalogue schema, country-scoped offering model and an intentionally conservative initial factual seed set. Each entry has an official company or exchange/regulatory source URL, a recorded verification date at seed time, and is seeded idempotently by `seed_key`.

| Country Pack | Company | Ticker | Exchange | Primary source |
| --- | --- | --- | --- | --- |
| Singapore | AEM Holdings Ltd. | AWX | Singapore Exchange Mainboard | SGX corporate-information record |
| Philippines | D&L Industries, Inc. | DNL | Philippine Stock Exchange | PSE issuer disclosure |
| Trinidad and Tobago | Guardian Holdings Limited | GHL | Trinidad and Tobago Stock Exchange | TTSEC record; company site |
| Global | AEM Holdings International | AWX | Singapore Exchange Mainboard | SGX corporate-information record |

## Deliberate research limitation

The requested approximately 340-company catalogue is **not** represented by invented entries. Completing it requires a country-by-country current verification pass against official issuers and exchanges, especially for the smaller Caribbean markets. The seed architecture is ready for per-country data files and remains intentionally incomplete until those records can be verified and their logo/usage rights recorded.

Platform offering prices in the seed are sample administrator-configured terms; they are not market prices and are labelled accordingly in the UI.
