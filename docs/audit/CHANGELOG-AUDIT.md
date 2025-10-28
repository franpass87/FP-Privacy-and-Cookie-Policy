# Audit Changelog

## 2025-09-30
- Implementato fallback Consent Mode su `dataLayer` e invocazione `fpPrivacyConsent.update` per aggiornamenti coerenti (`src/Integrations/ConsentMode.php`, `assets/js/banner.js`).
- Arricchiti `dataLayer` e `fp-consent-change` con `timestamp` e `consentId`, gestione `consent_id` lato client, e revision notice visibile (`src/Frontend/ConsentState.php`, `assets/js/banner.js`, `assets/css/banner.css`).
- Localizzazione completa di banner e anteprima admin; aggiornati cataloghi `.pot`/`.po` (`src/Admin/Settings.php`, `assets/js/admin.js`, `assets/js/banner.js`, `languages/fp-privacy.pot`, `languages/fp-privacy-en_US.po`).
- Migliorata accessibilità del modal (focus trap, Esc/Tab) e stili focus (`assets/js/banner.js`, `assets/css/banner.css`).
- Aggiunta preview live, avviso snapshot obsoleti, e miglioramenti UX admin (`src/Admin/Settings.php`, `assets/js/admin.js`, `assets/css/admin.css`).
- Aggiornata documentazione (README, readme.txt, CHANGELOG) con flusso preview, notice snapshot e packaging (`README.md`, `fp-privacy-cookie-policy/README.md`, `fp-privacy-cookie-policy/readme.txt`, `fp-privacy-cookie-policy/CHANGELOG.md`).
- Sincronizzato stato audit (report, summary, QA checklist, build-state) con punteggi 100% (`docs/audit/*.md`, `docs/BUILD-STATE.json`).

## 2024-?? (Audit phase)
- Sanitized eventi e stati di consenso prima del salvataggio per prevenire input arbitrari nel log (`src/Frontend/ConsentState.php`).
- Documentati risultati dell'audit iniziale (`docs/audit/*.md`, `docs/BUILD-STATE.json`).
