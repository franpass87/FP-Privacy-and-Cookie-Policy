# FP Privacy and Cookie Policy

Lightweight WordPress plugin for GDPR-compliant privacy & cookie management (banner, granular consent, auto policies, Google Consent Mode v2, consent log, multisite, WP-CLI, REST).

*PHP 7.4+* • *WP 6.2+*

## TL;DR / Quick intro (IT)
Plugin leggero per gestire banner, preferenze granulari e policy privacy/cookie in modo conforme. Adatto a blog, e-commerce e portali grazie a rilevamento servizi e policy generate automaticamente.

## Features
- Cookie banner with Accept / Reject / Preferences states, autosave, and a discreet reopen control.
- Granular categories with default states, descriptions, and per-category scripts.
- Auto-generated Privacy & Cookie Policies based on detected services and languages.
- Google Consent Mode v2 signal management with automatic updates, documented in `docs/google-consent-mode.md`.
- `dataLayer` push and `CustomEvent` dispatch when consent changes.
- Consent log with hashed IP, retention policy, CSV export, and purge tools.
- Preview mode for banner and policies with revision bump workflow.
- Import / export settings as JSON for staging-to-production parity.
- Gutenberg blocks and matching shortcodes for embedding policies and preferences.
- REST API endpoints for consent submission and administrative tasks.
- WP-CLI commands for provisioning, cleanup, exports, and regeneration.
- Multisite-aware provisioning and per-site configuration isolation.
- Internationalization-ready with bundled `.pot` template.

## Installation
### Manual install
1. Copy or symlink this repository into `wp-content/plugins/fp-privacy-cookie-policy`.
2. Ensure file permissions allow WordPress to read the plugin files.

### Activation
1. Activate **FP Privacy and Cookie Policy** from **Plugins → Installed Plugins**.
2. On activation the plugin creates the consent log table and drafts Privacy & Cookie Policy pages per active language.

### Requirements
- PHP ≥ 7.4
- WordPress ≥ 6.2

## Quick Configuration
1. Select available languages and review auto-created policy drafts.
2. Open the Privacy and Cookie Policy editors to adjust copy, services, and legal references.
3. Configure banner layout, colors, typography, and button labels using the preview with contrast checks.
4. Define consent categories, default states, and associated scripts or integrations.
5. Review Google Consent Mode defaults and map categories to Consent Mode signals.
6. Set consent log retention and anonymization options.
7. Use import/export tools to replicate settings across environments.

## Auto-generation of policies
The plugin uses a `DetectorRegistry` to inspect active integrations such as GA4, Google Tag Manager, Meta Pixel, Hotjar, Microsoft Clarity, reCAPTCHA, YouTube, Vimeo, LinkedIn Insight Tag, TikTok Pixel, Matomo, WooCommerce, and more. Detected services feed the `PolicyGenerator`, which outputs tailored Privacy & Cookie Policy content per language with GDPR-aligned sections covering definitions, data sources, safeguards, children's data, breach handling, and governance as of October 2025.

Use the **Regenerate policies** button when services change; the admin UI displays drift notices if detected services differ from the latest policy snapshot. Developers can customize behavior with the following filters:
- `fp_privacy_services_registry` — extend or modify detected services.
- `fp_privacy_policy_content` — filter the generated Privacy Policy HTML.
- `fp_cookie_policy_content` — filter the generated Cookie Policy HTML.

## Consent Mode v2 & Events
The plugin initializes Google Consent Mode v2 with default signals and updates them on user interaction:
- `analytics_storage`
- `ad_storage`
- `ad_user_data`
- `ad_personalization`
- `functionality_storage`
- `security_storage`

Consent changes push a `dataLayer` event named `fp_consent_update` and dispatch a `CustomEvent` named `fp-consent-change` on `window`. Ensure Google Tag Manager or gtag.js is loaded after the plugin scripts to avoid conflicting consent defaults.

## Shortcodes & Blocks
Shortcodes expose banner and policy components:
- `[fp_privacy_policy]`
- `[fp_cookie_policy]`
- `[fp_cookie_preferences]`
- `[fp_cookie_banner]`

Four Gutenberg blocks mirror the shortcodes and are available without any build toolchain.

## WP-CLI
```
wp fp-privacy status
wp fp-privacy recreate [--force]
wp fp-privacy cleanup
wp fp-privacy export --file=/path/consents.csv
wp fp-privacy settings-export --file=/path/settings.json
wp fp-privacy settings-import --file=/path/settings.json
wp fp-privacy detect
wp fp-privacy regenerate --lang=it|en_US|all [--bump-revision]
```

## REST API
Namespace: `fp-privacy/v1`
- `GET /consent/summary` — Admin-only aggregate statistics.
- `POST /consent` — Store granular consent (nonce validation and rate limiting enforced).
- `POST /revision/bump` — Admin-only endpoint to force a consent revision bump.

## Performance & Multisite
- Assets enqueue conditionally; CSS variables for the banner print inline only when the banner renders.
- Lightweight footprint suitable for shared hosting; daily cleanup cron purges expired consent entries.
- Network activation provisions defaults for each site via `wpmu_new_blog`, with options stored per-site and caches cleared on `switch_to_blog`.

## Localization
Textdomain: `fp-privacy`. The repository includes a `.pot` template alongside example translations for `en_US` and `it_IT`.

## Compliance Notes
- The plugin is a technical aid and does not constitute legal advice.
- Non-essential cookies and trackers fire only after explicit consent; strictly necessary cookies remain active.
- Exportable consent records support accountability and audit requests.

## Extensibility (Hooks & Filters)
- Actions: `fp_consent_update`, `fp_privacy_settings_imported`, `fp_privacy_policies_regenerated`.
- Filters: `fp_privacy_csv_export_batch_size`, `fp_privacy_cookie_duration_days`, `fp_privacy_services_registry`, `fp_privacy_service_purpose_{key}`.

## Changelog
- **Unreleased — Documentation & UX polish.**
  - Documented the Consent Mode v2 helper flow and linked companion guidance across the knowledge base.
  - Highlighted the floating reopen control for cookie preferences and refreshed accessibility metadata in public docs.
  - Expanded policy template descriptions to match the latest GDPR expectations captured in October 2025 updates.
- **0.1.1 — Refinements & hardening.**
  - Improved banner bootstrapping for shortcodes, accessibility safeguards, and Consent Mode fallbacks.
  - Hardened consent logging with stricter category handling, REST error surfacing, and cookie attribute filters.
  - Optimized detector-driven policy generation with localized service grouping and configurable cache TTL.
- **0.1.0 — Initial alpha release.**
  - First public release with banner, auto policies, Consent Mode v2, consent log, REST, WP-CLI, and multisite support.

## License & Author
- License: GPL-2.0-or-later
- Author: [Francesco Passeri](https://www.francescopasseri.com)
