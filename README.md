# FP Privacy and Cookie Policy

> Provides a GDPR-ready consent banner, consent logging, and automated privacy/cookie policies with Google Consent Mode v2 for WordPress. Includes REST, WP-CLI, and Gutenberg tooling for privacy workflows.

## Plugin Info

| Key | Value |
| --- | --- |
| Name | FP Privacy and Cookie Policy |
| Version | 0.5.4 |
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

## ⚡ Quick Start - Generazione Automatica Policy

Genera automaticamente le pagine Privacy Policy e Cookie Policy in pochi secondi:

```bash
# Con WP-CLI (consigliato)
wp fp-privacy generate-pages --all-languages

# Senza WP-CLI
php bin/generate-policies.php --all-languages
```

Il sistema:
- ✅ Rileva automaticamente i servizi integrati (Google Analytics, Facebook Pixel, ecc.)
- ✅ Genera contenuto completo conforme a GDPR, ePrivacy Directive e linee guida EDPB 2025
- ✅ Crea/aggiorna le pagine WordPress automaticamente
- ✅ Supporta multilingua

📖 **[Guida Completa alla Generazione Automatica](QUICK-START-GENERAZIONE.md)** | **[Documentazione Dettagliata](docs/GENERAZIONE-AUTOMATICA.md)**

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

## WP-CLI Commands

Il plugin include comandi WP-CLI completi per automatizzare la gestione delle policy:

### Generazione Automatica Policy

```bash
# Genera le pagine Privacy e Cookie Policy
wp fp-privacy generate-pages [--all-languages] [--lang=<code>] [--force] [--bump-revision] [--dry-run]

# Esempi
wp fp-privacy generate-pages --all-languages
wp fp-privacy generate-pages --lang=it_IT --force
wp fp-privacy generate-pages --dry-run
```

### Altri Comandi

```bash
# Visualizza lo stato del sistema
wp fp-privacy status

# Rileva i servizi integrati
wp fp-privacy detect

# Esporta i log del consenso
wp fp-privacy export --file=consent-log.csv

# Esporta/Importa impostazioni
wp fp-privacy settings-export --file=settings.json
wp fp-privacy settings-import --file=settings.json

# Rigenerazione policy (solo output, senza salvare)
wp fp-privacy regenerate [--lang=<code>] [--bump-revision]

# Cleanup dei log
wp fp-privacy cleanup

# Ricrea la tabella del database
wp fp-privacy recreate [--force]
```

Per dettagli completi: `wp help fp-privacy <comando>`

## Hooks, filters & REST

### Actions

| Hook | Arguments | When |
| --- | --- | --- |
| `fp_consent_update` | `$states`, `$event`, `$revision` | After a consent event is stored (also other plugins listen for this). |
| `fp_privacy_settings_saved` | `$payload` | After settings are saved in admin. |
| `fp_privacy_settings_imported` | `$settings` (full options array) | After settings import (admin or WP-CLI). |
| `fp_privacy_snapshots_refreshed` | `$snapshots` | After policy snapshots refresh (CLI/admin flows). |
| `fp_privacy_auto_update_completed` | `$snapshots`, `$services` | After automatic policy/service update audit. |
| `fp_privacy_enqueue_banner_assets` | `$lang` | When banner scripts/styles are enqueued for a language. |
| `fp_privacy_admin_page_settings` | — | Inside Privacy & Cookie → Settings screen. |
| `fp_privacy_admin_page_policy_editor` | — | Policy editor screen. |
| `fp_privacy_admin_page_consent_log` | — | Consent log screen. |
| `fp_privacy_admin_page_tools` | — | Tools screen. |
| `fp_privacy_admin_page_analytics` | — | Analytics screen. |
| `fp_privacy_admin_page_guide` | — | Guide screen. |

### Filters

| Filter | Arguments | Notes |
| --- | --- | --- |
| `fp_privacy_consent_ids_for_email` | `$ids`, `$email`, `$options_context` | Map an email to consent IDs. `$options_context` is `FP\Privacy\Utils\Options` when invoked from privacy exporter/eraser; `null` from application export. Callbacks with 2 parameters remain supported. |
| `fp_privacy_cookie_duration_days` | `$days` | Cookie lifetime in days. |
| `fp_privacy_cookie_options` | `$options`, `$value`, `$id`, `$rev` | Cookie `setcookie` options array. |
| `fp_privacy_services_registry` | `$services` | Full service registry array. |
| `fp_privacy_custom_services` | `$custom` | Additional custom services. |
| `fp_privacy_force_enqueue_banner` | `bool` | Force banner assets on a request. |
| `fp_privacy_policy_content` | `$html`, `$lang` | Privacy policy HTML from shortcode/renderer. |
| `fp_cookie_policy_content` | `$html`, `$lang` | Cookie policy HTML (legacy filter name). |
| `fp_privacy_view_context` | `$context`, `$template` | Template rendering context. |
| `fp_privacy_enable_privacy_tools` | `bool`, `$options` | WP privacy tools integration. |
| `fp_privacy_enable_gpc` | `bool` | Global Privacy Control handling. |
| `fp_privacy_reject_all_confirm` | `bool` | Default `true`: mostra `window.confirm` prima di «Rifiuta tutti» sul banner. |
| `fp_privacy_reject_all_confirm_preview` | `bool` | In anteprima admin default `false` (nessun confirm); override qui se serve. |
| `fp_privacy_tracking_scanner_option_keys` | `$keys` | Option keys scanned for tracking signatures. |
| `fp_privacy_detector_cache_ttl` | `$ttl` | Detector cache TTL (seconds). |
| `fp_privacy_csv_export_batch_size` | `$batch` | Rows per CSV batch (CLI/export). |
| `fp_privacy_chartjs_src` | `$url` | Chart.js script URL (admin analytics). |
| `fp_privacy_service_purpose_{key}` | `$purpose`, `$locale` | Per-service purpose string (e.g. `fp_privacy_service_purpose_ga4`). |

Core WordPress: `plugin_locale` is used with text domain `fp-privacy` for translations.

### REST API (`fp-privacy/v1`)

| Method | Route | Access |
| --- | --- | --- |
| `GET` | `/consent/summary` | `manage_options` |
| `POST` | `/consent` | Public consent submission (permission/nonce handled in handler). |
| `POST` | `/consent/revoke` | Same as consent POST. |
| `POST` | `/revision/bump` | `manage_options` |
| `GET` | `/settings` | `manage_options` |
| `PATCH` / `PUT` | `/settings` | `manage_options` |

Base URL: `/wp-json/fp-privacy/v1`.

Implementazione interna: `POST /consent` e `POST /consent/revoke` usano `FP\Privacy\Presentation\REST\Controllers\ConsentController` (contratto `FP\Privacy\REST\ConsentRestHandlerInterface`). `RESTConsentHandler` resta solo fallback se il container non espone il controller.

## Support

- Integrazione frontend (`FP_PRIVACY_DATA`, costanti versione): [`docs/INTEGRATION-FRONTEND.md`](docs/INTEGRATION-FRONTEND.md)
- Roadmap release **1.0**: [`docs/RELEASE-1.0.md`](docs/RELEASE-1.0.md)
- Checklist QA pre-1.0: [`docs/QA-1.0.md`](docs/QA-1.0.md)
- Documentation: see the [`docs/`](docs/) directory for overview, architecture notes, and FAQs.
- Issues & contact: [https://francescopasseri.com](https://francescopasseri.com)
- Builds: run `bash build.sh --bump=patch` to prepare a distributable ZIP without development artefacts.

## Changelog

Refer to [CHANGELOG.md](CHANGELOG.md) for release notes in Keep a Changelog format.

## Assumptions

No public issue tracker is bundled with the repository, so the support URL points to the author homepage for contact.
---

## Autore

**Francesco Passeri**
- Sito: [francescopasseri.com](https://francescopasseri.com)
- Email: [info@francescopasseri.com](mailto:info@francescopasseri.com)
- GitHub: [github.com/franpass87](https://github.com/franpass87)
