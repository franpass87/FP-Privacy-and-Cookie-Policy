# FP Privacy and Cookie Policy – Audit Playbook

Questo playbook elenca le verifiche richieste per l'audit del plugin (versione 0.1.0) suddivise per sezione, con peso, riferimenti e criteri di superamento (PASS) o fallimento (FAIL).

## 1. Security (peso 15)
- [ ] **CSRF & capability** – Verificare nonce e controlli `current_user_can` su ogni endpoint/admin-post. PASS se presenti ovunque; FAIL in caso contrario.
- [ ] **Sanitizzazione input** – Tutte le opzioni salvate devono passare per sanificazione/validazione. PASS se coprono campi liberi; FAIL se valori arbitrari raggiungono il DB.
- [ ] **Escape output** – Controllare `esc_html`, `esc_attr`, `wp_kses_post` per tutti gli output. PASS se coerenti; FAIL se XSS potenziale.
- [ ] **Gestione IP e cookie** – IP solo hash, cookie con `Secure` su HTTPS, `SameSite=Lax`, nessun dato personale in chiaro. PASS se conformi.
- [ ] **Rate limiting & eventi** – Valutare limite POST /consent, validazione `consent_revision`. PASS se limite < 15 richieste/10min e revisione intera.

## 2. Performance (peso 8)
- [ ] **Enqueue condizionale** – Asset front-end e admin caricati solo quando necessari.
- [ ] **Query** – Assenza di N+1 e uso di indici nel log (verificare schema DB).
- [ ] **Asset leggeri** – Nessun CSS/JS non utilizzato, niente variabili inline superflue.

## 3. Multisite (peso 10)
- [ ] **Provisioning** – Hook `wpmu_new_blog` e attivazione network corretti.
- [ ] **Options per-sito** – Verificare singleton `Options` e invalidazione su `switch_to_blog`.
- [ ] **Scheduler per-sito** – Controllare cron e cleanup isolati.

## 4. Consent Mode v2 (peso 10)
- [ ] **gtag default/update** – Implementazione coerente con categorie e nessun conflitto.
- [ ] **Mappatura categorie** – Analytics/ads/functionality/security aggiornati correttamente.

## 5. Data Layer & Events (peso 6)
- [ ] **`dataLayer.push`** – Evento `fp_consent_update` con dettagli completi.
- [ ] **CustomEvent** – `fp-consent-change` con `detail` completo (consent, event, revision, timestamp).

## 6. DB Schema & Retention (peso 8)
- [ ] **Schema** – Tabella log con tipi adeguati, indici su colonne interrogate.
- [ ] **Retention** – Scheduler di cleanup basato su `retention_days`.
- [ ] **Export** – Batch CSV con filtro `fp_privacy_csv_export_batch_size`.

## 7. REST API (peso 8)
- [ ] **Namespace & permessi** – Controllo ruoli per rotte sensibili.
- [ ] **Rate limiting** – Endpoint pubblici /consent con limite.
- [ ] **Validazione input** – Parametri sanitizzati e validati.

## 8. WP-CLI (peso 5)
- [ ] **Comandi** – Presenza comandi richiesti (status, recreate, cleanup, export, settings import/export, detect, regenerate con opzioni `--lang`/`--bump-revision`).
- [ ] **Supporto `--url`** – Funzionano in multisito.

## 9. I18n (peso 6)
- [ ] **Text domain** – Tutte le stringhe con `fp-privacy`.
- [ ] **File `.pot`** – Aggiornato in `languages/`.
- [ ] **Shortcode/Blocchi** – Traducibili.

## 10. Accessibility (peso 6)
- [ ] **Focus trap** – Modale banner con focus gestito, tasti Esc/Enter/Space.
- [ ] **ARIA & contrasto** – Attributi aria e contrasto AA nelle anteprime admin.

## 11. UX Admin (peso 5)
- [ ] **Chiarezza impostazioni** – Sezioni chiare, label tradotte.
- [ ] **Anteprima live** – Aggiornamenti colori in tempo reale.
- [ ] **Avvisi drift** – Presenza reminder “Rigenera policy” quando snapshot obsoleti.

## 12. Frontend Banner (peso 7)
- [ ] **Layout** – Supporto floating/bar, top/bottom.
- [ ] **Preferenze granulari** – Gestione categorie e anteprima.
- [ ] **Preview mode** – Nessun log/cookie quando attivo.

## 13. Detector & Generator (peso 10)
- [ ] **Registry** – Copertura servizi richiesti (GA4, GTM, Meta Pixel, Hotjar, Clarity, reCAPTCHA, YouTube/Vimeo, LinkedIn, TikTok, Matomo, Woo).
- [ ] **Policy generator** – Template con sezioni GDPR, tabelle servizi, filtri.

## 14. Docs/CI/Packaging (peso 6)
- [ ] **Documentazione** – README/readme.txt coerenti, CHANGELOG.
- [ ] **Workflow build** – Script/CI per pacchetti senza binari.
- [ ] **.gitignore/.gitattributes** – Bloccano artefatti binari.

Annotare note, evidenze e file toccati durante l'esecuzione.
