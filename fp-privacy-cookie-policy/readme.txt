=== FP Privacy and Cookie Policy ===
Contributors: francescopasseri
Tags: gdpr, cookie banner, consent management, privacy policy, google consent mode
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A modern GDPR-ready consent management platform for WordPress, with automatic Google Consent Mode v2 support and bilingual workflows tailored for Italian websites.

== Description ==
FP Privacy and Cookie Policy helps agencies and professionals implement a complete consent workflow while keeping the editorial experience inside WordPress. The plugin ships with a responsive cookie banner, multilingual texts (Italian/English), granular categories, consent logging and CSV export to stay compliant with GDPR and Google requirements.

= Highlights =
* Responsive cookie banner with "Accept", "Reject" and "Preferences" actions.
* Automatic language detection (Italian/English) based on browser and site locale.
* Granular cookie categories (necessary, preferences, statistics, marketing) with description of services used.
* Visual editor for privacy and cookie policy texts and dedicated shortcodes (`[fp_privacy_policy]`, `[fp_cookie_policy]`, `[fp_cookie_preferences]`).
* Consent registry with anonymised IP address, AJAX logging and CSV export.
* Contextual indicator showing the last consent update directly next to the manage button.
* Native Google Consent Mode v2 integration and `dataLayer` events to orchestrate your tracking setup.
* Configurable consent log retention with automatic cleanup and integration with the WordPress privacy export/erase tools.
* Adjustable consent cookie duration to align banner re-consent with your legal requirements.
* First-class WP-CLI commands to monitor the consent log, recreate the table, trigger cleanups and export CSV snapshots.

= Why it matters =
* Keep the entire consent lifecycle inside WordPress without relying on external dashboards.
* Offer bilingual experiences for Italian and international audiences out of the box.
* Demonstrate accountability through a searchable log and scheduled retention policies.
* Integrate with Google Tag Manager, gtag.js and custom scripts using consent events and hooks.

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
Usa la tab "Registro consensi" per consultare gli eventi registrati ed esportare l'intero archivio in formato CSV. Puoi inoltre sfruttare gli strumenti di esportazione/cancellazione dati di WordPress per rispondere alle richieste degli interessati.

== WP-CLI ==
The plugin ships with a dedicated command namespace to automate maintenance tasks:

* `wp fp-privacy status` — displays the consent table health, stored events and the next scheduled cleanup.
* `wp fp-privacy recreate [--force]` — recreates the consent log table and restores the cleanup schedule without accessing wp-admin.
* `wp fp-privacy cleanup` — runs the retention cleanup immediately while respecting your configuration.
* `wp fp-privacy export --file=consents.csv` — saves the entire consent log to a CSV file optimised for large datasets.

== Supporto e contatti ==
Il plugin è mantenuto da [Francesco Passeri](https://francescopasseri.com/). Per richieste professionali, personalizzazioni o supporto contatta [info@francescopasseri.com](mailto:info@francescopasseri.com).

== Screenshots ==
1. Banner cookie responsive con pulsanti principali e link alle preferenze.
2. Modal delle preferenze con categorie e descrizioni personalizzabili.
3. Registro consensi con esportazione CSV e informazioni anonimizzate.

== Changelog ==
= 1.6.0 =
* Added full multisite support with network-aware activation, cleanup and uninstall routines.
* Automatically bootstrap the consent registry when new sites are created on a network.

= 1.5.3 =
* Added proactive environment checks to disable the plugin when PHP or WordPress do not meet the minimum requirements.

= 1.5.2 =
* Updated all documentation (README, readme.txt, changelog) to provide a complete timeline of the changes shipped across releases.
* Set Francesco Passeri as the official maintainer with updated contact details.

= 1.5.1 =
* Added the `wp fp-privacy recreate` command to rebuild the consent log table and reschedule the cleanup cron job from the terminal.

= 1.5.0 =
* Added WP-CLI commands to check the consent table health, trigger manual cleanups and export CSV snapshots without accessing the admin panel.
* Documented the new automation workflow and bumped internal version metadata for the next stable release.

= 1.4.0 =
* Added a live consent update indicator with tooltip and time metadata stored alongside the consent state.
* Improved dataLayer pushes and custom events by reusing the recorded timestamp for analytics consistency.
* Enhanced accessibility by exposing the last update time via `aria-describedby` on the manage preferences button.

= 1.3.2 =
* Aligned the consent identifier cookie lifetime with the configured consent duration and exposed filters for advanced tuning.
* Ensured the consent identifier cookie inherits secure defaults, including the SameSite=Lax directive.

= 1.3.1 =
* Optimised the consent log CSV export to stream large datasets in configurable batches, preventing memory exhaustion on production environments.
* Added the `fp_privacy_csv_export_batch_size` filter to fine-tune the number of rows exported per batch when needed.

= 1.3.0 =
* Added a dedicated control to configure the lifetime of the consent cookie, with safeguards and filters for advanced workflows.
* Updated the frontend script to respect the configured consent lifetime while keeping secure defaults in place.

= 1.2.0 =
* Added retention controls for the consent registry with daily scheduled cleanup.
* Integrated the consent logs with WordPress personal data exporter and eraser workflows.
* Hardened the admin tools when the consent log table is missing or needs to be recreated.

= 1.1.0 =
* Added production-ready assets (translation files, readme and directory index placeholders).
* Introduced English localisation and updated plugin metadata for WordPress.org compliance.
* Improved consent identifier handling for a consistent UX.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==
= 1.6.0 =
Se attivi il plugin a livello di network, le tabelle e gli eventi di pulizia vengono ora creati automaticamente per ogni sito, incluse le nuove installazioni.

= 1.5.3 =
Assicurati che l'installazione soddisfi i requisiti minimi (PHP 7.4, WordPress 6.0) per evitare la disattivazione automatica del plugin.

= 1.5.2 =
Rivedi la documentazione aggiornata e sostituisci eventuali riferimenti al vecchio maintainer con i nuovi contatti.

= 1.5.1 =
Recreate the consent table from the terminal with `wp fp-privacy recreate` if you need to restore the consent registry after database maintenance.

= 1.5.0 =
Automate maintenance from the terminal with the new WP-CLI commands to monitor, export and clean consent logs.

= 1.4.0 =
Refresh any cached banner markup to surface the new consent status badge and timestamp metadata.

= 1.2.0 =
Review the new consent log retention setting and schedule to align it with your data governance policies.

= 1.1.0 =
Make sure to regenerate your banner texts if you want to take advantage of the new English localisation.
