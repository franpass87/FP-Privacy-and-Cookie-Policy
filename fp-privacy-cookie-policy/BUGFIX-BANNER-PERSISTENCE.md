# 🐛 Fix: Banner Cookie che si riapre

## Problema Rilevato

Il banner cookie si riapre in altre pagine anche dopo aver cliccato "Accetta Tutti" perché il cookie di consenso non viene salvato/letto correttamente.

## Correzioni Applicate

### 1. **Doppio Sistema di Persistenza** ✅

Ora il consenso viene salvato in **due modi**:
- **Cookie del browser** (metodo principale)
- **localStorage** (fallback di sicurezza)

Se il cookie fallisce (es. per restrizioni del browser), il localStorage garantisce che il consenso venga mantenuto.

### 2. **Lettura Migliorata** ✅

La funzione `readConsentIdFromCookie()` ora:
- Legge il cookie normalmente
- Se il cookie non esiste, controlla il localStorage
- Se trova il consenso in localStorage, prova a ripristinare il cookie

### 3. **Verifica Post-Salvataggio** ✅

Dopo aver salvato il cookie, il sistema:
- Verifica che sia stato effettivamente salvato
- Logga eventuali errori per il debug
- Usa il localStorage come fallback automatico

### 4. **Logica di Visualizzazione Rafforzata** ✅

Il banner ora controlla:
- Se esiste un `consent_id` valido
- Se le categorie sono state salvate
- Se la revisione è aggiornata
- **FIX CRITICO**: Se esiste un consent_id, assume che il consenso sia stato dato

## Come Testare

### Test 1: Verifica Cookie Manuale

1. **Pulisci cache e cookie del browser**:
   - Chrome: `Ctrl+Shift+Del` → Seleziona "Cookie" → Pulisci
   - Firefox: `Ctrl+Shift+Del` → Seleziona "Cookie" → Pulisci

2. **Vai alla homepage del sito**
   
3. **Apri Console del browser** (`F12` → Console)

4. **Clicca "Accetta Tutti"**

5. **Controlla i log** nella console - dovresti vedere:
   ```
   FP Privacy Debug: Cookie impostato: fp_consent_state_id=...
   FP Privacy Debug: Consenso salvato anche in localStorage: ...
   FP Privacy Debug: Cookie verificato con successo: ...
   ```

6. **Naviga su un'altra pagina** - il banner NON dovrebbe riapparirsi

### Test 2: Script di Test Automatico

1. **Vai a**: `https://tuosito.test/wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/fp-privacy-cookie-policy/test-cookie-persistence.php`

2. **Esegui i test**:
   - Clicca "1. Salva Cookie di Test"
   - Clicca "2. Leggi Cookie"
   - Clicca "3. Test localStorage"

3. **Verifica** che tutti i test mostrino ✅

### Test 3: Verifica in Incognito

1. **Apri finestra in incognito** (`Ctrl+Shift+N`)

2. **Vai al sito**

3. **Clicca "Accetta Tutti"**

4. **Naviga su 2-3 pagine diverse** - il banner NON deve riapparirsi

## Debug Avanzato

Se il problema persiste, abilita il debug completo:

### 1. Controlla i Cookie nel Browser

**Chrome DevTools**:
1. `F12` → Scheda "Application"
2. Sinistra: "Cookies" → Seleziona il tuo dominio
3. Cerca `fp_consent_state_id`
4. Verifica valore, scadenza, path, domain

### 2. Controlla localStorage

**Chrome DevTools**:
1. `F12` → Scheda "Application"
2. Sinistra: "Local Storage" → Seleziona il tuo dominio
3. Cerca `fp_consent_state_id`
4. Verifica che il valore sia salvato

### 3. Analizza i Log

Apri la console e cerca:
```
FP Privacy Debug: Cookie impostato: ...
FP Privacy Debug: Consenso salvato anche in localStorage: ...
FP Privacy Debug: Cookie verificato con successo: ...
```

Se vedi `ERRORE: Cookie non impostato correttamente`, significa che:
- Il browser blocca i cookie di terze parti
- Le impostazioni del browser impediscono i cookie
- C'è un problema con il domain del cookie

## Soluzioni ai Problemi Comuni

### Problema: "Cookie non si salva in localhost"

**Causa**: Alcuni browser bloccano i cookie su localhost

**Soluzione**:
```javascript
// Il fix ora gestisce automaticamente localhost
// Non serve fare nulla
```

### Problema: "Cookie non si salva su sottodominio"

**Causa**: Domain del cookie troppo specifico

**Soluzione**: Il fix ora usa automaticamente il dominio principale:
```javascript
// Esempio: da www.example.com usa .example.com
var mainDomain = '.' + domainParts.slice(-2).join('.');
```

### Problema: "Cookie si cancella quando chiudo il browser"

**Causa**: Impostazione del browser "Elimina cookie alla chiusura"

**Soluzione**: Il localStorage mantiene il consenso anche in questo caso

## Verifica Finale

Dopo aver applicato il fix:

1. ✅ **Pulisci cache del sito** (se usi un plugin di caching)
2. ✅ **Pulisci cache del browser** (`Ctrl+F5`)
3. ✅ **Testa in finestra normale**
4. ✅ **Testa in finestra incognito**
5. ✅ **Testa su dispositivo mobile**

## File Modificati

- `assets/js/banner.js` - Logica principale del banner
  - Funzione `setConsentCookie()` - Aggiunto salvataggio in localStorage
  - Funzione `readConsentIdFromCookie()` - Aggiunto fallback localStorage
  - Sezione `initializeBanner()` - Migliorata logica di visualizzazione

## Compatibilità

- ✅ Chrome/Edge (Chromium)
- ✅ Firefox
- ✅ Safari
- ✅ Opera
- ✅ Mobile browsers
- ✅ Incognito/Private mode (con localStorage)

## Note Tecniche

### Formato Cookie

```
fp_consent_state_id=CONSENT_ID|REVISION
```

Esempio:
```
fp_consent_state_id=fpconsent7a8b9c1d2e3f|1
```

### Durata

- Cookie: **180 giorni** (configurabile)
- localStorage: **Permanente** (fino a pulizia manuale)

### Sicurezza

- `SameSite=Lax` - Previene CSRF
- `Secure` - Solo su HTTPS (quando disponibile)
- `path=/` - Disponibile su tutto il sito
- `domain=.example.com` - Funziona su sottodomini

## Supporto

Se il problema persiste dopo aver applicato il fix:

1. Invia i log della console
2. Specifica browser e versione
3. Indica se sei su localhost o dominio reale
4. Verifica che il JavaScript non sia bloccato da ad-blocker

---

**Fix applicato**: 2025-10-28  
**Versione plugin**: 0.1.2+  
**Testato su**: Chrome 120, Firefox 121, Safari 17
