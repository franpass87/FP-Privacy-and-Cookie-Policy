=== FP Privacy and Cookie Policy ===
Contributors: francescopasseri
Tags: privacy, cookies, consent, gdpr, consent mode
Requires at least: 6.2
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive GDPR/ePrivacy consent manager with Google Consent Mode v2, auto-generated privacy/cookie policies, consent logging, REST + WP-CLI tools, and Gutenberg blocks (no build step).

== Description ==

FP Privacy and Cookie Policy combines privacy notice automation with a fully accessible cookie banner, granular consent storage, Google Consent Mode v2, dataLayer hooks, and developer tooling. The plugin is build-tool free (pure ES5) and supports multisite installs, CSV exports, REST endpoints, and WP-CLI commands.

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

IP addresses are hashed with a constant salt (`FP_PRIVACY_IP_SALT`). No clear IP data is stored.

= Can I customize detected services? =

Yes. Use the `fp_privacy_services_registry` filter to add or override detection callbacks, providers, and cookie lists.

= How do I force users to reconfirm consent? =

Use the "Reset consent (bump revision)" button in **Privacy & Cookie → Settings** or call `wp fp-privacy regenerate --bump-revision` via WP-CLI. Increasing the revision invalidates stored consent and re-triggers the banner.

= Where do I find GTM examples? =

See the quick guide screen inside the plugin and the repository `README.md` for sample dataLayer usage and Consent Mode integration notes.

= How do I build a distributable ZIP? =

Run `bin/package.sh` from the repository root. The script produces a clean archive under `dist/` without minified or binary artefacts.

== Changelog ==

= 0.1.0 =
* Initial alpha release with banner, consent registry, CSV export, policy detector/generator, WP-CLI, REST API, Google Consent Mode v2, shortcodes, Gutenberg blocks, multisite support, and i18n.

== Upgrade Notice ==

= 0.1.0 =
First release. Configure banner settings and review generated policies after upgrading.
