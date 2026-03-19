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

---

## Prima del tag `v1.0.0`

### Contratto pubblico (freeze)

- [ ] Nessuna modifica breaking a `do_action( 'fp_consent_update', … )` senza major successiva.
- [ ] REST `fp-privacy/v1` — stessi path e semantica risposta documentati; eventuale `v2` solo in major.
- [ ] `FP_PRIVACY_VERSION` / `FP_PRIVACY_DATA` — documentare chiavi minime per integratori frontend.

### Codice e architettura

- [x] Consolidare stack REST: un solo percorso per `POST /consent` e revoke (`ConsentController` + interfaccia; legacy solo fallback).
- [ ] Rimuovere o wrappare con `_deprecated_function` le API segnate `@deprecated` (es. `fp_privacy_get_ip_salt`, `ConsentModel`, `OptionsAdapter` temporaneo) — decidere: rimozione in 1.0 vs deprecazione fino a 2.0.
- [ ] Chiudere o documentare i `TODO` in `assets/js/admin.js`.
- [ ] Allineare `INSTALL.md` / audit docs alla versione corrente.

### Qualità

- [ ] PHPStan livello concordato (attualmente analisi parziale su `src/REST` + registry; espandere gradualmente) + PHPUnit su use case critici (consenso, cookie, REST permission) — **suite base verde** da v0.4.2 (`composer test`).
- [ ] Checklist manuale: prima visita, accetta/rifiuta/salva, revoca, bump revisione, multisite (se in scope).

### Rilascio

- [ ] `CHANGELOG.md`: sezione **Upgrade da 0.x a 1.0** (anche “nessun breaking” va esplicitato).
- [ ] Tag Git `v1.0.0`, release notes GitHub, verifica **fp-git-updater** / zip.

---

## Opzionale post-1.0 / 2.0

- Rinominare filtro `fp_cookie_policy_content` → alias `fp_privacy_cookie_policy_content` con retrocompatibilità.
- Namespace REST `fp-privacy/v2` con schema JSON evoluto.

---

## Versioni intermedie suggerite

- **0.4.x** — hardening, deduplica, doc, test.
- **1.0.0-rc.1** — feature freeze, solo fix.
- **1.0.0** — release stabile.
