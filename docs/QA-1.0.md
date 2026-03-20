# Checklist QA pre-release 1.0.0

Eseguire su un sito di staging (preferibilmente copia di produzione) con **WP 6.x** e **PHP 8.0+**. Segnare data e ambiente accanto a ogni blocco.

---

## Automatizzabile (locale / CI)

| Passo | Comando / verifica |
| --- | --- |
| PHPUnit | `composer test` |
| PHPStan (livello 5, percorsi in `phpstan.neon.dist`) | `composer phpstan` |
| i18n (dopo modifiche stringhe) | `wp i18n make-pot` / `make-mo` come da workflow plugin |

---

## Prima visita e banner

- [ ] Prima visita (cookie assenti): il banner compare; focus trap / chiusura modal accessibili.
- [ ] **Accetta tutti**: cookie di consenso salvato; script di categoria appropriati sbloccati; `dataLayer` / Consent Mode coerenti (se GTM/GA attivi).
- [ ] **Rifiuta tutti** (e conferma se attiva): solo necessari; niente marketing prima del nuovo consenso.
- [ ] **Preferenze**: granularità categorie e (se abilitata) servizi EDPB; salvataggio e riapplicazione al reload.
- [ ] Pulsante **riapri preferenze** (floating/bar): riapre il pannello con stato salvato.

---

## Admin

- [ ] **Impostazioni**: salvataggio senza notice errate; anteprima banner con palette corretta.
- [ ] **Reset a default** (se usato): ripristino opzioni e messaggio di successo.
- [ ] **Policy editor** / contenuti multilingua: salvataggio e shortcode pagine policy/cookie.
- [ ] **Diagnostica / Analytics** (se in uso): pagine caricano senza errori PHP/JS in console.

---

## REST e integrazioni

- [ ] `POST /wp-json/fp-privacy/v1/consent` (o route documentata): risposta attesa con utente autorizzato / flusso pubblico come da documentazione.
- [ ] Revoca consenso (endpoint o azione UI): stato aggiornato e coerente con cookie.
- [ ] Integrazione **INTEGRATION-FRONTEND.md**: `FP_PRIVACY_VERSION` / `window.FP_PRIVACY_DATA` presenti dove previsto.

---

## Multisite (solo se in scope)

- [ ] Attivazione / blog nuovo: opzioni provisionate; banner e pagine policy per sito corretto.
- [ ] Switch tra siti: consenso e opzioni non si mescolano tra network.

---

## Rilascio

- [ ] `CHANGELOG.md` e `readme.txt` allineati alla versione taggata.
- [ ] Zip / **fp-git-updater**: installazione pulita o aggiornamento da ultima release pubblica.
- [ ] Smoke test su tema reale (non solo Twenty *) se possibile.

---

Dopo il completamento, aggiornare `docs/RELEASE-1.0.md` (sezione “Prima del tag”) e procedere con **1.0.0-rc.1** → **1.0.0** secondo la roadmap.
