# Architecture

## Core Modules

- `FP\Privacy\Plugin` wires all subsystems together, handles activation/deactivation, provisions multisite blogs, and keeps a singleton instance for shared services.
- `FP\Privacy\Utils\Options` centralises plugin settings, seeds default documents, ensures policy pages exist, and exposes typed getters for other components.
- `FP\Privacy\Integrations\DetectorRegistry` maps detectors for scripts, cookies, and known service signatures that feed the policy generator.
- `FP\Privacy\Admin\PolicyGenerator` composes localized privacy and cookie policies using templates under `templates/` and detector data.
- `FP\Privacy\Frontend\Banner` renders and boots the consent banner, delegating state management to `Frontend\ConsentState` and exposing the `fp-consent-change` event.

## Data Storage

- Consent events are stored in the `wp_fp_consent_log` table managed by `Consent\LogModel`, including hashed IP address, revision, language, and JSON consent states.
- Options are persisted in the standard WordPress options table via `Options::set()`; stored settings cover banner copy, palette, detection overrides, retention, and Consent Mode defaults.
- Generated privacy and cookie policies are saved as WordPress pages populated with shortcodes (`[fp_privacy_policy]`, `[fp_cookie_policy]`).

## Workflows

1. Activation (or multisite blog provisioning) calls `Plugin::setup_site()` which seeds default options, creates the consent table, primes the IP salt, and schedules the `fp_privacy_cleanup` cron.
2. Front-end requests enqueue scripts/styles for the banner, expose template tags/shortcodes, and emit Consent Mode signals via `Integrations\ConsentMode`.
3. Admin screens register via `Admin\Menu`, `Admin\Settings`, `Admin\PolicyEditor`, and `Admin\ConsentLogTable`, each relying on the shared `Options`, `PolicyGenerator`, and `LogModel` services.
4. Daily cleanup runs through `Consent\Cleanup`, purging log rows beyond the configured retention window.

## REST API & CLI

- `REST\Controller` registers endpoints under `fp-privacy/v1` for submitting consent (`POST /consent`), fetching summaries (`GET /consent/summary`), and bumping revisions (`POST /revision/bump`).
- `CLI\Commands` exposes `wp fp-privacy` subcommands for status inspection, table recreation, cleanup, export/import of settings, regeneration, and detector output.

## Blocks, Shortcodes, and Templates

- Gutenberg blocks live in `blocks/` and are registered by `Frontend\Blocks`, using ES5 modules to avoid build tooling.
- Shortcodes implemented in `Frontend\Shortcodes` wrap the same renderers used by blocks, enabling Classic theme compatibility.
- Twig-like PHP templates in `templates/` define the sections of generated policies and can be overridden via filters.

## Hooks and Filters

Key extensibility points include:

- `fp_consent_update` and `fp_privacy_csv_export_batch_size` for auditing and exporting consent.
- `fp_privacy_services_registry` and `fp_privacy_service_purpose_{key}` for altering detector metadata and generated copy.
- `fp_privacy_policy_content` / `fp_cookie_policy_content` for manipulating rendered documents.
- `fp_privacy_cookie_options`, `fp_privacy_cookie_duration_days`, and `fp_privacy_detector_cache_ttl` for low-level behaviour tuning.
