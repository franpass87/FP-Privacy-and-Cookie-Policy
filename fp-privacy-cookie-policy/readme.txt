=== FP Privacy and Cookie Policy ===
Contributors: fpdigitalassistant
Tags: gdpr, cookie banner, consent management, privacy policy, google consent mode
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modern GDPR-ready consent management platform for WordPress, with automatic Google Consent Mode v2 support and bilingual workflows tailored for Italian websites.

== Description ==
FP Privacy and Cookie Policy helps agencies and professionals implement a complete consent workflow while keeping the editorial experience inside WordPress. The plugin ships with a responsive cookie banner, multilingual texts (Italian/English), granular categories, consent logging and CSV export to stay compliant with GDPR and Google requirements.

* Responsive cookie banner with "Accept", "Reject" and "Preferences" actions.
* Automatic language detection (Italian/English) based on browser and site locale.
* Granular cookie categories (necessary, preferences, statistics, marketing) with description of services used.
* Visual editor for privacy and cookie policy texts and dedicated shortcodes.
* Consent registry with anonymised IP address, AJAX logging and CSV export.
* Native Google Consent Mode v2 integration and `dataLayer` events to orchestrate your tracking setup.
* WordPress privacy tools integration to export or delete consent logs per-user on request.

== Installation ==
1. Upload the `fp-privacy-cookie-policy` folder to the `/wp-content/plugins/` directory, or install the plugin from the WordPress plugin screen.
2. Activate the plugin through the "Plugins" screen in WordPress.
3. Upon activation the consent log table is created automatically.

== Frequently Asked Questions ==

= Posso personalizzare i testi del banner? =
Sì. Tutte le etichette e i messaggi possono essere personalizzati e tradotti in inglese dalla schermata delle impostazioni.

= Il plugin supporta Google Consent Mode v2? =
Certo. Il banner imposta i valori di default, aggiorna automaticamente i segnali `gtag('consent', 'update')` e pubblica l'evento `fp_consent_update` sul `dataLayer` per strumenti avanzati.

= Come posso dimostrare il consenso? =
Usa la tab "Registro consensi" per consultare gli eventi registrati ed esportare l'intero archivio in formato CSV.

== Screenshots ==
1. Banner cookie responsive con pulsanti principali e link alle preferenze.
2. Modal delle preferenze con categorie e descrizioni personalizzabili.
3. Registro consensi con esportazione CSV e informazioni anonimizzate.

== Changelog ==
= 1.2.0 =
* Added integration with WordPress privacy exporter/eraser tools to manage consent logs per user.
* Fixed the consent banner script initialisation so it always runs even if the DOM is already loaded.
* Hardened cookie handling with SameSite=Lax and stricter validation of Google Consent Mode defaults.

= 1.1.0 =
* Added production-ready assets (translation files, readme and directory index placeholders).
* Introduced English localisation and updated plugin metadata for WordPress.org compliance.
* Improved consent identifier handling for a consistent UX.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==
= 1.2.0 =
Regenerate il pacchetto dopo l'aggiornamento per beneficiare dell'integrazione con gli strumenti di esportazione/cancellazione dati di WordPress e del fix allo script del banner.

= 1.1.0 =
Make sure to regenerate your banner texts if you want to take advantage of the new English localisation.
