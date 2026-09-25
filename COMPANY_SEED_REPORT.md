# Company Seed Verification Report

The catalogue seeder creates a reusable factual master pool of 20 public companies and uses that pool to populate market-specific platform investment offerings. Company facts remain separate from administrator-configured offering terms.

## Catalogue targets

| Market type | Active offerings after seed | Featured offerings |
| --- | ---: | ---: |
| Each enabled national market | at least 10 | at least 5 |
| Standard / Global catalogue | at least 20 | at least 5 |

The seeder is idempotent: it creates missing records but does not overwrite an existing administrator-edited offering. On an existing installation that already contains the original starter records, those records count toward the market target and the seeder fills the remaining slots.

## Factual master company pool

Apple, Microsoft, NVIDIA, Alphabet, Amazon, JPMorgan Chase, Visa, Coca-Cola, SAP, Siemens, ASML, LVMH, TotalEnergies, Toyota Motor, Sony Group, Samsung Electronics, DBS Group, HSBC, BHP and Reliance Industries.

Each master company includes an official investor/company source in company_sources. Market offerings rotate through the pool so national catalogues are not identical. Global uses up to the full 20-company pool. Platform offering prices and projected returns are administrator-configured terms; they are not live exchange prices, historical market returns or guarantees.

Verification commands:

    php bin/seed.php
    php bin/verify-seed.php

The verification command exits non-zero if an enabled national market has fewer than 10 active offerings or 5 featured offerings, or if Global has fewer than 20 active offerings or 5 featured offerings.
