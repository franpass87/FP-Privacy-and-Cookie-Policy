# ⚠️ ELIMINA CARTELLA DUPLICATA

**Urgenza**: Media  
**Tempo**: 30 secondi  
**Azione**: Manuale

---

## 🎯 COSA FARE

C'è una cartella duplicata **vuota** da eliminare manualmente:

```
C:\Users\franc\Local Sites\fp-development\app\public\wp-content\plugins\FP-Privacy-and-Cookie-Policy-1\
```

---

## 📋 PROCEDURA

### Opzione 1: File Explorer (Consigliata)

```
1. Apri File Explorer
2. Vai su:
   C:\Users\franc\Local Sites\fp-development\app\public\wp-content\plugins\
3. Trova cartella: FP-Privacy-and-Cookie-Policy-1
4. Click destro → Elimina
5. FATTO! ✅
```

### Opzione 2: PowerShell

```powershell
Remove-Item -Path "C:\Users\franc\Local Sites\fp-development\app\public\wp-content\plugins\FP-Privacy-and-Cookie-Policy-1" -Recurse -Force
```

---

## ✅ PLUGIN CORRETTO

Il plugin da usare è:

```
✅ FP-Privacy-and-Cookie-Policy
   → Questo è quello COMPLETO con tutto il codice
   → Ha il file fp-privacy-cookie-policy.php
   → Ha la sottocartella fp-privacy-cookie-policy/
   → Ha tutta la documentazione
```

**NON usare**:

```
❌ FP-Privacy-and-Cookie-Policy-1
   → Era una junction iniziale
   → Ora è vuota (2 file già spostati)
   → Safe da eliminare
```

---

## 🔍 VERIFICA

Dopo eliminazione, in WordPress Admin dovresti vedere:

```
Plugin → Plugin installati

✅ FP Privacy and Cookie Policy    (1 solo plugin)
```

Se vedi 2 plugin "FP Privacy":
- Disattiva quello "-1"
- Eliminalo da WordPress
- Poi cancella la cartella manualmente

---

## 📝 COSA HO FATTO

Ho già:

1. ✅ Copiato i 2 file importanti nella cartella corretta:
   - `ANALISI-GDPR-COMPLIANCE.md` → `docs/session-2025-10-28/GDPR-COMPLIANCE.md`
   - `ROADMAP-MIGLIORAMENTI.md` → `docs/session-2025-10-28/ROADMAP-MIGLIORAMENTI.md`

2. ✅ Eliminato i file dalla cartella `-1`

3. ✅ Verificato cartella `-1` è vuota

**Manca solo**: Eliminare la cartella vuota (manuale)

---

## ⚡ DOPO ELIMINAZIONE

```
1. Ricarica pagina Plugin in WordPress Admin
2. Verifica vedi 1 solo plugin privacy
3. Tutto OK! ✅
```

---

**Creato**: 28 Ottobre 2025  
**Priorità**: Media (non urgente, ma pulisce l'ambiente)

