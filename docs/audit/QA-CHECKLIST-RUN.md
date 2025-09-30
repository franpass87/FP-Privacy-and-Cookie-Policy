# QA Checklist Run

| Check | Esito | Come ripetere |
| --- | --- | --- |
| Sanity salvataggio consenso REST | PASS | Eseguire `wp-env` o install locale, inviare POST `wp-json/fp-privacy/v1/consent` con header `X-WP-Nonce` valido e payload `{ "event": "consent", "states": {"marketing": true}, "lang": "it_IT" }`; verificare risposta 200 e voce log con `consent_id` e `states` normalizzati. |
| Verifica rate limit | PASS | Ripetere la chiamata REST >10 volte in 10 minuti e attendere risposta 429 (transient `fp_privacy_rate_*`). |
| Preview mode | PASS | Abilitare `preview_mode` in admin, ricaricare frontend e controllare (devtools → Network) che nessuna POST venga inviata e che il pannello debug mostri i cookie correnti. |
| Accessibility modal | PASS | Aprire banner, premere `Tab` fino all'ultimo bottone, confermare che il focus ruota al primo elemento; premere `Esc` per chiudere e tornare al trigger originale. |
| Data layer & CustomEvent | PASS | Accettare i cookie, quindi eseguire in console `dataLayer[dataLayer.length-1]` e verificare presenza `event`, `consentId`, `timestamp`; ascoltare `fp-consent-change` (`document.addEventListener(...)`) per confermare gli stessi valori. |
| Revision notice & stale snapshot | PASS | Impostare `consent_revision` > `state.last_revision` oppure cancellare il cookie, ricaricare: il banner mostra la `revision_notice`. In admin, azzerare `snapshots` tramite Tools → Regenerate, quindi dopo 15 giorni simulati (modificare timestamp via DB) appare il notice giallo con link Tools. |
| Documentazione & packaging | PASS | Eseguire `bin/package.sh` e verificare la creazione di `dist/fp-privacy-cookie-policy-0.1.0.zip` senza file binari/minificati; confrontare README/readme/CHANGELOG aggiornati. |
