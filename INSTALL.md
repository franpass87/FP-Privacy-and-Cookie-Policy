# 📦 GUIDA INSTALLAZIONE - FP Privacy & Cookie Policy

**Versione**: 0.4.4  
**Tempo Installazione**: 5 minuti  
**Difficoltà**: ⭐ Facile

---

## 🎯 PREREQUISITI

Prima di iniziare, verifica di avere:

- ✅ WordPress 6.2 o superiore
- ✅ PHP 8.0 o superiore  
- ✅ MySQL 5.6 o superiore
- ✅ Accesso SSH/FTP (per Composer)
- ✅ Accesso Admin WordPress

---

## 📥 METODO 1: Installazione da Junction (Development)

### Già fatto! ✅

Se stai usando il sistema junction:

```powershell
# Junction già creata da:
$WPPlugins = "C:\Users\franc\Local Sites\fp-development\app\public\wp-content\plugins"
$PluginName = "FP-Privacy-and-Cookie-Policy-1"
$LAB = "C:\Users\franc\OneDrive\Desktop\FP-Privacy-and-Cookie-Policy-1"

# Junction attiva:
wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/
  → C:\Users\franc\OneDrive\Desktop\FP-Privacy-and-Cookie-Policy-1\
```

**Prossimo passo**: Vai a "Attivazione"

---

## 📥 METODO 2: Installazione Manuale (Production)

### Passo 1: Upload File

```bash
# Via FTP/SFTP
Upload cartella in:
  wp-content/plugins/FP-Privacy-and-Cookie-Policy/

# Via SSH
cd wp-content/plugins/
git clone https://github.com/franpass87/FP-Privacy-and-Cookie-Policy.git
```

### Passo 2: Composer

```bash
cd wp-content/plugins/FP-Privacy-and-Cookie-Policy/fp-privacy-cookie-policy/
composer install --no-dev --optimize-autoloader
```

**Importante**: `--no-dev` per produzione (no dev dependencies)

---

## ⚙️ ATTIVAZIONE

### Da WordPress Admin

```
1. Login WordPress Admin
2. Plugin → Plugin installati
3. Cerca "FP Privacy and Cookie Policy"
4. Click "Attiva"
5. Attendi messaggio "Plugin attivato"
```

### Via WP-CLI

```bash
wp plugin activate FP-Privacy-and-Cookie-Policy
```

---

## 🎨 CONFIGURAZIONE INIZIALE

### Setup Guidato (5 minuti)

#### 1. Lingue (30 sec)

```
Privacy & Cookie → Settings

Languages:
  [it_IT,en_US_____________________]
  
→ Save settings
```

#### 2. Colori Brand (1 min)

```
Settings → Scroll to "Palette"

Primary Bg:  [●──] Click quadratino
             → Scegli colore brand con Color Picker
             → Vedi preview live aggiornata!

→ Save settings
```

#### 3. Testi Banner (2 min - Opzionale)

```
Settings → Banner content

Language: it_IT
  Title:   [Rispettiamo la tua privacy____]
  Message: [Utilizziamo i cookie...______]
  
Language: en_US
  Title:   [We respect your privacy_____]
  Message: [We use cookies...___________]

→ Vedi preview mentre scrivi!
→ Save settings
```

#### 4. Policy Generation (1 min)

```
Privacy & Cookie → Tools

→ Click "Generate Policies"
→ Attendi completamento
→ Policy create automaticamente!
```

---

## ✅ VERIFICA INSTALLAZIONE

### Test 1: Admin (30 sec)

```
Privacy & Cookie → Settings

DEVI VEDERE:
✅ 10+ sezioni (Languages, Banner, Preview, Palette...)
✅ Color Picker (quadratini colorati)
✅ Preview banner interattiva
✅ [Save settings] button

SE VEDI SOLO TITOLO: Disattiva e riattiva plugin
```

### Test 2: Analytics (20 sec)

```
Privacy & Cookie → Analytics

DEVI VEDERE:
✅ 4 stat cards colorate
✅ 4 grafici Chart.js
✅ Tabella consensi

SE NON VEDI: Cache browser (Ctrl+F5)
```

### Test 3: Frontend (1 min)

```
1. Apri sito in incognito (Ctrl+Shift+N)
2. Banner appare in bottom? ✅
3. Click "Accetta Tutti"
4. Banner sparisce SUBITO? ✅
5. F12 → Application → Cookies
6. Cookie "fp_consent_state_id" presente? ✅
7. Naviga su altra pagina
8. Banner NON riappare? ✅
```

**Se tutti ✅**: 🎉 **INSTALLAZIONE RIUSCITA!**

---

## 🔧 CONFIGURAZIONE AVANZATA

### Google Consent Mode v2

```
Settings → Consent Mode defaults

analytics_storage:      [denied ▼]
ad_storage:             [denied ▼]
ad_user_data:           [denied ▼]
ad_personalization:     [denied ▼]
functionality_storage:  [granted ▼]
personalization_storage:[denied ▼]
security_storage:       [granted ▼] (sempre)

→ Save
```

**Consigliato**: Tutto "denied" di default (privacy-first)

### Global Privacy Control

```
Settings → Global Privacy Control (GPC)

☑ Honor Global Privacy Control

Info: Rispetta automaticamente segnale Sec-GPC: 1 del browser
```

**Consigliato**: Attivato (best practice)

### Retention Policy

```
Settings → Retention & Revision

Retention days: [180_]

Info: Dopo 180 giorni i log consensi vengono cancellati automaticamente
```

**Consigliato**: 180 giorni (6 mesi)

---

## 🔌 INTEGRAZIONE FP PERFORMANCE

### Auto-Detection

**Nessuna configurazione necessaria!**

Se hai FP Performance Suite installato:
- ✅ Rilevamento automatico
- ✅ Esclusione asset privacy
- ✅ Disabilita ottimizzazioni durante banner
- ✅ Riattiva dopo consenso

**Verifica**:
```
1. Attiva entrambi i plugin
2. Frontend senza consenso → HTML non minificato
3. Dai consenso → Ricarica pagina
4. Visualizza sorgente → HTML minificato ✅
```

---

## 📊 PRIMA ANALYTICS

### Dopo 24-48 ore

```
Privacy & Cookie → Analytics

Controlla:
- Consent rate (accept vs reject)
- Trend temporale
- Categorie più/meno accettate
- Lingue prevalenti
```

**Ottimizza**:
- Se accept rate < 70% → Migliora testi banner
- Se marketing consent basso → Migliora descrizione categoria

---

## 🆘 PROBLEMI?

### "Pagine admin vuote"

**Soluzione**:
```
1. Disattiva plugin
2. Riattiva plugin
3. Ctrl+F5 (refresh cache browser)
```

### "Color picker non si apre"

**Soluzione**:
```
Ctrl+F5 (refresh cache browser)
```

### "Analytics non mostra grafici"

**Soluzione**:
```
1. Verifica connessione internet (Chart.js da CDN)
2. Ctrl+F5 (refresh cache)
3. F12 → Console → Cerca errori
```

### "Banner si riapre"

✅ **RISOLTO** in v0.1.2 - Aggiorna plugin

---

## 📚 PROSSIMI PASSI

Dopo installazione base:

1. **Personalizza categorie cookie**:
   - Settings → Categories
   - Aggiungi/modifica categorie

2. **Configura script blocking**:
   - Settings → Script blocking
   - Aggiungi regole per servizi di terze parti

3. **Monitora analytics**:
   - Analytics page
   - Controlla consent rate settimanalmente

4. **Ottimizza conversion**:
   - Modifica testi basandoti su dati
   - Testa varianti

---

## ✅ CHECKLIST POST-INSTALLAZIONE

- [ ] Plugin attivato senza errori
- [ ] Settings configurate (lingue, colori)
- [ ] Policy generate
- [ ] Banner testato su frontend
- [ ] Cookie salvato correttamente
- [ ] Analytics funzionante
- [ ] Color picker provato
- [ ] Preview live verificata
- [ ] Mobile responsive testato

**Tutti ✅**: 🎉 **PRONTO PER PRODUZIONE!**

---

## 📞 SUPPORTO

### Documentazione Completa

- `README.md` - Panoramica generale
- `CHANGELOG.md` - Storico modifiche
- `docs/` - Documentazione dettagliata

### WP-CLI

```bash
wp help fp-privacy
wp fp-privacy status
```

---

**Guida creata**: 28 Ottobre 2025  
**Versione**: 1.0  
**Tempo lettura**: 3 minuti

