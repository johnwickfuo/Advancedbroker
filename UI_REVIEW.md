# UI review checklist

## Shared foundations reviewed

- Theme variables use Country Pack primary, secondary, accent, surface, text and border values rather than hard-coded national pages.
- Responsive public header, desktop/mobile dashboard shell, form grid, cards, tables and focus styles are defined in `public/assets/css/app.css`.
- Arabic emits `dir="rtl"` from the public layout. Review dashboard/admin components before enabling Arabic content beyond the existing shared UI.
- The Global identity uses its neutral globe flag asset and USD, not a United States flag or catalogue.

## Required visual acceptance checks

| Area | Desktop | Tablet/320px | Long/RTL text | Empty/error state |
| --- | --- | --- | --- | --- |
| Public pages | Pending runtime review | Pending | German, Arabic, Japanese, Korean | Pending |
| Catalogue/detail/calculator | Pending runtime review | Pending | Currency display | Pending |
| Dashboard/auth | Pending runtime review | Pending | Arabic/CJK | Pending |
| Deposit/investment/KYC/withdrawal | Templates and shared layouts present; runtime review required | Responsive wrappers/forms require browser verification | German/Arabic/CJK pending runtime check | Empty/rejection states present; verify visually |
| Admin screens | Pending runtime review | Pending | Pending | Pending |

## Image controls

1. Use documented, locally stored images only.
2. Record source URL, creator, licence/usage note, attribution and placement in `IMAGE_SOURCES.md` and `image_sources`.
3. Never publish watermarked, hotlinked, generic fake-company, or unverified regulatory images.
4. Fallback: country asset → Global/default asset → neutral placeholder; never a broken image.

## Runtime blocker

This workspace has no PHP executable or reachable MySQL service. Device review, migration execution and data-backed visual checks must be completed on the target development environment before production sign-off.
