# FP Privacy and Cookie Policy

FP Privacy and Cookie Policy is a WordPress plugin that delivers a full GDPR/ePrivacy consent management suite with Google Consent Mode v2 support. It automates banner rendering, consent state logging, document generation, WP-CLI tools, REST endpoints, and Gutenberg blocks without relying on a JavaScript build toolchain.

## Key Features

- GDPR-ready cookie banner with granular preferences, preview mode, accessibility-focused modal, revision reminder, and configurable palette.
- Automated privacy and cookie policy generation based on detected integrations (Google Analytics 4, GTM, Meta Pixel, Hotjar, etc.).
- Consent registry stored in a dedicated database table with hashed IPs, daily retention cleanup, CSV export, and analytics summary.
- Google Consent Mode v2 orchestration and `dataLayer` integration with `fp-consent-change` CustomEvent.
- Shortcodes, Block API v2 (ES5) blocks, and template overrides for complete front-end flexibility.
- WP-CLI commands for status, cleanup, export, detection, and policy regeneration.
- REST API endpoints (`fp-privacy/v1`) for saving consent and retrieving dashboards.
- Multisite aware activation, per-site provisioning, and automatic policy page creation.
- Extensive i18n with `fp-privacy` text domain and ready-to-translate POT file.
- Admin UX covering settings, policy editor with diff preview, consent log table, CSV export, tools (import/export), and quick guide.

## Installation

1. Copy the `fp-privacy-cookie-policy` directory into your WordPress `wp-content/plugins/` directory.
2. Activate **FP Privacy and Cookie Policy** from the Plugins screen (network activate on multisite if required).
3. During activation the plugin will:
   - Create the consent log table (`wp_fp_consent_log`).
   - Generate privacy/cookie policy pages for the default locale (shortcodes inserted).
   - Schedule the daily cleanup cron (`fp_privacy_cleanup`).

## Configuration Overview

1. Navigate to **Privacy & Cookie → Settings**.
2. Configure active languages, banner copy, layout, palette, consent mode defaults, retention, and controller/DPO details.
3. Use the live preview panel and color contrast checker to validate accessible palettes as you type.
4. Observe the stale snapshot notice above the form whenever detected services or generated documents are older than two weeks.
5. Save settings and optionally bump the consent revision to re-trigger the banner for returning visitors.
6. Use **Policy editor** to customize or regenerate documents. Regeneration invokes the detector registry and updates pages while bumping the revision.
7. Review the **Consent log** for event breakdowns, filters, and CSV export.
8. **Tools** allow JSON import/export, regeneration shortcuts, and revision reset. The quick guide documents shortcodes, blocks, and hooks.

## Shortcodes

- `[fp_privacy_policy]` – render the generated privacy policy.
- `[fp_cookie_policy]` – render the generated cookie policy.
- `[fp_cookie_preferences]` – output the cookie preferences management button.
- `[fp_cookie_banner]` – manually place the banner in templates.

## Gutenberg Blocks (ES5, No Build Step)

Blocks are registered under the “FP Privacy” category:

1. **Privacy Policy** – server-rendered via shortcode.
2. **Cookie Policy** – server-rendered via shortcode.
3. **Cookie Preferences** – injects the preferences button with aria attributes.
4. **Cookie Banner** – renders the floating/bar banner wrapper.

Scripts are loaded with the WordPress-provided globals (`wp.blocks`, `wp.element`, `wp.i18n`, `wp.editor`) and require no additional tooling.

## Google Consent Mode v2 & Data Layer

- Default consent signals are dispatched with `gtag('consent', 'default', {...})` at banner bootstrap.
- When users update preferences the plugin sends `gtag('consent', 'update', {...})`, pushes `fp_consent_update` events into the `dataLayer`, and emits the `fp-consent-change` CustomEvent.
- Use the README quick guide or `bin/qa-checklist.md` for GTM snippet examples and debugging pointers.

## Detector & Policy Generator

`FP\Privacy\Integrations\DetectorRegistry` inspects scripts, known option signatures, and cookie names to identify services. The generator groups services by category, provider, cookies, and legal basis to build localized documents using the templates in `templates/`.

The detector map is filterable via `fp_privacy_services_registry` for custom services, and the generator output can be post-processed with `fp_privacy_policy_content` / `fp_cookie_policy_content` filters.

## Consent Registry & Data Retention

- Logs events (`accept_all`, `reject_all`, `consent`, `reset`, `revision_bump`) with hashed IP, user agent, language, revision, and JSON states.
- Daily cleanup respects the `retention_days` option; the interval is filterable via `fp_privacy_csv_export_batch_size` and the cron schedule runs per site.
- Exporter/Eraser handlers integrate with WordPress personal data tools.

## WP-CLI Commands

Run `wp help fp-privacy` for full documentation. Highlights:

- `wp fp-privacy status` – Inspect table presence, event counts, and next cleanup.
- `wp fp-privacy recreate [--force]` – Recreate the consent table and reschedule cron.
- `wp fp-privacy cleanup` – Execute retention cleanup immediately.
- `wp fp-privacy export --file=/path/to/file.csv` – Stream CSV export in batches.
- `wp fp-privacy settings-export --file=/path/to/file.json`
- `wp fp-privacy settings-import --file=/path/to/file.json`
- `wp fp-privacy detect` – Output detected services.
- `wp fp-privacy regenerate [--lang=LANG|all] [--bump-revision]`

## REST API

Namespace: `fp-privacy/v1`

- `GET /consent/summary` (admin capability) – returns 30-day event stats and revision info.
- `POST /consent` – stores granular consent with nonce and rate limiting.
- `POST /revision/bump` – admin endpoint mirroring the revision bump action.

## Hooks & Filters

- `fp_consent_update( $states, $event, $revision )`
- `fp_privacy_settings_imported( $settings )`
- `fp_privacy_policy_content` / `fp_cookie_policy_content`
- `fp_privacy_services_registry`
- `fp_privacy_service_purpose_{key}`
- `fp_privacy_csv_export_batch_size`
- `fp_privacy_cookie_duration_days`

## Multisite Support

- Network activation provisions all sites by creating tables, options, and scheduling cleanup events.
- New sites added via `wpmu_new_blog` trigger automatic provisioning.
- Each site retains independent settings and consent registries.

## Development Notes

- PHP 7.4+, WordPress 6.2+ compatibility target.
- No compiled assets or `.min` files; ES5 scripts enqueue directly.
- Autoloading uses a lightweight PSR-4 routine scoped to the `FP\Privacy` namespace.
- Tests are not bundled; follow the QA checklist for manual verification.
- Run `bin/package.sh` from the repository root to create a distributable ZIP without development artefacts.

## License

Distributed under the [GNU General Public License v2 or later](LICENSE).

## Disclaimer

This plugin provides technical tooling to support privacy compliance workflows. Always review generated documents and consent flows with your legal counsel.
