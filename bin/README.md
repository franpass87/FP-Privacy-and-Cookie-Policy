# Script di Utilità FP Privacy

Questa cartella contiene script di utilità per la gestione, testing e manutenzione del plugin FP Privacy.

## 🚀 Accesso rapido

**Dashboard Tools (consigliato):**
```
http://tuosito.local/wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/
```

Interfaccia centralizzata per accedere a tutti gli script di sviluppo e testing.

---

## 📋 Script disponibili

### 🔍 Diagnostica & Testing

- **`diagnostics.php`** - Diagnostica completa del plugin
- **`test-translations.php`** - Test sistema traduzioni

### 🔧 Manutenzione Traduzioni

- **`compile-mo-files.php`** - Compila file `.po` in `.mo`
- **`force-update-translations.php`** - Forza aggiornamento traduzioni nel DB

### 📄 Generazione Policy

- **`generate-policies.php`** - Genera pagine Privacy/Cookie Policy

### 📊 Altri Tool

- **`dev-test.ps1`** - PowerShell script per sviluppo Windows
- **`qa-checklist.md`** - Checklist quality assurance

---

## 🔍 Diagnostica Completa

### diagnostics.php

Esegue una diagnostica completa del plugin verificando tutti i componenti critici.

**Esecuzione:**
```bash
# Browser
http://tuosito.local/wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/diagnostics.php

# WP-CLI
wp eval-file wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/diagnostics.php
```

**Verifica:**
- ✅ **Core:** Plugin caricato, costanti definite, PSR-4 autoload
- ✅ **Database:** Tabella consensi, numero record
- ✅ **Traduzioni:** Textdomain, file .mo, traduzioni funzionanti
- ✅ **File:** Presenza file critici, dimensioni
- ✅ **Configurazione:** Lingue attive, pagine create, opzioni
- ✅ **Integrazioni:** FP-Multilanguage, WooCommerce, etc.

---

## 🧪 Test Traduzioni

### test-translations.php

Esegue una suite completa di test per verificare che il sistema di traduzioni funzioni correttamente.

**Esecuzione:**
```bash
# Browser
http://tuosito.local/wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/test-translations.php

# CLI
php wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/test-translations.php

# WP-CLI
wp eval-file wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/test-translations.php
```

**Verifica:**
- ✅ Caricamento textdomain
- ✅ Locale corrente
- ✅ Esistenza file `.mo`
- ✅ Traduzioni banner (11 stringhe)
- ✅ Traduzioni palette (9 stringhe)
- ✅ Simulazione rendering

---

## 🔄 Force Update Traduzioni

### force-update-translations.php

Forza l'aggiornamento delle traduzioni del banner nel database, rimuovendo eventuali testi cached e caricando le traduzioni dai file `.mo`.

**Quando usarlo:**
- Dopo aver modificato i file `.po` e ricompilato i `.mo`
- Quando i testi nelle impostazioni non si aggiornano
- Dopo aver cambiato le lingue attive

**Esecuzione:**
```bash
# Browser
http://tuosito.local/wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/force-update-translations.php

# WP-CLI
wp eval-file wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/force-update-translations.php
```

**Effetti:**
- 🧹 Pulisce cache WordPress e OPcache
- 🔄 Ricarica textdomain
- 💾 Forza aggiornamento traduzioni nel database
- 📊 Mostra testi prima/dopo

---

## 🔨 Compilazione File MO

### compile-mo-files.php

Compila i file `.po` (testo) in file `.mo` (binari) utilizzati da WordPress per le traduzioni.

**Quando usarlo:**
- Dopo aver modificato i file `.po`
- Dopo aver aggiunto nuove stringhe traducibili
- Prima di rilasciare una nuova versione del plugin

**Esecuzione:**
```bash
# CLI (dalla cartella bin)
php compile-mo-files.php

# CLI (dalla root WordPress)
php wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/compile-mo-files.php

# Browser
http://tuosito.local/wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/compile-mo-files.php

# WP-CLI
wp eval-file wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/compile-mo-files.php
```

**Output:**
- ✅ Numero di traduzioni compilate
- ✅ Dimensione file `.mo` generato
- ✅ Timestamp compilazione

---

## 📄 Generazione Policy

### generate-policies.php

Genera automaticamente le pagine Privacy Policy e Cookie Policy.

**Esecuzione:**

```bash
# Genera le pagine (lingua predefinita)
php generate-policies.php

# Genera per tutte le lingue
php generate-policies.php --all-languages

# Genera solo per l'italiano
php generate-policies.php --lang=it_IT

# Forza la rigenerazione
php generate-policies.php --force

# Verifica senza modificare
php generate-policies.php --dry-run

# Mostra aiuto completo
php generate-policies.php --help
```

## Alternative

### Con WP-CLI (consigliato)

```bash
wp fp-privacy generate-pages --all-languages
```

### Dalla directory WordPress

```bash
php wp-content/plugins/fp-privacy-cookie-policy/bin/generate-policies.php --all-languages
```

## Opzioni Principali

- `--all-languages` - Genera per tutte le lingue configurate
- `--lang=<codice>` - Genera solo per una lingua (es. it_IT)
- `--force` - Forza la sovrascrittura di pagine modificate
- `--bump-revision` - Incrementa la revisione del consenso
- `--dry-run` - Simula senza salvare modifiche

---

## 🔄 Workflow tipico

### 1. Modificare traduzioni

```bash
# 1. Modifica i file .po in languages/
nano languages/fp-privacy-it_IT.po

# 2. Compila i file .mo
php bin/compile-mo-files.php

# 3. Forza aggiornamento nel database
php bin/force-update-translations.php

# 4. Verifica con i test
php bin/test-translations.php
```

### 2. Verificare traduzioni

```bash
# Test completo via browser
http://tuosito.local/wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/test-translations.php

# O via WP-CLI
wp eval-file wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/bin/test-translations.php
```

### 3. Debugging traduzioni

Se i testi non appaiono in italiano:

```bash
# 1. Test per identificare il problema
php bin/test-translations.php

# 2. Se i file .mo sono vecchi, ricompila
php bin/compile-mo-files.php

# 3. Forza aggiornamento database
php bin/force-update-translations.php

# 4. Pulisci cache browser (Ctrl+Shift+Delete)
# 5. Ricarica pagina Settings (Ctrl+Shift+R)
```

---

## 📚 Documentazione Completa

Per maggiori dettagli:
- Generazione policy: [`/docs/GENERAZIONE-AUTOMATICA.md`](../docs/GENERAZIONE-AUTOMATICA.md)
- Fix traduzioni: [`/SOLUZIONE-TRADUZIONI-FP-PRIVACY.md`](../SOLUZIONE-TRADUZIONI-FP-PRIVACY.md)
