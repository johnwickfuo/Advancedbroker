# Image Sources

No third-party company logo or cover image is seeded in this repository yet. The catalogue uses a neutral CSS monogram fallback rather than copying or hotlinking unverified copyrighted branding.

The `company_images` and `image_sources` tables record local storage path, source URL, provider, creator, licence, required attribution, download date and usage location before any external image is published. Administrator uploads are validated as decoded JPEG, PNG or WebP files with randomized storage names.

Existing Country Pack flag-source attribution remains at `public/assets/images/flags/SOURCES.md`.

## Final audit status

No newly sourced production imagery was introduced during the final hardening pass. This is intentional: an unverified or watermarked image is not a safe substitute for a documented licence. Before publishing a new country hero, company image or branded asset, add both the database `image_sources` record and the required attribution/usage entry here; use the neutral fallback until that review is complete.
