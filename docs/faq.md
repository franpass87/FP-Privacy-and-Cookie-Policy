# Frequently Asked Questions

## Does the plugin rely on external SaaS services?
No. Everything runs inside WordPress with self-hosted scripts, so consent data never leaves your infrastructure unless you export it.

## How are IP addresses handled in the consent log?
IP addresses are hashed with a site-specific salt generated on activation (`fp_privacy_get_ip_salt()`) and stored without the clear value. The salt can be rotated by deleting the related option and letting the plugin regenerate it.

## Can I force a consent reset after updating policies?
Yes. Use the **Reset consent (bump revision)** action in **Privacy & Cookie → Settings** or run `wp fp-privacy regenerate --bump-revision` via WP-CLI to invalidate saved states and show the banner again.

## How can visitors reopen the preferences panel after closing the banner?
The front-end script injects a floating reopen button in the lower-left corner as soon as the banner finishes loading. It uses the same `data-fp-privacy-open` attribute as other launchers, so accessibility metadata stays in sync and the button can be restyled through the `.fp-privacy-reopen` class.

## How do I customise detected services or add bespoke trackers?
Hook into `fp_privacy_services_registry` to register additional services or override built-in detectors. Each service definition controls provider labels, cookie lists, and policy templates.

## Where are the generated privacy and cookie policies stored?
They are regular WordPress pages created during activation. You can edit them manually, but the policy editor screen lets you regenerate content while preserving shortcode placement.

## Is Google Consent Mode optional?
Yes. Consent Mode defaults are configured in the settings screen. If you disable Consent Mode, the banner still manages cookies and consent logging without calling `gtag`.

## Does it support Google Consent Mode v2?
Yes. The consent helper sets the required `ad_user_data` and `ad_personalization` signals alongside the standard storage keys, publishes both default and update events to `gtag`/`dataLayer`, and exposes helper hooks you can target from Google Tag Manager. See [docs/google-consent-mode.md](google-consent-mode.md) for a detailed walkthrough.

## Can I surface consent data in external dashboards?
Use the REST endpoint `GET /wp-json/fp-privacy/v1/consent/summary` or the CSV export tool to feed external analytics. The `fp_consent_update` action also exposes every change for custom integrations.

## How do I translate the banner and policies?
Define languages inside the settings screen, provide translated copy for each field, and leverage the bundled POT file. All front-end strings use the `fp-privacy` text domain for compatibility with Loco Translate and similar tools.
