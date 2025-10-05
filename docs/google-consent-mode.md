# Google Consent Mode v2 compliance

This plugin implements a full Google Consent Mode v2 (GCMv2) integration out of the box. The consent helper that ships with the banner is automatically enqueued on the front end and keeps Google tags synchronized with the visitor's cookie decisions.

## Default signals

On every page load the plugin:

- registers the `fp-privacy-consent-mode` script and exposes the consent defaults to the front end (see `src/Integrations/ConsentMode.php`);
- emits a `gtag('consent', 'default', ...)` call that contains every GCMv2 key, including `ad_user_data` and `ad_personalization` (also in `src/Integrations/ConsentMode.php`);
- pushes a `gtm.init_consent` event to the dataLayer so Google Tag Manager can react to the initial consent snapshot (within the same integration class).

The defaults themselves are stored in the options table and already ship with the v2-specific consent keys. Administrators can override them from the settings screen, and any new key introduced by Google will fall back to the packaged defaults to prevent accidental omissions. Relevant code lives in `src/Utils/Options.php`, `src/Admin/Settings.php`, and `src/Utils/Validator.php`.

## Mapping banner decisions to GCMv2

When the visitor interacts with the banner:

- their category toggles are translated to the seven Google consent signals (`analytics_storage`, `ad_storage`, `ad_user_data`, `ad_personalization`, `functionality_storage`, `personalization_storage`, and `security_storage`);
- the normalized payload is delivered through `gtag('consent', 'update', ...)` and mirrored to the dataLayer with a dedicated `fp_consent_mode_update` event.

This behaviour is implemented in both the progressive enhancement bootstrap (`src/Frontend/Banner.php`) and the main banner bundle (`assets/js/consent-mode.js` and `assets/js/banner.js`), so it is available regardless of whether the banner renders immediately or only after the first user interaction.

## Integrating with your tags

You can now rely on Google Consent Mode v2 being present on every page where the plugin runs. All you need to do is:

1. ensure your Google tags are loaded after the plugin (or respect the consent state emitted to the dataLayer);
2. read the `fp_consent_mode_update` and `fp_consent_update` events if you need to react to consent changes in custom scripts.

No additional configuration is required to comply with the Google deadlines for Consent Mode v2.
