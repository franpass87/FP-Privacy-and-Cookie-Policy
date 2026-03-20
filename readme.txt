=== FP Privacy and Cookie Policy ===
Contributors: francescopasseri
Tags: privacy, cookies, consent, gdpr, consent mode
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0-rc.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Provides a GDPR-ready consent banner, consent logging, and automated privacy/cookie policies with Google Consent Mode v2 for WordPress. Includes REST, WP-CLI, and Gutenberg tooling for privacy workflows.

== Description ==

Provides a GDPR-ready consent banner, consent logging, and automated privacy/cookie policies with Google Consent Mode v2 for WordPress. Includes REST, WP-CLI, and Gutenberg tooling for privacy workflows.

The plugin combines privacy notice automation with a fully accessible cookie banner, granular consent storage, Google Consent Mode v2, dataLayer hooks, and developer tooling. It is build-tool free (pure ES5) and supports multisite installs, CSV exports, REST endpoints, and WP-CLI commands.

Visitors can always revisit their decisions via the built-in floating reopen button, and the generated policies include expanded GDPR-aligned sections updated for October 2025 guidance.

= Highlights =

* GDPR-friendly banner with floating/bar layouts, palette syncing, preview mode, revision notice, accessible modal controls, and a discreet reopen button for cookie preferences.
* Consent registry with hashed IP, retention policies, CSV exports, and 30-day summaries.
* Auto-detected services (GA4, GTM, Meta Pixel, Hotjar, reCAPTCHA, YouTube, Matomo, TikTok, etc.) feeding localized privacy/cookie policy templates with GDPR-aligned sections (definitions, legal bases, safeguards, breach handling, and more).
* Google Consent Mode v2 defaults/updates plus `fp-consent-change` CustomEvent, `dataLayer` push, and detailed documentation in `docs/google-consent-mode.md`.
* Shortcodes, template tags, and four Gutenberg blocks (Privacy Policy, Cookie Policy, Preferences Button, Cookie Banner).
* WP-CLI: status, recreate, cleanup, CSV export, settings import/export, detector, policy regeneration.
* REST API namespace `fp-privacy/v1` with endpoints for consent submission and summaries.
* Multisite support with automatic provisioning on activation and `wpmu_new_blog`.
* Extensive hooks (`fp_consent_update`, `fp_privacy_services_registry`, etc.) and filters for customization.

= Developer Hooks =

* `fp_privacy_cookie_duration_days` – Change the consent cookie lifespan.
* `fp_privacy_cookie_options` – Filter cookie attributes before they are written.
* `fp_privacy_detector_cache_ttl` – Override the detector cache expiry time.
* `fp_privacy_enqueue_banner_assets` – Force banner assets to load when rendering via shortcode.

== Installation ==

1. Upload the `fp-privacy-cookie-policy` directory to `/wp-content/plugins/`.
2. Activate through the **Plugins** menu (or network activate on multisite).
3. Configure languages, banner content, palette, Consent Mode defaults, and controller/DPO details under **Privacy & Cookie → Settings**.
4. Use the live preview panel and contrast warning on the settings screen to validate copy and palettes as you edit.
5. Review the stale snapshot notice that links to the Tools tab whenever generated policies are older than two weeks.
6. Review/regenerate the privacy and cookie policies via **Privacy & Cookie → Policy editor**.
7. Monitor events in **Privacy & Cookie → Consent log** and export CSVs if required.

== Frequently Asked Questions ==

= Does the banner log IP addresses? =

IP addresses are hashed with a site-specific salt stored per installation. No clear IP data is stored.

= Can I customize detected services? =

Yes. Use the `fp_privacy_services_registry` filter to add or override detection callbacks, providers, and cookie lists.

= How do I force users to reconfirm consent? =

Use the "Reset consent (bump revision)" button in **Privacy & Cookie → Settings** or call `wp fp-privacy regenerate --bump-revision` via WP-CLI. Increasing the revision invalidates stored consent and re-triggers the banner.

= Where do I find GTM examples? =

See the quick guide screen inside the plugin and the repository `README.md` for sample dataLayer usage and Consent Mode integration notes.

= How do I build a distributable ZIP? =

Run `bin/package.sh` from the repository root. The script produces a clean archive under `dist/` without minified or binary artefacts.

== Changelog ==

= 1.0.0-rc.1 =
* Release candidate verso 1.0.0 (feature freeze).

= 0.5.12 =
* Docs: roadmap 1.0 (PHPUnit, contratto hook/REST); QA-1.0 link a RELEASE.

= 0.5.11 =
* Dev: PHPStan path unico `src` in `phpstan.neon.dist`; roadmap release 1.0 aggiornata.

= 0.5.10 =
* PHPStan Core/Services/CLI/Integrations; fix static analysis.

= 0.5.9 =
* PHPStan src/Presentation, stub WP-CLI, fix REST/CLI Presentation.

= 0.5.8 =
* PHPStan src/Admin; fix diagnostica, settings, policy editor, DI container.

= 0.5.7 =
* PHPStan su `src/Consent`; fix analisi statica `LogModel` / `LogModelTable`. 

= 0.5.6 =
* PHPStan su src/Infrastructure; bootstrap ARRAY_A/ARRAY_N; fix MultisiteManager, ConsentTable, HttpClientInterface.

= 0.5.5 =
* PHPStan su src/Frontend; bootstrap costanti WP/plugin; piccoli refactor DI Shortcodes (senza View inutilizzato).

= 0.5.4 =
* Verso 1.0: checklist QA `docs/QA-1.0.md`; PHPStan su `src/Utils` + bootstrap costanti; fix static analysis (Options, PageManager, Logger, View, …).

= 0.5.3 =
* Roadmap 1.0: documento RELEASE-1.0 aggiornato; PHPStan esteso a src/Providers; piccoli fix static analysis in CoreServiceProvider.

= 0.5.2 =
* i18n: traduzioni EN complete per policy lunga e admin (granularità EDPB); `.mo` aggiornati; tooling in `bin/` per rigenerare chunk.

= 0.5.1 =
* Consent Mode aggiornato prima dello sblocco script (fix eventi marketing/analytics). UX: rifiuta secondario + conferma opzionale (filtri PHP); testo reject_all_confirm.

= 0.5.0 =
* UI admin allineata al design system FP (header viola, card, pulsanti); frontend banner/policy con struttura FP e colori dalla palette; anteprima admin con palette inline; variabili CSS sulla pagina policy.

= 0.4.4 =
* Reset impostazioni ai default (admin-post sicuro); documento integrazione frontend FP_PRIVACY_DATA; PHPStan su Application; bozza sezione upgrade 1.0 in CHANGELOG.

= 0.4.3 =
* PHPStan: analisi estesa a `src/Domain`; fix condizioni in `AIDisclosureGenerator`; test `RESTPermissionChecker::is_same_origin`; stub `wp_parse_url` nel bootstrap PHPUnit.

= 0.4.2 =
* Dev: bootstrap PHPUnit con stub WP minime; test ColorPalette/ServiceRegistry/OptionsValidator allineati; `composer test` verde.

= 0.4.1 =
* REST: route consent/revoke su ConsentController + ConsentRestHandlerInterface; RESTConsentHandler solo fallback.

= 0.4.0 =
* Roadmap 1.0 in docs/RELEASE-1.0.md; ServiceRegistry Integrations → facade su Domain; limite retry init banner JS.

= 0.3.5 =
* Changed: filtro `fp_privacy_consent_ids_for_email` con terzo argomento opzionale documentato; README con hook/filtri/REST in tabella.

= 0.3.4 =
* Fixed: revoca consenso — aggiornamento immediato banner/reopen; merge revisione cookie con last_revision stringa dal server.

= 0.3.3 =
* Fixed: bottone flottante preferenze cookie — riallineamento stato UI dopo timeout/errore; merge revisione cookie con dato server per evitare incoerenze.

= 0.3.2 =
* Fixed: silenziati i log/warn di debug in console in produzione per banner e analytics (attivi solo con flag debug).

= 0.3.0 =
* Raised minimum PHP to 8.0 and WordPress to 6.2 for February 2026 compatibility.
* Improved service detection: pattern-based fallback (TrackingPatternScanner) so privacy/cookie policies populate when analytics (GA4, GTM, Meta Pixel, Hotjar, Clarity, etc.) are loaded via theme options or content.
* Fixed additional services not loading: added AdditionalServicesConfig.php so LinkedIn, TikTok, Matomo, Pinterest, HubSpot, WooCommerce and embed services are registered.
* Hardened detector config loading (try/catch, is_readable) and TrackingPatternScanner (safe json_encode, skip empty values).

= 0.2.0 =
* Added WordPress Color Picker for professional color palette management
* Enhanced live preview with Desktop/Mobile toggle
* Added Analytics Dashboard with Chart.js integration
* Improved UX admin interface

= 0.1.2 =
* Documented the Google Consent Mode v2 helper defaults/updates across the handbook and linked the dedicated implementation guide.
* Publicised the floating reopen preferences button and refreshed accessibility attributes in the UX documentation.
* Expanded privacy/cookie policy template descriptions to mirror the October 2025 GDPR guidance baked into the generator.
* Fixed: Secured input handling in settings controller to properly manage both string and array inputs for language configuration.
* Fixed: Improved hash generation in auto-translator with secure fallback when JSON encoding fails, preventing cache collisions.

= 0.1.1 =
* Improved banner bootstrapping for shortcode placements, strengthened accessibility guards, and ensured Consent Mode defaults fire even when `gtag` is asynchronous.
* Hardened consent logging by filtering unknown categories, honoring locked toggles, surfacing REST errors, and exposing filters for cookie attributes and IP salt storage.
* Reused cached detector results for localized policy generation, grouped manual services with translated labels, and expanded embed detection in admin/CLI contexts with configurable cache TTL.

= 0.1.0 =
* Initial alpha release with banner, consent registry, CSV export, policy detector/generator, WP-CLI, REST API, Google Consent Mode v2, shortcodes, Gutenberg blocks, multisite support, and i18n.

== Upgrade Notice ==

= 0.3.0 =
Requires PHP 8.0+ and WordPress 6.2+. Improved policy detection and additional services loading. Recommended update.

= 0.2.0 =
New features: Color picker, enhanced preview, and analytics dashboard. Recommended update for all users.

= 0.1.2 =
Security improvements: enhanced input validation and cache hash generation. Recommended update for all users.

= 0.1.1 =
Review banner behavior if you rely on shortcode placement and confirm consent settings after the hardened logging/filters.

= 0.1.0 =
First release. Configure banner settings and review generated policies after upgrading.
