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

Su Windows, **HTTP** verso Local di solito funziona senza problemi di certificato; **HTTPS** può richiedere `-SkipTls` o aprire il sito nel browser accettando il certificato.

## Check rapido manuale

```powershell
php -r "chdir('C:/path/to/wordpress'); require 'wp-load.php'; echo FP_PRIVACY_VERSION;"
```

(con path corretto alla root WordPress del sito Local)
