# Changelog

## 0.1.0 — Initial alpha release
- First public release with GDPR cookie banner, granular categories, consent registry (CSV export + retention), live palette preview, Google Consent Mode v2 integration, `fp-consent-change` CustomEvent, service detection with automatic privacy/cookie policy generation, shortcodes, Gutenberg blocks (ES5), WP-CLI commands, multisite provisioning, i18n, REST API, exporter/eraser, and cron-based cleanup.
- Added Consent Mode bootstrap fallback to `dataLayer` for asynchronous tag managers.
- Enriched `fp_consent_update` payloads with timestamps and consent identifiers to match audit requirements.
- Introduced admin banner preview with live contrast checks plus stale snapshot notice linking to policy tools.
