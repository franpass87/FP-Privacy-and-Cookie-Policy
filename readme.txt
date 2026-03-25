=== FP Privacy and Cookie Policy ===
Contributors: francescopasseri
Tags: privacy, cookies, consent, gdpr, consent mode
Requires at least: 6.2
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.1.3
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


= 1.1.3 =
* Changed: banner e modal cookie — meno spazio verticale (padding tab/pannelli/bottoni, altezza pannello tab).

= 1.1.2 =
* Changed: pagine Privacy/Cookie salvano solo shortcode — tabelle servizi sempre aggiornate al detector ad ogni visita; rigenerazione/CLI/script allineati.
* Fixed: template — niente tabelle solo intestazioni per categorie senza servizi.

= 1.1.1 =
* Fixed: salvataggio da tab — disabilita submit e spinner anche per `.fp-privacy-btn` e pulsanti “Save all” fuori dal form (`form=` attribute).

= 1.1.0 =
* Added: AdminUi submit FP-style; CSS design system (fields-grid, alert, input focus, diagnostica).
* Changed: niente submit_button/hr/inline su admin principale; diagnostica in card FP; pannelli legacy e tabella rilevati allineati DS.

= 1.0.36 =
* Changed: navigazione sezioni nel menu laterale WordPress (sottopagine); barra orizzontale solo con filtro `fp_privacy_collapse_admin_submenus` = true (legacy).

= 1.0.35 =
* Fixed: pagine admin — niente scroll orizzontali su subnav/tab; tab a capo su mobile; editor policy contenuto nella larghezza.

= 1.0.34 =
* Changed: banner titolo admin come FP Mail SMTP (padding, tipografia, badge, senza ombra sul box).

= 1.0.33 =
* Fixed: box titolo admin (banner gradiente) conforme al design system FP (padding, tipografia h2, ombra testo, descrizione, badge versione).

= 1.0.32 =
* Changed: grafica admin allineata a FP Mail SMTP (pulsanti, header, card, tab, sticky save, filtri, accordion); breadcrumb e notice; barra “Save all settings” con icona.

= 1.0.31 =
* Changed: sezioni impostazioni collassabili con markup PHP stabile (`data-fp-section`); stili FP; L10n filtri tabella rilevati e toolbar expand/collapse.

= 1.0.30 =
* Added: filtro `fp_privacy_collapse_admin_submenus` — menu WP senza sottovoci duplicate (navigazione via subnav); breadcrumb sopra i tab impostazioni.
* Changed: Quick actions snellite; tab impostazioni e stringhe admin JS con msgid inglesi; CSS tab/subnav e mobile.

= 1.0.29 =
* Fixed: PHPStan livello configurato — posizione menu `add_menu_page` come float, PHPDoc `@param mixed` sui blocchi Gutenberg, rimozione check ridondanti (`FooterPolicyLinks`, `BannerTextsManager`, subnav).
* Changed: `tools/verify-local.ps1` include smoke HTTP su `/privacy-policy/` e `/cookie-policy/`.

= 1.0.28 =
* Added: script CLI `tools/regenerate-policy-pages-wp.php` per rigenerare le policy senza WP-CLI; rigenera anche le cookie mappate solo per lingue extra (es. `en`/`en_US`) senza forzare `languages_active`.
* Added: `tools/fix-policy-pages-option-mapping.php` per ripristinare gli ID pagina in `fp_privacy_options` (uso manuale).

= 1.0.27 =
* Added: script CLI `tools/cleanup-policy-pages-slugs.php` per pulizia slug policy duplicate (uso manuale, vedi CHANGELOG).

= 1.0.26 =
* Fixed: cookie policy in inglese — titoli e label tabella come privacy (testo EN letterale se lang è en_*).

= 1.0.25 =
* Fixed: sommario policy — rimosso doppio escape HTML sulle voci del TOC.

= 1.0.24 =
* Fixed: policy in inglese — titoli sommario, h2, tabella servizi e “Last updated” in inglese letterale quando `$lang` è en_* (evita WPML/gettext ancora su it_IT).

= 1.0.23 =
* Changed: titoli privacy policy con msgid in inglese (sommario coerente in EN anche se il .mo non carica).
* Fixed: cataloghi it_IT/en_US e file .mo aggiornati.

= 1.0.22 =
* Fixed: errore critico possibile su pagine policy se un filtro su `fp_privacy_view_context` lancia eccezione; ripristino lingua in `PolicyGenerator` non propaga più errori dal `finally`.

= 1.0.21 =
* Fixed: traduzioni en_US (PO + MO) per titoli/sommario policy — niente più testo italiano quando la lingua attiva è en_US.

= 1.0.20 =
* Fixed: salvataggio cache traduzioni categorie senza ensure_pages_exist durante il render policy (evita errori critici con shortcode en_US/en_GB e WPML).
* Changed: Composer `platform-check: false` + autoload senza `platform_check.php` (deploy/vendor parziali non bloccano il bootstrap).

= 1.0.19 =
* Fixed: locale `en_EN` negli shortcode normalizzato a `en_US` (locale WP valido).

= 1.0.18 =
* Fixed: vendor/composer/platform_check.php incluso nel repo (fix require autoload in produzione).

= 1.0.17 =
* Fixed: Hardening generazione policy (contesto vista, palette, testi AI, template, lingua); try/catch shortcode privacy/cookie.

= 1.0.16 =
* Fixed: Errore critico PHP 8+ su privacy policy — attributi blocco Gutenberg nulli normalizzati; template policy salta voci servizi non-array.

= 1.0.15 =
* Fixed: Subnav/link Diagnostica solo se voce menu presente; sticky save button WP classes.
* Changed: Consent log filtri in card; Analytics tabella in card + fp-privacy-table; filtro fp_privacy_admin_subnav_items.

= 1.0.14 =
* Changed: Admin ristrutturato (menu, subnav FP, card, body class, enqueue); rimosso Menu.php duplicato in Presentation.

= 1.0.12 =
* Fixed: Tab Info con UI italiana ma testo Info ancora in inglese — rilevamento paragrafo standard EN e sostituzione con IT (backend + JS).

= 1.0.11 =
* Fixed: Tab Info - override about_content in ConsentState (backend) per garantire dato corretto in FP_PRIVACY_DATA.

= 1.0.10 =
* Fixed: Tab Info - fallback robusto in buildBanner() per testo breve deprecato.

= 1.0.9 =
* Fixed: Tab Info del banner - mostra sempre il testo standard completo invece del vecchio testo breve deprecato (migrazione backend + fallback JS IT/EN).

= 1.0.8 =
* Menu position 56.8 per ordine alfabetico FP.

= 1.0.7 =
* Changed: Menu WordPress - titolo "FP Privacy & Cookie" (allineamento naming con altri plugin FP).

= 1.0.6 =
* Fixed: tutti gli error_log (LogModelTable, ConsentTable, Cleanup) condizionati a WP_DEBUG per evitare output debug in produzione.

= 1.0.5 =
* Fixed: uniformata la lingua IT/EN tra banner/modal e pagine policy generate, con categorie core coerenti per locale e traduzioni `.mo` aggiornate.

= 1.0.4 =
* Fixed: mantenuta la visibilita' del pulsante flottante "Gestisci preferenze" anche dopo consenso, con riapertura del modal sempre disponibile fuori da preview mode.

= 0.3.3 =
* Dev: PHPStan livello 5 esteso a `src/Consent`; fix analisi statica su `LogModel` / `LogModelTable`.

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
