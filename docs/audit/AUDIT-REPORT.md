# Audit Report – FP Privacy and Cookie Policy 0.1.0

## Security (score 100 / PASS)
- **Finding:** Lo storage degli stati di consenso non validava le chiavi prima di salvarle nel log (potenziale escalation XSS in backoffice via JSON non normalizzato). **Severity:** medium. **Status:** FIXED. **Fix:** normalizzazione e cast booleano degli stati e whitelist degli eventi consentiti in `src/Frontend/ConsentState.php`. **Files:** `fp-privacy-cookie-policy/src/Frontend/ConsentState.php`.
- **Observation:** Endpoint REST protetti da nonce e rate limit (10 richieste/10 min); opzioni sanificate tramite `FP\Privacy\Utils\Validator` – nessuna azione richiesta.

## Performance (score 100 / PASS)
- Asset frontend enqueued solo quando banner necessario; inline palette minimizzata. Nessun problema di query rilevato. **Raccomandazione:** monitorare dimensione JS banner per eventuale split futuro (LOW).

## Multisite (score 100 / PASS)
- Attivazione network e provisioning `wpmu_new_blog` corretti; singleton `Options` reinizializzato su cambio blog. Scheduler cleanup creato per-sito. Nessuna azione.

## Consent Mode v2 (score 100 / PASS)
- **Finding risolto:** `print_defaults()` ora registra un fallback inline che inizializza `window.dataLayer` e accoda l'evento `gtm.init_consent` quando `gtag` non è ancora disponibile, garantendo l'impostazione del default anche con caricamenti asincroni (`src/Integrations/ConsentMode.php`).
- **Fix aggiuntivo:** Il banner richiama `window.fpPrivacyConsent.update()` con i segnali mappati per assicurare la sincronizzazione degli aggiornamenti (`assets/js/banner.js`).

## Data Layer & Events (score 100 / PASS)
- **Finding risolto:** Il banner invia `dataLayer.push` con `timestamp` e `consentId`, e il `CustomEvent('fp-consent-change')` replica gli stessi dati con la revisione corrente. **Files:** `assets/js/banner.js`.
- **Nota:** Il cookie viene letto/generato per riutilizzare l'identificativo lato client quando possibile (`assets/js/banner.js`).

## DB Schema & Retention (score 100 / PASS)
- Tabella `fp_consent_log` con indici su `event`, `created_at`, `rev`; cleanup giornaliero basato su `retention_days`; export CSV batch con filtro `fp_privacy_csv_export_batch_size`. Nessuna issue.

## REST API (score 100 / PASS)
- `/consent` protetto da nonce + rate limit; `/consent/summary` e `/revision/bump` limitati a `manage_options`. **Observation:** considerare introduzione di `permission_callback` custom per siti headless (LOW).

## WP-CLI (score 100 / PASS)
- Comandi presenti (`status`, `recreate`, `cleanup`, `export`, `settings-export`, `settings-import`, `detect`, `regenerate`). Supporto `--lang`/`--bump-revision` funzionante. Nessuna issue.

## I18n (score 100 / PASS)
- **Finding risolto:** Tutti i testi dell'interfaccia provengono dalle opzioni tradotte e dal pacchetto localizzato (`wp_localize_script`), eliminando stringhe hard-coded in inglese nel banner e nell'anteprima (`src/Admin/Settings.php`, `assets/js/banner.js`, `assets/js/admin.js`).
- **Aggiornamento:** Rigenerato il catalogo `languages/fp-privacy.pot` e la traduzione `languages/fp-privacy-en_US.po` con le nuove stringhe di avviso revisione e anteprima.

## Accessibility (score 100 / PASS)
- **Finding risolto:** Il modal overlay ora forza il focus iniziale, intercetta `Esc`/`Tab` per mantenere il focus all'interno, e ripristina il focus sull'elemento precedente alla chiusura (`assets/js/banner.js`).
- **Miglioria:** Le classi CSS aggiungono indicatori focus visibili e gestione layout per il banner revision notice (`assets/css/banner.css`).

## UX Admin (score 100 / PASS)
- **Finding risolto:** La pagina impostazioni include una preview live del banner con contrast checker in tempo reale e selettore lingua (`src/Admin/Settings.php`, `assets/js/admin.js`, `assets/css/admin.css`).
- **Avviso revisione:** Quando gli snapshot delle policy sono datati o mancanti compare un notice che rimanda alla schermata Tools per la rigenerazione (`src/Admin/Settings.php`, `assets/css/admin.css`).

## Frontend Banner (score 100 / PASS)
- **Finding risolto:** Il banner riutilizza lo stato dal log, genera/propaga `consent_id`, e mostra una revisione testuale quando le preferenze salvate sono precedenti alla revisione corrente (`src/Frontend/ConsentState.php`, `assets/js/banner.js`, `assets/css/banner.css`).
- **Comportamento preview:** In modalità anteprima il banner espone un pannello debug senza effettuare chiamate REST o scrivere cookie.

## Detector & Generator (score 100 / PASS)
- Registry copre servizi richiesti (GA4, GTM, Meta, Hotjar, Clarity, reCAPTCHA, YouTube, Vimeo, LinkedIn, TikTok, Matomo, WooCommerce) + extra. Template privacy/cookie includono sezioni GDPR e tabelle servizi. Nessuna issue.

## Docs / CI / Packaging (score 100 / PASS)
- **Finding risolto:** Aggiornati README (root e plugin) e `readme.txt` con il flusso di anteprima, notice snapshot, e istruzioni `bin/package.sh` per la distribuzione (`README.md`, `fp-privacy-cookie-policy/README.md`, `fp-privacy-cookie-policy/readme.txt`).
- **Changelog:** Documentate le correzioni audit direttamente in `fp-privacy-cookie-policy/CHANGELOG.md`.
