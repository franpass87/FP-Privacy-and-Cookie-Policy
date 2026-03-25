# Verifica ambiente locale (WordPress + FP Privacy)

## PHP CLI e MySQL (Windows / Scoop)

WordPress richiede **`mysqli`** (e spesso **`pdo_mysql`**) nel PHP usato da terminale per `wp-load.php`, WP-CLI o script di test.

Se vedi *«L'installazione di PHP non ha l'estensione MySQL necessaria»*:

1. Apri il `php.ini` del PHP CLI (es. `php --ini`).
2. Abilita le righe (rimuovi `;` iniziale):
   - `extension=mysqli`
   - `extension=pdo_mysql`
3. Verifica: `php -r "var_dump(extension_loaded('mysqli'));"` → `bool(true)`.

## Script nel repository

Da root del plugin (o path assoluto):

```powershell
powershell -ExecutionPolicy Bypass -File tools/verify-local.ps1
powershell -ExecutionPolicy Bypass -File tools/verify-local.ps1 -SiteUrl "http://fp-development.local"
```

Parametri:

- **`-SiteUrl`**: base URL del sito Local (default `http://fp-development.local`).
- **`-SkipTls`**: per HTTPS con certificato self-signed usa `-SkipTls` (richiede PowerShell 7+ per `-SkipCertificateCheck`).

Lo script verifica anche **`/privacy-policy/`** e **`/cookie-policy/`** (200 atteso se gli slug canonici esistono sul sito).

Su Windows, **HTTP** verso Local di solito funziona senza problemi di certificato; **HTTPS** può richiedere `-SkipTls` o aprire il sito nel browser accettando il certificato.

## Check rapido manuale

```powershell
php -r "chdir('C:/path/to/wordpress'); require 'wp-load.php'; echo FP_PRIVACY_VERSION;"
```

(con path corretto alla root WordPress del sito Local)

## Perché non vedo il banner cookie nel browser?

Comportamento **normale** se:

1. **Hai già dato il consenso** (cookie `fp_consent_state_id` / stato salvato): `ConsentState` imposta `should_display` a false, quindi **il banner strip iniziale non viene mostrato**; di default il plugin carica comunque `banner.js` / CSS così restano il **pulsante reopen** (basso a sinistra) e il modal con link policy. Solo se un sito usa il filtro `fp_privacy_enqueue_full_banner_assets` a `false` torna il caricamento minimo solo `consent-mode.js` (senza reopen).
2. **Sei loggato** e hai navigato in passato: spesso il cookie è già presente nella sessione del browser.

### Come vedere il banner in sviluppo

| Metodo | Cosa fare |
|--------|-----------|
| **A. Modalità anteprima (consigliata per admin)** | **Privacy e Cookie → Impostazioni → tab «Privacy e Consenso»** → sezione **Retention & Revision** → spunta **«Abilita modalità anteprima (solo admin)»** → **Salva impostazioni privacy**. Poi ricarica il frontend: il banner viene forzato (`preview_mode` nel payload `FP_PRIVACY_DATA`). Ricordati di **disattivare** l’anteprima in produzione. |
| **B. Finestra anonima / altro browser** | Nessun cookie di consenso → il banner compare se serve ancora il consenso. |
| **C. Cancella cookie del sito** | Per il dominio locale (es. `fp-development.local`), rimuovi i cookie del plugin e ricarica. |
| **D. Nuova revisione consenso** | Stesso tab: **«Forza nuovo consenso (incrementa revisione)»** invalida i consensi precedenti. |

Anteprima **solo aspetto** (palette/testi) senza logica di consenso: dalla stessa schermata impostazioni usa **«Preview Banner»** (anteprima admin, non sostituisce il frontend).

