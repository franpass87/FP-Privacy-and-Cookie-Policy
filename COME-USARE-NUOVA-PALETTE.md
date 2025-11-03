# 🎨 Come Usare la Nuova Palette Colori

## 🚀 Quick Start

### 1️⃣ Accedi alle Impostazioni
`WordPress Admin → Privacy & Cookie → Impostazioni → Tab "Banner e Aspetto" → Sezione "Palette"`

### 2️⃣ Ricarica la Pagina
**IMPORTANTE**: Fai **Hard Refresh** per caricare i nuovi file:
- **Windows**: `Ctrl + F5` o `Ctrl + Shift + R`
- **Mac**: `Cmd + Shift + R`

---

## 🎯 Due Modi per Scegliere i Colori

### Metodo 1: Visual (Gradiente) 🌈

1. **Click sul quadrato colorato** 🟦
2. Si apre la palette con il gradiente
3. Scegli il colore visualmente
4. Il campo HEX si aggiorna automaticamente
5. Click fuori per chiudere

**Nota**: Aprendo un picker, gli altri si chiudono automaticamente!

---

### Metodo 2: Codice HEX (Incolla) 📋

1. **Copia** il codice colore da Figma/Adobe/CSS (es: `#FF5733`)
2. **Click nel campo input HEX** (il campo grande sotto "📋 CODICE HEX")
3. **Incolla** il codice (`Ctrl+V`)
4. Vedrai:
   - ✅ Badge "✓ Valido" (se corretto)
   - 🟢 Bordo verde temporaneo
   - 🎨 Animazione di sfondo
   - Il quadrato colorato si aggiorna automaticamente

**Formati accettati**:
```
FF5733    →  #FF5733  ✅
#FF5733   →  #FF5733  ✅
ff5733    →  #FF5733  ✅ (convertito in maiuscolo)
#ff5733   →  #FF5733  ✅
```

---

## 🎨 Layout Interfaccia

Ogni colore mostra:

```
┌────────────────────────────────────┐
│  SFONDO BANNER                     │  ← Nome colore
├────────────────────────────────────┤
│                                    │
│  📋 CODICE HEX                     │  ← Etichetta
│  ┌──────┐  ┌──────────────────┐   │
│  │  🟦  │  │   #FFFFFF        │   │  ← Quadrato + Input
│  └──────┘  └──────────────────┘   │
│             [Predefinito]          │  ← Pulsante reset
└────────────────────────────────────┘
```

---

## ✨ Caratteristiche Smart

### Auto-Format
Digita `ff5733` → Diventa automaticamente `#FF5733`

### Validazione Real-Time
- Codice valido = Bordo verde + Badge ✓
- Codice invalido = Bordo rosso
- Incompleto = Nessun feedback (continua a digitare)

### Sincronizzazione
Campo HEX ↔ Picker Visuale sempre sincronizzati!

### Auto-Close
Apertura di un picker chiude automaticamente gli altri (solo uno aperto alla volta)

---

## 🔧 Troubleshooting

### "Non vedo i campi HEX"
**Soluzione**: Hard refresh del browser (`Ctrl+F5`)

### "Cliccando sul campo HEX si apre la palette"
**Soluzione**: 
1. Hard refresh (`Ctrl+F5`)
2. Se persiste, svuota cache browser
3. Verifica Console browser (`F12`) per errori

### "I picker sono tutti aperti"
**Soluzione**:
1. Hard refresh (`Ctrl+F5`)
2. Se persiste, apri Console (`F12`) e digita:
   ```javascript
   jQuery('.fp-privacy-palette .wp-picker-holder').hide();
   ```

### "Il campo HEX sparisce"
**Soluzione**: Hard refresh - il MutationObserver garantirà la visibilità permanente

---

## 🎓 Tips & Tricks

### Copia Colore da Sito Web
1. Usa DevTools del browser (`F12`)
2. Click sull'icona "Selettore colore" 🎨
3. Click sul colore desiderato
4. Copia il codice HEX
5. Incolla nel campo FP Privacy

### Palette Coordinata
Usa strumenti come:
- **Coolors.co** - Generatore palette
- **Adobe Color** - Ruota cromatica
- **Material Design Colors** - Palette Google

### Accessibilità
Verifica contrasto con **WebAIM Contrast Checker** per garantire leggibilità testi.

---

## 🔥 Workflow Consigliato

### Per Designer
1. Crea palette in Figma/Adobe
2. Esporta codici HEX
3. Incolla direttamente nei campi FP Privacy
4. Salva impostazioni

### Per Developer
1. Usa variabili CSS dal tema
2. Copia valori HEX
3. Incolla in FP Privacy
4. Mantieni coerenza brand

---

## 📸 Esempi Pratici

### Tema Chiaro
```
Sfondo banner:           #FFFFFF
Testo banner:            #1F2937
Pulsante primario BG:    #2563EB
Pulsante primario Text:  #FFFFFF
Link:                    #2563EB
```

### Tema Scuro
```
Sfondo banner:           #1F2937
Testo banner:            #F9FAFB
Pulsante primario BG:    #3B82F6
Pulsante primario Text:  #FFFFFF
Link:                    #60A5FA
```

---

## ✅ Checklist Prima di Salvare

- [ ] Contrasto testo/sfondo sufficiente (min 4.5:1)
- [ ] Pulsanti ben visibili
- [ ] Link distinguibili
- [ ] Testato in Preview banner
- [ ] Testato mobile e desktop

---

## 🆘 Supporto

Problemi? Controlla:
1. `BUGFIX-COLOR-PICKER-PROFONDO.md` - Dettagli tecnici
2. `MIGLIORAMENTI-PALETTE-COLORI.md` - Changelog completo
3. Console browser (`F12`) - Errori JavaScript

---

**Buon lavoro! 🎨**

