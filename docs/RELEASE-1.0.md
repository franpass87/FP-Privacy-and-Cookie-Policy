# Roadmap verso la versione 1.0.0

Documento di lavoro per portare **FP Privacy and Cookie Policy** a una release **1.0.0** stabile (contratto API chiaro, debito tecnico sotto controllo).

---

## Completato (tracciamento)

| Voce | Note |
| --- | --- |
| Tabella hook / filtri / REST in README | v0.3.5+ |
| Filtro `fp_privacy_consent_ids_for_email` a 3 argomenti documentati | v0.3.5 |
| `Integrations\ServiceRegistry` → facade su `Domain\Services\ServiceRegistry` | v0.4.0 |
| Limite tentativi init banner JS (nessun loop infinito senza root) | v0.4.0 |
| REST `POST /consent` + `/consent/revoke` → `ConsentController` + `ConsentRestHandlerInterface` | v0.4.1 |
| PHPUnit verde in CI locale: `tests/bootstrap.php` + test allineati (ColorPalette, facade registry, default opzioni) | v0.4.2 |
| PHPStan su `src/REST` + `src/Domain`; test same-origin `RESTPermissionChecker` | v0.4.3 |
| Documento integratori `docs/INTEGRATION-FRONTEND.md`; reset impostazioni ai default (admin); PHPStan + `src/Application`; bozza upgrade 1.0 in CHANGELOG | v0.4.4 |
| Admin UI design system FP, palette policy/banner, fix anteprima palette | v0.5.0 |
| Consent Mode / `dataLayer` prima dello sblocco script; UX rifiuta tutti + filtri `fp_privacy_reject_all_confirm*` | v0.5.1 |
| i18n `en_US` completa (policy lunga, EDPB, tooling `bin/` + chunk JSON) | v0.5.2 |
| PHPStan esteso a `src/Providers` (fix `CoreServiceProvider` livello 5) | v0.5.3 |
| PHPStan esteso a `src/Utils` + bootstrap costanti (`tools/phpstan-bootstrap.php`); fix static analysis in Logger, View, Options, PageManager, AutoTranslator, BannerValidator; rimozione dipendenze inutilizzate in BannerTextsManager/CategoriesManager | v0.5.4 |
| Checklist QA manuale pre-1.0: `docs/QA-1.0.md` | v0.5.4 |
| PHPStan livello 5 su `src/Frontend` + costanti bootstrap (`DAY_IN_SECONDS`, URL/versione plugin, …) | v0.5.5 |
| PHPStan livello 5 su `src/Infrastructure` + costanti `ARRAY_A` / `ARRAY_N` in bootstrap | v0.5.6 |
| PHPStan livello 5 su `src/Consent` + fix `LogModel` / `LogModelTable` | v0.5.7 |
| PHPStan livello 5 su `src/Admin` + fix diagnostica/DI/settings | v0.5.8 |
| PHPStan livello 5 su `src/Presentation` + stub WP-CLI per analisi | v0.5.9 |
| PHPStan su `src/Core`, `Services`, `CLI`, `Interfaces`, `Shared` + `Integrations` intera; pulizia `DetectorRegistry` | v0.5.10 |
| PHPStan: path unico `src` in `phpstan.neon.dist`; checklist roadmap aggiornata | v0.5.11 |
| Roadmap pre-1.0: PHPUnit + contratto pubblico (hook / REST) verificati vs codice e README | v0.5.12 |
| Release candidate **1.0.0-rc.1** (feature freeze, solo fix consentiti fino a 1.0.0) | v1.0.0-rc.1 |

---

## Prima del tag `v1.0.0`

### Contratto pubblico (freeze)

- [x] `do_action( 'fp_consent_update', … )`: firma attuale `($states, $event, $revision)` documentata in README e usata in `ConsentState`; per **1.0.0** non sono previste modifiche breaking; eventuali cambi incompatibili solo in **major** successiva.
- [x] REST `fp-privacy/v1`: path e semantica allineati a README e implementazione (`RESTServiceProvider`, `ConsentController`); eventuale `v2` solo in **major**.
- [x] `FP_PRIVACY_VERSION` / `FP_PRIVACY_DATA` — chiavi minime documentate in `docs/INTEGRATION-FRONTEND.md` (estendere se emergono nuovi campi).

### Codice e architettura

- [x] Consolidare stack REST: un solo percorso per `POST /consent` e revoke (`ConsentController` + interfaccia; legacy solo fallback).
- [x] **Decisione (verso 1.0)**: le API con `@deprecated 2.0.0` / PHPDoc `@deprecated` **restano** nella 1.0 senza breaking change; rimozione o `_deprecated_function` runtime valutata in **2.0**. Eccezione: `fp_privacy_get_ip_salt()` resta funzione globale di compatibilità (già documentata nel main file); uso interno preferibile via `IpSaltService`.
- [x] `assets/js/admin.js`: rimossi TODO su reset default e badge tab (completamento campi `required`); nessun TODO aperto rimasto nel file.
- [x] Allineare `INSTALL.md` / audit docs alla versione corrente (versione guida aggiornata v0.4.4+).

### Qualità

- [x] PHPStan livello **5** su tutto `src/` (`composer phpstan`); configurazione con path unico `src` in `phpstan.neon.dist`; bootstrap `tools/phpstan-bootstrap.php` e stub WP-CLI `tools/phpstan-wp-cli-stubs.php`. Nota: layer `CLI` e `Presentation\CLI` restano entrambi analizzati fino a eventuale unificazione.
- [x] PHPUnit su use case critici (consenso, cookie, REST permission) — **suite base verde** (`composer test`; same-origin REST da v0.4.3).
- [ ] Checklist manuale: seguire **`docs/QA-1.0.md`** su build **1.0.0-rc.1** (prima visita, accetta/rifiuta/salva, revoca, reset default admin, multisite se in scope).

### Rilascio

- [x] `CHANGELOG.md`: sezione **Upgrade da 0.x a 1.0** (bozza; da rifinire al tag stabile).
- [x] Tag Git **`v1.0.0-rc.1`** — release candidate (feature freeze); verifica zip / **fp-git-updater** come per release stabile.
- [ ] Tag Git **`v1.0.0`**, release notes GitHub, verifica **fp-git-updater** / zip.

---

## Opzionale post-1.0 / 2.0

- Rinominare filtro `fp_cookie_policy_content` → alias `fp_privacy_cookie_policy_content` con retrocompatibilità.
- Namespace REST `fp-privacy/v2` con schema JSON evoluto.

---

## Versioni intermedie suggerite

- **0.4.x** — hardening, deduplica, doc, test.
- **1.0.0-rc.1** — feature freeze, solo fix. *(pubblicata come candidate)*
- **1.0.0** — release stabile.
