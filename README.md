# FP Privacy and Cookie Policy

Plugin WordPress progettato per gestire privacy policy, cookie policy e consenso informato nel rispetto del GDPR, con particolare attenzione alle esigenze dei siti italiani e all'integrazione del Google Consent Mode v2.

## Funzionalità principali

- Banner cookie responsive con pulsanti "Accetta", "Rifiuta" e accesso rapido alle preferenze.
- Localizzazione automatica italiano/inglese del banner e delle categorie in base alla lingua del browser.
- Gestione delle categorie di cookie (necessari, preferenze, statistiche, marketing) e descrizione dei servizi utilizzati.
- Editor WYSIWYG per i testi di privacy e cookie policy e shortcodes dedicati.
- Registro consensi con anonimizzazione IP, esportazione in CSV e conservazione degli eventi di scelta.
- Integrazione automatica con Google Consent Mode v2 (`analytics_storage`, `ad_storage`, `ad_user_data`, `ad_personalization`, `functionality_storage`, `security_storage`).
- Supporto per Google Tag Manager/eventi personalizzati via `dataLayer` e custom event `fp-consent-change`.
- Traduzioni `en_US` pronte all'uso e file `.pot` per localizzazioni aggiuntive.
- Integrazione con gli strumenti privacy di WordPress per esportare o cancellare i log dei consensi su richiesta degli utenti.

## Installazione

1. Copia la cartella `fp-privacy-cookie-policy` all'interno di `wp-content/plugins/`.
2. Accedi alla bacheca WordPress e attiva **FP Privacy and Cookie Policy** dal menu "Plugin".
3. Alla prima attivazione viene creata automaticamente la tabella del registro consensi.

## Configurazione rapida

1. Vai su **Privacy & Cookie** nel menu laterale della bacheca.
2. Aggiorna i testi di Privacy e Cookie Policy con l'editor visuale. Puoi usare gli shortcode per richiamarli in qualsiasi pagina:
   - `[fp_privacy_policy]`
   - `[fp_cookie_policy]`
   - `[fp_cookie_preferences]` (pulsante per riaprire le preferenze)
3. Configura il banner cookie (titolo, messaggio, etichette pulsanti) e decidi se mostrare il pulsante di rifiuto e quello delle preferenze. Compila anche i testi in inglese per offrire un'esperienza bilingue.
4. Per ciascuna categoria indica descrizione, servizi e durata dei cookie utilizzati. Le categorie obbligatorie sono marcate come "Sempre attivo" nel front-end.
5. Imposta i valori di default del Google Consent Mode v2 scegliendo tra `granted` o `denied` per ciascun segnale.

## Google Consent Mode v2

- Il plugin invia i segnali di default tramite `gtag('consent', 'default', {...})` e aggiorna automaticamente gli stati al salvataggio delle preferenze (`consent`, `accept_all`, `reject_all`).
- Gli eventi vengono tracciati su `window.dataLayer` con `event: 'fp_consent_update'` e i dettagli dell'ultima scelta.
- Per utilizzare Google Tag Manager o gtag.js assicurati che lo script sia caricato **dopo** il banner (ad esempio nell'`head`) e che non imposti manualmente il consent mode in conflitto con il plugin.
- Puoi ascoltare l'evento JavaScript personalizzato `fp-consent-change` per abilitare/disabilitare altri script di terze parti in base alle categorie selezionate.

## Registro consensi

- Nella tab **Registro consensi** trovi gli ultimi eventi registrati (50 per pagina) con data, ID consenso, stato e IP anonimizzato.
- È possibile esportare l'intero registro in formato CSV per adempiere agli obblighi di accountability.
- Gli ID consenso sono conservati in un cookie tecnico sicuro e anonimo (`fp_consent_state_id`).

## Suggerimenti legali

Questo plugin fornisce strumenti tecnici ma non sostituisce la consulenza legale. Per la piena conformità al GDPR:

- Aggiorna periodicamente le informative e i registri in base ai trattamenti effettivi.
- Notifica agli utenti eventuali cambiamenti nelle finalità o nei partner terzi e raccogli nuovamente il consenso quando necessario.
- Imposta procedure interne per gestire le richieste degli interessati (accesso, rettifica, cancellazione, portabilità, ecc.).

## Supporto

Il plugin è pensato per sviluppatori e web agency. Puoi estenderlo registrando nuovi hook su `fp-consent-change` o leggendo il cookie `fp_consent_state` per gestire script personalizzati.
