# FP Privacy and Cookie Policy

**Autore:** [Francesco Passeri](https://francescopasseri.com/)  \
**Email:** [info@francescopasseri.com](mailto:info@francescopasseri.com)

Plugin WordPress progettato per gestire privacy policy, cookie policy e consenso informato nel rispetto del GDPR, con particolare attenzione alle esigenze dei siti italiani e all'integrazione del Google Consent Mode v2.

## Panoramica

FP Privacy and Cookie Policy offre un flusso di raccolta consensi completo, dalla presentazione del banner alla registrazione degli eventi in un log consultabile e esportabile. Il plugin mantiene tutti i testi all'interno di WordPress per facilitare aggiornamenti e revisioni editoriali e permette di orchestrare strumenti di terze parti grazie a hook ed eventi dedicati.

## Funzionalità principali

- Banner cookie responsive con pulsanti **Accetta**, **Rifiuta** e accesso rapido alle preferenze.
- Localizzazione automatica italiano/inglese del banner e delle categorie in base alla lingua del browser.
- Gestione granulare delle categorie di cookie (necessari, preferenze, statistiche, marketing) con descrizione dei servizi utilizzati.
- Editor WYSIWYG per i testi di privacy e cookie policy e shortcode pronti all'uso.
- Registro consensi con anonimizzazione IP, esportazione in CSV e conservazione configurabile degli eventi.
- Indicatore contestuale della data dell'ultimo consenso con aggiornamento automatico del pulsante di gestione.
- Integrazione automatica con Google Consent Mode v2 (`analytics_storage`, `ad_storage`, `ad_user_data`, `ad_personalization`, `functionality_storage`, `security_storage`).
- Supporto per Google Tag Manager/eventi personalizzati via `dataLayer` e custom event `fp-consent-change`.
- Traduzioni `en_US` pronte e file `.pot` per localizzazioni aggiuntive.
- Comandi WP-CLI per verificare lo stato del registro, ricreare la tabella dei consensi, esportare i log e avviare la pulizia manuale.

## Componenti principali

| Area | Descrizione |
| --- | --- |
| Banner front-end | Template responsive con gestione dinamica di testi, categorie e preferenze utente. |
| Modulo privacy | Editor visuale per privacy policy e cookie policy, con shortcode `fp_privacy_policy`, `fp_cookie_policy` e `fp_cookie_preferences`. |
| Registro consensi | Tabella dedicata nel database con anonimizzazione IP, esportazione CSV e pianificazione cleanup. |
| Integrazione Consent Mode | Script JavaScript che sincronizza gli stati del consenso con Google Tag Manager e gtag.js. |
| WP-CLI | Namespace `fp-privacy` per comandi di verifica, ricostruzione tabella, pulizia ed export. |

## Installazione

1. Copia la cartella `fp-privacy-cookie-policy` all'interno di `wp-content/plugins/` oppure carica l'archivio dal pannello di WordPress.
2. Attiva **FP Privacy and Cookie Policy** dalla voce "Plugin".
3. Alla prima attivazione viene creata automaticamente la tabella del registro consensi.

## Configurazione rapida

1. Vai su **Privacy & Cookie** nel menu laterale della bacheca.
2. Aggiorna i testi di Privacy e Cookie Policy con l'editor visuale. Gli shortcode disponibili sono:
   - `[fp_privacy_policy]`
   - `[fp_cookie_policy]`
   - `[fp_cookie_preferences]`
3. Configura il banner cookie (titolo, messaggio, etichette dei pulsanti) e definisci se mostrare il pulsante di rifiuto o quello delle preferenze.
4. Per ciascuna categoria inserisci descrizione, servizi e durata dei cookie. Le categorie obbligatorie sono marcate come "Sempre attivo" nel front-end.
5. Imposta i valori di default del Google Consent Mode v2 scegliendo tra `granted` o `denied` per ciascun segnale.
6. Configura la conservazione del registro consensi per rispettare le policy interne e le richieste GDPR.

## Google Consent Mode v2

- Il plugin invia i segnali di default tramite `gtag('consent', 'default', {...})` e aggiorna automaticamente gli stati al salvataggio delle preferenze (`consent`, `accept_all`, `reject_all`).
- Gli eventi vengono tracciati su `window.dataLayer` con `event: 'fp_consent_update'` e i dettagli dell'ultima scelta.
- Per utilizzare Google Tag Manager o gtag.js assicurati che lo script sia caricato **dopo** il banner (ad esempio nell'`head`) e che non imposti manualmente il consent mode in conflitto con il plugin.
- Puoi ascoltare l'evento JavaScript personalizzato `fp-consent-change` per abilitare/disabilitare altri script di terze parti in base alle categorie selezionate.

## Registro consensi

- Nella tab **Registro consensi** trovi gli ultimi eventi registrati (50 per pagina) con data, ID consenso, stato e IP anonimizzato.
- È possibile esportare l'intero registro in formato CSV per adempiere agli obblighi di accountability, con esportazione a blocchi ottimizzata e personalizzabile tramite il filtro `fp_privacy_csv_export_batch_size`.
- Puoi definire un periodo di conservazione automatica del registro per eliminare i consensi più datati.
- Il registro è compatibile con gli strumenti di esportazione ed eliminazione dati di WordPress per gestire le richieste GDPR.
- Gli ID consenso sono conservati in un cookie tecnico sicuro e anonimo (`fp_consent_state_id`).

## Comandi WP-CLI

- `wp fp-privacy status` mostra se la tabella del registro è disponibile, quanti eventi sono memorizzati e quando è prevista la prossima pulizia pianificata.
- `wp fp-privacy recreate [--force]` ricrea la tabella del registro consensi e ripristina la pianificazione della pulizia.
- `wp fp-privacy cleanup` avvia la pulizia manuale del registro rispettando il periodo di conservazione configurato.
- `wp fp-privacy export --file=percorso/file.csv` esporta i consensi in un file CSV, utile per audit e archiviazione offline.

## Localizzazione e personalizzazione

- Le stringhe sono tradotte in inglese (`en_US`) e il file `.pot` consente di generare rapidamente nuove localizzazioni.
- Hook e filtri permettono di personalizzare testi, durata dei cookie e batch di esportazione.
- L'evento `fp-consent-change` può essere intercettato da script custom per modulare caricamenti di terze parti.

## Suggerimenti legali

Questo plugin fornisce strumenti tecnici ma non sostituisce la consulenza legale. Per la piena conformità al GDPR:

- Aggiorna periodicamente le informative e i registri in base ai trattamenti effettivi.
- Notifica agli utenti eventuali cambiamenti nelle finalità o nei partner terzi e raccogli nuovamente il consenso quando necessario.
- Imposta procedure interne per gestire le richieste degli interessati (accesso, rettifica, cancellazione, portabilità, ecc.).

## Supporto

Il plugin è pensato per sviluppatori e web agency. Puoi estenderlo registrando nuovi hook su `fp-consent-change` o leggendo il cookie `fp_consent_state` per gestire script personalizzati. Per informazioni professionali o richieste su misura contatta [info@francescopasseri.com](mailto:info@francescopasseri.com).

## Changelog

-### 1.5.3
- Aggiunto un controllo preventivo dei requisiti minimi (PHP e WordPress) con disattivazione automatica e avvisi in bacheca.

### 1.5.2
- Aggiornata la documentazione ufficiale (README, readme.txt e changelog) con la cronologia completa delle release.
- Allineati i riferimenti all'autore al nuovo maintainer Francesco Passeri.

### 1.5.1
- Aggiunto il comando WP-CLI `wp fp-privacy recreate` per ricreare rapidamente la tabella del registro e ripristinare la pianificazione della pulizia.

### 1.5.0
- Aggiunti comandi WP-CLI per verificare lo stato del registro consensi, avviare pulizie manuali ed esportare snapshot CSV senza accedere alla bacheca.

### 1.4.0
- Aggiunto un indicatore live con tooltip e metadati temporali per mostrare l'ultimo aggiornamento del consenso.
- Migliorati i push sul `dataLayer` e gli eventi personalizzati riutilizzando il timestamp registrato.
- Migliorata l'accessibilità esponendo l'ultima data di consenso tramite `aria-describedby` nel pulsante di gestione.

### 1.3.2
- Allineata la durata del cookie identificativo del consenso alla durata configurata e aggiunti filtri per personalizzazioni avanzate.
- Garantita l'applicazione dei parametri di sicurezza predefiniti, incluso `SameSite=Lax`.

### 1.3.1
- Ottimizzata l'esportazione CSV del registro consensi per gestire dataset ampi senza saturare la memoria.
- Introdotto il filtro `fp_privacy_csv_export_batch_size` per regolare il numero di righe esportate per batch.

### 1.3.0
- Introdotto un controllo dedicato per configurare la durata del cookie di consenso con salvaguardie e filtri dedicati.
- Aggiornato lo script front-end per rispettare la durata configurata mantenendo default sicuri.

### 1.2.0
- Aggiunti controlli di conservazione del registro consensi con pianificazione quotidiana della pulizia automatica.
- Integrati i log del consenso con gli strumenti di esportazione e cancellazione dati di WordPress.
- Rafforzati gli strumenti amministrativi quando la tabella del registro manca o necessita di ricreazione.

### 1.1.0
- Pubblicati gli asset di produzione (file di traduzione, readme e placeholder per la directory).
- Introdotta la localizzazione inglese e aggiornati i metadati del plugin per la conformità a WordPress.org.
- Migliorata la gestione dell'identificativo di consenso per un'esperienza coerente.

### 1.0.0
- Prima release stabile.
