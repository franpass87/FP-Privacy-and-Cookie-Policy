# 🚀 Guida: Creare Release su GitHub per fp-git-updater

## 📋 Problema

Quando installi il plugin tramite `fp-git-updater` da GitHub, ricevi sempre la versione **1.0** invece della versione corrente **0.1.2**.

**Causa:** Non esistono release taggate su GitHub con la versione corrente.

---

## ✅ Soluzione

Per far vedere la versione corretta (0.1.2) a `fp-git-updater`, devi creare una **GitHub Release** con il tag corrispondente.

---

## 🛠️ Metodo 1: Script Automatico (Raccomandato)

### Passo 1: Esegui lo script PowerShell

1. Apri PowerShell nella cartella del plugin LAB:
```powershell
cd "C:\Users\franc\OneDrive\Desktop\FP-Privacy-and-Cookie-Policy-1"
```

2. Esegui lo script:
```powershell
.\create-release.ps1
```

3. Lo script farà automaticamente:
   - ✅ Commit di tutte le modifiche
   - ✅ Creazione del tag `v0.1.2`
   - ✅ Push su GitHub (branch main)
   - ✅ Push del tag

### Passo 2: Crea la Release su GitHub

1. Vai su: https://github.com/franpass87/FP-Privacy-and-Cookie-Policy/releases/new

2. **Choose a tag**: Seleziona `v0.1.2` (appena creato)

3. **Release title**: `v0.1.2 - Italiano di Default`

4. **Description**:
```markdown
## 🇮🇹 Versione Italiana di Default

### Novità Principali
- ✅ Testi banner in italiano di default
- ✅ Cookie Policy generata in italiano
- ✅ Privacy Policy generata in italiano
- ✅ Preview banner multilingua funzionante
- ✅ Sezione Palette migliorata
- ✅ Fix banner preview per ogni lingua
- ✅ Compatibilità completa con FP-Multilanguage

### File Modificati
- `src/Utils/Options.php` - Default italiani hardcoded
- `src/Utils/Validator.php` - Default per lingua specifica
- `src/Admin/SettingsRenderer.php` - Preview multilingua
- `src/Admin/SettingsController.php` - Pulizia codice
- `templates/cookie-policy.php` - Template italiano
- `templates/privacy-policy.php` - Template italiano
- `assets/css/admin.css` - Palette migliorata

### Conformità
- ✅ GDPR (UE) 2016/679
- ✅ ePrivacy Directive 2002/58/CE
- ✅ Linee guida EDPB ottobre 2025

### Installazione
Usa `fp-git-updater` o scarica lo ZIP dalla release.
```

5. **Publish release** ✅

---

## 🛠️ Metodo 2: Manuale (Alternativa)

### Passo 1: Commit e Push

```bash
cd "C:\Users\franc\OneDrive\Desktop\FP-Privacy-and-Cookie-Policy-1"

# Aggiungi modifiche
git add .

# Commit
git commit -m "Release v0.1.2 - Italiano di Default"

# Push
git push origin main
```

### Passo 2: Crea Tag

```bash
# Crea tag
git tag -a v0.1.2 -m "Release v0.1.2 - Italiano di Default"

# Push tag
git push origin v0.1.2
```

### Passo 3: Crea Release su GitHub

Segui il **Passo 2** del Metodo 1 sopra.

---

## 🔍 Verifica

### Dopo aver creato la release:

1. Vai su: https://github.com/franpass87/FP-Privacy-and-Cookie-Policy/releases
2. Dovresti vedere: **v0.1.2 - Italiano di Default** come ultima release

### In WordPress:

1. Vai su **Plugin → Plugin installati**
2. Cerca **FP Privacy and Cookie Policy**
3. Se installato tramite `fp-git-updater`, dovresti vedere:
   - **Versione**: 0.1.2
   - **Aggiornamento disponibile**: NO (se è l'ultima)

---

## 📚 Come funziona fp-git-updater

Il plugin `fp-git-updater` legge le informazioni dalle **GitHub API**:

1. **Versione installata**: Legge `Version: 0.1.2` dal file `fp-privacy-cookie-policy.php`

2. **Versione disponibile**: Controlla le release su:
   ```
   https://api.github.com/repos/franpass87/FP-Privacy-and-Cookie-Policy/releases/latest
   ```

3. **Confronto**: Se la versione su GitHub è più recente, mostra "Aggiornamento disponibile"

4. **Download**: Scarica lo ZIP dalla release (o dal branch se non ci sono asset)

### Headers necessari nel plugin:

```php
/**
 * Plugin Name: FP Privacy and Cookie Policy
 * Version: 0.1.2
 * GitHub Plugin URI: franpass87/FP-Privacy-and-Cookie-Policy
 * Primary Branch: main
 * Release Asset: true
 */
```

---

## 🎯 Best Practices

### Versioning

Usa **Semantic Versioning** (semver):
- **MAJOR** (1.0.0): Breaking changes
- **MINOR** (0.1.0): Nuove funzionalità backward-compatible
- **PATCH** (0.0.1): Bug fixes

Esempio:
- `0.1.2` → Bug fix
- `0.2.0` → Nuova funzionalità
- `1.0.0` → Prima versione stabile

### Tag Git

Usa sempre il prefisso `v`:
- ✅ `v0.1.2`
- ✅ `v1.0.0`
- ❌ `0.1.2` (funziona ma non è standard)

### Changelog

Mantieni un file `CHANGELOG.md` aggiornato:

```markdown
## [0.1.2] - 2025-10-28

### Added
- Testi banner in italiano di default
- Cookie Policy e Privacy Policy in italiano

### Fixed
- Preview banner multilingua
- Sezione Palette visualizzazione

### Changed
- Default italiani hardcoded invece di traduzioni
```

---

## 🐛 Troubleshooting

### "Tag già esistente"

```bash
# Elimina tag locale
git tag -d v0.1.2

# Elimina tag remoto
git push origin :refs/tags/v0.1.2

# Ricrea tag
git tag -a v0.1.2 -m "Release v0.1.2"
git push origin v0.1.2
```

### "Permission denied" su push

Verifica le credenziali GitHub:
```bash
git remote -v
# Dovrebbe mostrare: https://github.com/franpass87/...

# O usa SSH:
git remote set-url origin git@github.com:franpass87/FP-Privacy-and-Cookie-Policy.git
```

### "fp-git-updater non vede la nuova versione"

1. Svuota cache di WordPress
2. Ricarica la pagina Plugin (CTRL+F5)
3. Verifica che la release sia "Latest" su GitHub
4. Controlla che il tag sia `v0.1.2` (con la v)

### "Download fallito"

Se `Release Asset: true` nel header, fp-git-updater cerca un file .zip nella release.

**Soluzioni:**
- Cambia a `Release Asset: false` (scarica dal branch)
- O carica un file .zip nella release su GitHub

---

## 📝 Script Disponibili

### `create-release.ps1` (PowerShell)
Script automatico che fa tutto:
- Commit
- Tag
- Push

### `create-release.bat` (Windows Batch)
Alternativa semplice per Windows.

### `tools/bump-version.php` (PHP)
Aggiorna automaticamente il numero di versione:

```bash
# Incrementa patch (0.1.2 → 0.1.3)
php tools/bump-version.php --patch

# Incrementa minor (0.1.2 → 0.2.0)
php tools/bump-version.php --minor

# Imposta versione specifica
php tools/bump-version.php --set=1.0.0
```

---

## ✅ Checklist Release

Prima di pubblicare una release:

- [ ] Tutte le modifiche committate
- [ ] Versione aggiornata in `fp-privacy-cookie-policy.php`
- [ ] Versione aggiornata in `readme.txt` (Stable tag)
- [ ] CHANGELOG.md aggiornato
- [ ] Test del plugin effettuati
- [ ] Nessun errore di linter
- [ ] Tag Git creato (`v0.1.2`)
- [ ] Push su GitHub completato
- [ ] Release pubblicata su GitHub
- [ ] Verificato che fp-git-updater veda la nuova versione

---

**Documento creato:** 28 Ottobre 2025  
**Plugin:** FP Privacy and Cookie Policy  
**Versione corrente:** 0.1.2  
**Repository:** https://github.com/franpass87/FP-Privacy-and-Cookie-Policy

