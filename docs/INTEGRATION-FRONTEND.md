# Integrazione frontend: `FP_PRIVACY_DATA` e versione plugin

Documento per integratori che leggono lo stato del banner e il consenso dal browser (verso la **1.0** il contratto va considerato stabile salvo note di release).

## `FP_PRIVACY_VERSION` (PHP)

- Costante definita nel file principale del plugin (`fp-privacy-cookie-policy.php`), alias di `FP_PRIVACY_PLUGIN_VERSION`.
- Uso tipico: cache-busting degli asset, telemetria interna, confronto versione in integrazioni server-side.

## `window.FP_PRIVACY_DATA` (JavaScript)

Popolato da `wp_localize_script` sullo script `fp-privacy-consent-mode` quando il plugin registra gli asset frontend (`BannerAssetManager`).

### Chiavi attese (minimo documentato)

| Chiave | Tipo | Descrizione |
| --- | --- | --- |
| `ajaxUrl` | `string` | URL `admin-ajax.php` (azioni legacy/AJAX ove applicabile). |
| `nonce` | `string` | Nonce azioni `fp-privacy-consent` (contesto AJAX plugin). |
| `options` | `object` | Stato calcolato per la lingua corrente (testi banner, layout, categorie, `state`, `mode`, ecc.). |
| `options.state` | `object` | Include almeno `should_display`, `preview_mode`, `categories` (forma dipende da `ConsentState`). |
| `cookie` | `object` | `name`: nome cookie consenso; `duration`: giorni (dopo filtro `fp_privacy_cookie_duration_days`). |
| `rest` | `object` | `url`: endpoint `POST` consenso REST; `nonce`: nonce `wp_rest` per richieste autenticate da stesso sito. |

### REST consenso

- Namespace: `fp-privacy/v1` (vedi README: `POST /consent`, `POST /consent/revoke`).
- I permessi lato server combinano **same-origin** / Referer / `X-WP-Nonce` (vedi `RESTPermissionChecker`).

### Note

- Non fare affidamento su chiavi non documentate qui: possono evolversi nelle minor; breaking solo in major.
- Per forzare il caricamento asset banner in contesti non standard usare il filtro `fp_privacy_force_enqueue_banner`.
