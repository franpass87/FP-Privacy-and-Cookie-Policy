# 🚀 Guida Rapida: Deploy Automatico Plugin WordPress

## Setup Iniziale (Da fare una sola volta)

### Su WordPress:

1. **Installa Git Updater**:
   ```
   - Scarica: https://github.com/afragen/git-updater/releases/latest
   - Carica su WordPress → Plugin → Aggiungi nuovo → Carica plugin
   - Attiva il plugin
   ```

2. **Attiva Auto-Update**:
   ```
   - Vai su Plugin → Plugin installati
   - Trova "FP Privacy and Cookie Policy"
   - Clicca "Abilita aggiornamenti automatici"
   ```

✅ **Fatto!** Ora WordPress riceverà automaticamente gli aggiornamenti da GitHub.

---

## Rilasciare una Nuova Versione

### Ogni volta che vuoi deployare:

1. **Aggiorna versione** in `fp-privacy-cookie-policy/fp-privacy-cookie-policy.php`:
   ```php
   * Version: 0.1.2  // ← Cambia qui
   
   define( 'FP_PRIVACY_PLUGIN_VERSION', '0.1.2' );  // ← E qui
   ```

2. **Fai merge su main**:
   ```bash
   git add .
   git commit -m "Release v0.1.2"
   git push origin main
   ```

3. **Aspetta 2-5 minuti**:
   - GitHub Actions compila il plugin
   - Crea automaticamente la release
   - WordPress riceverà l'aggiornamento entro 12 ore

---

## ⚡ Forzare Update Immediato

Se non vuoi aspettare 12 ore:

1. Vai su WordPress → **Dashboard → Aggiornamenti**
2. Clicca **"Controlla di nuovo"**
3. L'aggiornamento apparirà immediatamente

---

## 🔍 Verificare che Funzioni

### Dopo il push:

1. **GitHub**: Vai su [Actions](https://github.com/franpass87/FP-Privacy-and-Cookie-Policy/actions)
   - Il workflow deve completarsi con ✅

2. **GitHub**: Vai su [Releases](https://github.com/franpass87/FP-Privacy-and-Cookie-Policy/releases)
   - La nuova versione deve apparire

3. **WordPress**: Vai su **Dashboard → Aggiornamenti**
   - Clicca "Controlla di nuovo"
   - Dovresti vedere l'aggiornamento disponibile

---

## 🎯 Esempio Completo

```bash
# 1. Modifica il codice
vim fp-privacy-cookie-policy/src/Admin/Settings.php

# 2. Aggiorna versione
vim fp-privacy-cookie-policy/fp-privacy-cookie-policy.php
# Cambia: Version: 0.1.2
# Cambia: FP_PRIVACY_PLUGIN_VERSION, '0.1.2'

# 3. Commit e push
git add .
git commit -m "Add new admin settings - v0.1.2"
git push origin main

# 4. Aspetta 2-5 minuti e controlla:
# https://github.com/franpass87/FP-Privacy-and-Cookie-Policy/actions
```

**FATTO!** 🎉 Il plugin si aggiornerà automaticamente su WordPress.

---

## ❓ Problemi?

Vedi la [guida completa](./DEPLOY-AUTOMATICO-GITHUB.md) per troubleshooting dettagliato.
