# FP Privacy and Cookie Policy
Lightweight WordPress plugin for GDPR-compliant privacy & cookie management (banner, granular consent, auto policies, Google Consent Mode v2, consent log, multisite, WP-CLI, REST).

PHP 7.4+ · WordPress 6.2+

## TL;DR / Quick intro (IT)
Plugin leggero per banner cookie, gestione consensi granulari e generazione automatica di privacy/cookie policy. Funziona senza dipendenze complesse ed è adatto a blog, e-commerce e portali che richiedono compliance rapida.

## Features
- Cookie banner with Accept, Reject, and Preferences actions plus contrast-checked layouts.
- Granular categories with default purposes and custom labels per locale.
- Automatic Privacy & Cookie Policy generation based on detected services.
- Google Consent Mode v2 signal management out of the box.
- `dataLayer` push + `CustomEvent` dispatch for consent updates.
- Consent log with hashed IP, retention scheduler, and CSV export tooling.
- Preview mode for testing banner variants before publishing.
- Revision bump to force policy cache invalidation.
- Import/export settings via JSON files for fast replication.
- Gutenberg blocks and shortcodes for policies and preference dialogs.
- REST endpoints for consent capture and administrative actions.
- WP-CLI commands for detection, regeneration, and maintenance tasks.
- Multisite provisioning with per-site settings isolation.
- Internationalization-ready with bundled `.pot` file.

## Installation
1. **Manual install**: Copy the plugin folder into `wp-content/plugins/fp-privacy-cookie-policy/`.
2. **Activation**: Activate via the Plugins screen; the plugin creates the consent log database table and drafts Privacy & Cookie Policy pages if missing.
3. **Requirements**: PHP ≥ 7.4, WordPress ≥ 6.2.

## Quick Configuration
1. Select available languages and review default translations.
2. Edit the Privacy Policy and Cookie Policy content within the built-in editors.
3. Adjust banner layout, colors, and typography; use preview mode to validate contrast ratios.
4. Enable or disable granular consent categories and assign services to each.
5. Define Consent Mode v2 defaults for new visitors.
6. Configure consent log retention and automated cleanup.
7. Import or export settings JSON to share configurations across environments.

## Auto-generation of policies
The plugin leverages a `DetectorRegistry` to recognize common services (Google Analytics 4, Google Tag Manager, Meta Pixel, Hotjar, Microsoft Clarity, reCAPTCHA, YouTube, Vimeo, LinkedIn Insight, TikTok, Matomo, WooCommerce integrations, and more). Detected signals flow into the `PolicyGenerator`, which produces localized Privacy & Cookie Policy content.

Admins can regenerate policies via a dedicated button; the UI highlights content drift if manual edits exist. Customize generation through filters:

- `fp_privacy_services_registry`
- `fp_privacy_policy_content`
- `fp_cookie_policy_content`

## Consent Mode v2 & Events
The plugin manages Google Consent Mode v2 signals for `analytics_storage`, `ad_storage`, `ad_user_data`, `ad_personalization`, `functionality_storage`, and `security_storage`. Default signals are applied on first visit and updated whenever preferences change.

Each consent update triggers:

- `dataLayer` event `fp_consent_update`
- `CustomEvent` `fp-consent-change`

Ensure Tag Manager or gtag scripts load after the plugin bootstrap to prevent conflicting consent settings.

## Shortcodes & Blocks
Available shortcodes:

- `[fp_privacy_policy]`
- `[fp_cookie_policy]`
- `[fp_cookie_preferences]`
- `[fp_cookie_banner]`

The plugin also bundles four Gutenberg blocks mirroring the shortcodes without requiring any JavaScript build toolchain.

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
- Namespace: `fp-privacy/v1`
- `GET /consent/summary` (administrator access) — aggregated consent statistics.
- `POST /consent` — stores granular consent choices (nonce-protected with rate limiting).
- `POST /revision/bump` (administrator) — increments policy revision to invalidate caches.

## Performance & Multisite
The plugin only enqueues CSS and JS when a banner or preference dialog renders, using inline CSS variables for runtime theming. Daily cron jobs purge expired consent logs and transient caches, keeping the footprint low even on shared hosting.

In multisite installs, network activation provisions new sites via `wpmu_new_blog`. Options are stored per-site with cache invalidation when `switch_to_blog` runs, ensuring consistent configuration isolation.

## Localization
Version 0.1.0 ships with textdomain `fp-privacy` and an included `.pot` file. Sample translations for `en_US` and `it_IT` demonstrate message contexts and plural handling.

## Compliance Notes
This plugin is a technical aid and does not provide legal advice. It blocks non-essential scripts until consent is granted, while strictly necessary cookies remain active. Consent records can be exported for accountability and audit trails.

## Extensibility (Hooks & Filters)
- **Actions**: `fp_consent_update`, `fp_privacy_settings_imported`, `fp_privacy_policies_regenerated`
- **Filters**: `fp_privacy_csv_export_batch_size`, `fp_privacy_cookie_duration_days`, `fp_privacy_services_registry`, `fp_privacy_service_purpose_{key}`

## Changelog
- 0.1.0 — Initial alpha release with consent banner, policy generation, Consent Mode v2, logging, REST, and WP-CLI tooling.

## License & Author
GPL-2.0-or-later. Developed by [Francesco Passeri](https://francescopasseri.com/).
