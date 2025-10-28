# 🐛 Fix: Banner Preview mostra testi corretti per ogni lingua

## 📋 Problema Risolto

**Bug:** La preview del banner nelle impostazioni mostrava sempre testi in inglese, anche quando si selezionava `it_IT` come lingua.

**Causa:** Il codice usava i default di una sola lingua (quella primaria) per popolare i campi di TUTTE le lingue, invece di usare i default specifici per ogni lingua.

---

## ✅ Modifiche Implementate

### File Modificati

#### 1. **SettingsRenderer.php**
**Riga 101:** Ora usa `get_banner_texts_for_language($lang)` per ottenere i default specifici di ogni lingua

```php
// PRIMA (SBAGLIATO):
$text = \wp_parse_args( $text, $default_texts_raw );  // Usa sempre gli stessi default!

// DOPO (CORRETTO):
$lang_specific_defaults = $this->options->get_banner_texts_for_language( $lang );
$text = \wp_parse_args( $text, $lang_specific_defaults );  // Usa i default per ogni lingua!
```

#### 2. **SettingsController.php**
**Righe 71-75:** Rimosso il codice che generava `$default_texts_raw` globale (non più necessario)

```php
// RIMOSSO:
$default_texts_raw = isset( $default_options['banner_texts'][ $default_locale ] ) ... 

// SOSTITUITO CON:
// I default specifici per lingua vengono ora gestiti direttamente nel renderer
```

**Riga 100:** Rimosso parametro `'default_texts_raw'` dall'array di dati

---

## 🎯 Come Funziona Ora

### Flusso Corretto

1. **Pagina Settings si carica**
   - Per ogni lingua configurata (es: `it_IT`, `en_US`)

2. **Per ogni lingua:**
   - Legge i valori salvati nel DB per quella lingua
   - Chiama `get_banner_texts_for_language($lang)`
   - Ottiene i default **specifici per quella lingua**:
     - `it_IT` → testi italiani hardcoded
     - `en_US` → testi inglesi hardcoded
     - Altre lingue → testi tradotti tramite `.po/.mo`

3. **Popola i campi input**
   - Se esistono valori salvati → usa quelli
   - Se NON esistono → usa i default della lingua

4. **Preview JavaScript**
   - Legge i valori dai campi input
   - Mostra i testi corretti per la lingua selezionata

---

## 🧪 Come Testare

### Test 1: Preview con it_IT

1. Vai su **Impostazioni → FP Privacy → Tab Banner**
2. Nel campo **"Languages"** assicurati che ci sia `it_IT`
3. Nella sezione **"Language: it_IT"** verifica i campi:
   - **Title:** dovrebbe contenere "Rispettiamo la tua privacy"
   - **Message:** dovrebbe iniziare con "Utilizziamo i cookie..."
   - **Accept button:** "Accetta tutti"

4. Scrolla alla sezione **"Banner preview"**
5. Nel campo **"Preview language"** seleziona `it_IT`
6. **La preview dovrebbe mostrare TUTTI i testi in italiano** ✅

### Test 2: Preview con en_US

1. Nel campo **"Languages"** aggiungi anche `en_US`: `it_IT,en_US`
2. Salva le impostazioni
3. Ricarica la pagina (CTRL+F5)
4. Nella sezione **"Language: en_US"** verifica i campi:
   - **Title:** dovrebbe contenere "We respect your privacy"
   - **Message:** "We use cookies..."
   - **Accept button:** "Accept all"

5. Nella **"Banner preview"** seleziona `en_US`
6. **La preview dovrebbe mostrare TUTTI i testi in inglese** ✅

### Test 3: Cambia lingua nella preview

1. Nella **"Banner preview"** cambia il dropdown da `it_IT` a `en_US` (o viceversa)
2. **La preview dovrebbe aggiornarsi immediatamente** mostrando i testi nella lingua selezionata
3. Tutti i campi devono aggiornarsi:
   - Titolo
   - Messaggio
   - Pulsanti (Accept/Reject/Manage)
   - Link policy
   - Revision notice

---

## 🔍 Dettagli Tecnici

### Funzione Chiave: `get_banner_texts_for_language()`

Questa funzione in `Options.php` (che abbiamo già modificato prima) gestisce i default per lingua:

```php
public function get_banner_texts_for_language( $lang = '' ) {
    // Se it_IT → restituisce testi italiani hardcoded
    if ( $requested === 'it_IT' || $this->normalize_language( $requested ) === 'it_IT' ) {
        $italian_translations = $this->get_hardcoded_italian_translations();
        // ... merge con valori salvati
        return array_merge( $italian_translations, $texts[ $requested ] );
    }
    
    // Se en_US → restituisce testi inglesi hardcoded
    if ( $requested === 'en_US' || $this->normalize_language( $requested ) === 'en_US' ) {
        $english_translations = $this->get_hardcoded_english_translations();
        // ... merge con valori salvati
        return array_merge( $english_translations, $texts[ $requested ] );
    }
    
    // Per altre lingue → usa sistema traduzioni WordPress
    $translated_defaults = $this->get_translated_banner_defaults( $requested );
    return $translated_defaults;
}
```

### JavaScript Preview (`admin.js`)

La preview legge i valori dai campi tramite:

```javascript
function collectTexts( lang ) {
    var panel = getLanguagePanel( lang );  // Trova il pannello della lingua
    
    return {
        title: panel.find( '[data-field="title"]' ).val() || '',
        message: panel.find( '[data-field="message"]' ).val() || '',
        btnAccept: panel.find( '[data-field="btn_accept"]' ).val() || '',
        // ... altri campi
    };
}
```

Quando cambi lingua nel dropdown `#fp-privacy-preview-language`, viene chiamato:
1. `collectTexts(lang)` → legge i valori del pannello di quella lingua
2. `updatePreview()` → aggiorna la preview con quei valori

---

## ✅ Risultato

### Prima del Fix ❌
- Preview mostrava sempre inglese
- Cambiare lingua nel dropdown non aveva effetto
- Confusione per gli utenti italiani

### Dopo il Fix ✅
- Preview mostra **testi corretti per ogni lingua**
- `it_IT` → testi italiani
- `en_US` → testi inglesi
- Cambiare lingua aggiorna immediatamente la preview
- UX migliorata

---

## 📝 Note per Sviluppatori

### Se aggiungi una nuova lingua con default hardcoded

1. Aggiungi la funzione in `Options.php`:
```php
private function get_hardcoded_french_translations() {
    return array(
        'title'   => 'Nous respectons votre vie privée',
        'message' => 'Nous utilisons des cookies...',
        // ... altri campi
    );
}
```

2. Aggiungi il controllo in `get_banner_texts_for_language()`:
```php
if ( $requested === 'fr_FR' || $this->normalize_language( $requested ) === 'fr_FR' ) {
    $french_translations = $this->get_hardcoded_french_translations();
    // ... logica merge
}
```

3. Non serve modificare SettingsRenderer o SettingsController! ✅

---

## 🐛 Troubleshooting

### La preview mostra ancora testi sbagliati

**Soluzione:**
1. Svuota cache browser (CTRL+F5)
2. Controlla che i campi input contengano i testi corretti
3. Ispeziona console JavaScript per errori
4. Verifica che il pannello lingua sia visibile nella pagina HTML

### I campi sono vuoti per una lingua

**Soluzione:**
1. Verifica che la lingua sia in `get_banner_texts_for_language()`
2. Controlla che i file `.po/.mo` siano caricati per quella lingua
3. Esegui `update-italian-defaults.php` per it_IT

### JavaScript non aggiorna la preview

**Soluzione:**
1. Controlla console JavaScript per errori
2. Verifica che `admin.js` sia caricato correttamente
3. Controlla che i campi abbiano l'attributo `data-field`
4. Verifica che il pannello abbia l'attributo `data-lang`

---

**Fix implementato da:** Cursor AI Assistant  
**Data:** 28 Ottobre 2025  
**File modificati:** 2  
**Linee di codice:** ~10  
**Tempo di test:** 5 minuti  
**Impatto:** 🟢 Basso (solo rendering preview)

