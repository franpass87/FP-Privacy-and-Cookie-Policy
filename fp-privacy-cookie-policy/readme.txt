=== FP Privacy and Cookie Policy ===
Contributors: francescopasseri
Tags: privacy, cookies, consent, gdpr, consent mode
Requires at least: 6.2
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Provides a GDPR-ready consent banner, consent logging, and automated privacy/cookie policies with Google Consent Mode v2 for WordPress. Includes REST, WP-CLI, and Gutenberg tooling for privacy workflows.

== Description ==

Provides a GDPR-ready consent banner, consent logging, and automated privacy/cookie policies with Google Consent Mode v2 for WordPress. Includes REST, WP-CLI, and Gutenberg tooling for privacy workflows.

The plugin combines privacy notice automation with a fully accessible cookie banner, granular consent storage, Google Consent Mode v2, dataLayer hooks, and developer tooling. It is build-tool free (pure ES5) and supports multisite installs, CSV exports, REST endpoints, and WP-CLI commands.

= Highlights =

* GDPR-friendly banner with floating/bar layouts, palette syncing, preview mode, revision notice, and accessible modal controls.
* Consent registry with hashed IP, retention policies, CSV exports, and 30-day summaries.
* Auto-detected services (GA4, GTM, Meta Pixel, Hotjar, reCAPTCHA, YouTube, Matomo, TikTok, etc.) feeding localized privacy/cookie policy templates.
* Google Consent Mode v2 defaults/updates plus `fp-consent-change` CustomEvent and `dataLayer` push.
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

= 0.1.1 =
* Improved banner bootstrapping for shortcode placements, strengthened accessibility guards, and ensured Consent Mode defaults fire even when `gtag` is asynchronous.
* Hardened consent logging by filtering unknown categories, honoring locked toggles, surfacing REST errors, and exposing filters for cookie attributes and IP salt storage.
* Reused cached detector results for localized policy generation, grouped manual services with translated labels, and expanded embed detection in admin/CLI contexts with configurable cache TTL.

= 0.1.0 =
* Initial alpha release with banner, consent registry, CSV export, policy detector/generator, WP-CLI, REST API, Google Consent Mode v2, shortcodes, Gutenberg blocks, multisite support, and i18n.

== Upgrade Notice ==

= 0.1.1 =
Review banner behavior if you rely on shortcode placement and confirm consent settings after the hardened logging/filters.

= 0.1.0 =
First release. Configure banner settings and review generated policies after upgrading.
