# FP Privacy and Cookie Policy

> Provides a GDPR-ready consent banner, consent logging, and automated privacy/cookie policies with Google Consent Mode v2 for WordPress. Includes REST, WP-CLI, and Gutenberg tooling for privacy workflows.

## Plugin Info

| Key | Value |
| --- | --- |
| Name | FP Privacy and Cookie Policy |
| Version | 0.1.1 |
| Author | [Francesco Passeri](https://francescopasseri.com) |
| Author Email | [info@francescopasseri.com](mailto:info@francescopasseri.com) |
| Requires WordPress | 6.2 |
| Tested up to | 6.6 |
| Requires PHP | 7.4 |
| Text Domain | `fp-privacy` |
| Domain Path | `/languages` |
| License | [GPL-2.0-or-later](LICENSE) |

## About

FP Privacy and Cookie Policy provides a privacy compliance toolkit for WordPress that fuses an accessible consent banner with automated privacy and cookie policy generation. It focuses on maintainable, build-step-free JavaScript, integrates Google Consent Mode v2, and exposes REST and WP-CLI interfaces to help site owners and developers orchestrate consent workflows across multisite environments.

## Features

- GDPR-ready consent banner with floating or bar layouts, palette preview, accessibility guards, and shortcode placement.
- Automated privacy and cookie policy generation that detects services such as Google Analytics 4, GTM, Meta Pixel, Hotjar, YouTube, TikTok, and more.
- Consent registry stored in a dedicated database table with hashed IP addresses, retention cleanup, CSV export, and analytics summaries.
- Google Consent Mode v2 bootstrap and update helpers with `dataLayer` pushes and the `fp-consent-change` `CustomEvent`.
- REST API namespace `fp-privacy/v1` for submitting consent decisions and fetching consent summaries.
- WP-CLI commands for inspecting status, regenerating policies, exporting logs, resetting revisions, and orchestrating detection routines.
- Gutenberg blocks (ES5, no build tooling) for banner placement, policy output, and the consent preferences button.
- Shortcodes and template tags for rendering policies, preferences buttons, and the consent banner in classic themes.
- Multisite support with automatic provisioning on activation and `wpmu_new_blog` events.
- Extensive translation support with ready-to-build POT files and localization helpers.

## Installation

1. Copy the `fp-privacy-cookie-policy` directory into your WordPress `wp-content/plugins/` directory.
2. Activate **FP Privacy and Cookie Policy** from the Plugins screen (or network activate in multisite).
3. During activation the plugin creates the consent log table, generates privacy and cookie policy pages, and schedules the daily cleanup cron.

## Usage

### Configure settings

1. Navigate to **Privacy & Cookie → Settings** and configure active languages, banner content, palette, Consent Mode defaults, retention windows, and controller/DPO contact details.
2. Use the live preview and contrast checker to validate banner accessibility and styling choices while editing.
3. Bump the consent revision when you need returning visitors to reconfirm preferences.

### Maintain policies

1. Visit **Privacy & Cookie → Policy editor** to regenerate localized documents whenever detected services change.
2. Review the stale snapshot notice for quick access to regeneration tools.
3. Leverage the shortcode- and block-based outputs to inject policies into custom layouts.

### Monitor consent

1. Inspect **Privacy & Cookie → Consent log** for per-event breakdowns, filters, and CSV export.
2. Use REST or WP-CLI commands (`wp fp-privacy ...`) to audit state, reset tables, or export/import settings.
3. Dispatch custom triggers via `fp-consent-change` or `dataLayer` listeners in your front-end scripts.

## Hooks / Filters

- `fp_consent_update( $states, $event, $revision )`
- `fp_privacy_settings_imported( $settings )`
- `fp_privacy_policy_content`
- `fp_privacy_cookie_policy_content`
- `fp_privacy_services_registry`
- `fp_privacy_service_purpose_{key}`
- `fp_privacy_csv_export_batch_size`
- `fp_privacy_cookie_duration_days`
- `fp_privacy_cookie_options`
- `fp_privacy_detector_cache_ttl`
- `fp_privacy_enqueue_banner_assets`

## Support

- Documentation: see the [`docs/`](docs/) directory for overview, architecture notes, and FAQs.
- Issues & contact: [https://francescopasseri.com](https://francescopasseri.com)
- Builds: run `bash build.sh --bump=patch` to prepare a distributable ZIP without development artefacts.

## Changelog

Refer to [CHANGELOG.md](CHANGELOG.md) for release notes in Keep a Changelog format.

## Assumptions

No public issue tracker is bundled with the repository, so the support URL points to the author homepage for contact.
