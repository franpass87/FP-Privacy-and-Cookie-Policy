# Changelog

## Unreleased
- Restored the consent banner to prioritize shortcode containers, wire external preference triggers, and guard the modal UI for assistive technologies while bootstrapping saved consent states via the lightweight Consent Mode helper.
- Hardened consent persistence by filtering unknown categories, enforcing locked toggles, surfacing REST failures, and exposing a cookie attribute filter alongside a site-specific IP hash salt and uninstall cleanup routine.
- Reused cached detector output for policy generation, merged manual services into grouped tables with localized labels, and expanded embed detectors to work in admin/CLI contexts with configurable cache TTL handling.

## 0.1.0 — Initial alpha release
- First public release with GDPR cookie banner, granular categories, consent registry (CSV export + retention), live palette preview, Google Consent Mode v2 integration, `fp-consent-change` CustomEvent, service detection with automatic privacy/cookie policy generation, shortcodes, Gutenberg blocks (ES5), WP-CLI commands, multisite provisioning, i18n, REST API, exporter/eraser, and cron-based cleanup.
- Added Consent Mode bootstrap fallback to `dataLayer` for asynchronous tag managers.
- Enriched `fp_consent_update` payloads with timestamps and consent identifiers to match audit requirements.
- Introduced admin banner preview with live contrast checks plus stale snapshot notice linking to policy tools.
