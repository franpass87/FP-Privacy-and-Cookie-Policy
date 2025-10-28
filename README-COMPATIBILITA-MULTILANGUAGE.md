# 🔄 Compatibilità FP-Privacy con FP-Multilanguage

## ✅ Integrazione Automatica

**FP-Privacy** ora include **compatibilità nativa** con **FP-Multilanguage**!

L'integrazione è **automatica** - non serve nessuna configurazione manuale. I due plugin rilevano automaticamente la presenza reciproca e si coordinano.

---

## 🎯 Cosa Fa l'Integrazione

### 1. Esclusione Pagine Privacy
Le pagine **Privacy Policy** e **Cookie Policy** create da FP-Privacy **NON vengono tradotte** automaticamente da FP-Multilanguage.

**Perché?**
- FP-Privacy gestisce già la multilingua internamente
- Evita duplicazioni e conflitti
- Ogni lingua ha la sua pagina nativa

**Risultato:**
```
FP-Privacy crea:
- /privacy-policy/ (italiano, ID: 123)
- /privacy-policy-en/ (inglese, ID: 456)

FP-Multilanguage NON creerà:
- /it/privacy-policy/ 
- /en/privacy-policy/
```

### 2. Sincronizzazione Lingua Corrente

Il banner cookie **si adatta automaticamente** alla lingua visualizzata dall'utente.

**Esempio:**
```
Utente visita /en/about-us/
↓
FP-Multilanguage imposta locale: en_US
↓
FP-Privacy rileva: en_US
↓
Banner mostra testi in inglese ✅
```

### 3. URL Tradotti

I link nel banner puntano alla **versione corretta** della pagina policy.

**Esempio:**
```php
Banner in italiano → Link: /privacy-policy/ (italiano)
Banner in inglese → Link: /privacy-policy-en/ (inglese)
```

---

## 🔧 Codice Implementato

### File Modificato: `src/Plugin.php`

Aggiunti **4 metodi** per la compatibilità:

#### 1. `setup_multilanguage_compatibility()`
Rileva se FP-Multilanguage è attivo e configura i hook.

```php
private function setup_multilanguage_compatibility() {
    $fpml_active = defined( 'FPML_VERSION' ) || class_exists( 'FP\MultiLanguage\Plugin' );
    
    if ( ! $fpml_active ) {
        return; // Skip se non attivo
    }
    
    // Configura i 3 hook di integrazione
}
```

#### 2. `exclude_privacy_pages_from_translation($skip, $post_id)`
Esclude pagine privacy dalla traduzione automatica.

```php
public function exclude_privacy_pages_from_translation( $skip, $post_id ) {
    // Se questo post_id è una pagina privacy/cookie
    // ritorna true per skipparla
}
```

Hook: `fpml_skip_post`

#### 3. `sync_locale_with_multilanguage($locale)`
Sincronizza il locale tra i due plugin.

```php
public function sync_locale_with_multilanguage( $locale ) {
    if ( function_exists( 'fpml_get_current_language' ) ) {
        return fpml_get_current_language();
    }
    return $locale;
}
```

Hook: `locale` (priorità 5)

#### 4. `translate_policy_url($url, $type, $lang)`
Traduce URL policy per il banner.

```php
public function translate_policy_url( $url, $type, $lang ) {
    // Ottieni ID pagina per la lingua
    // Ritorna permalink tradotto
}
```

Hook: `fp_privacy_policy_link_url`

---

## 🧪 Come Testare

### Test Automatico

Visita lo script di test:
```
http://fp-development.local/test-fp-integration.php
```

Lo script verifica:
- ✅ Plugin attivi
- ✅ Lingue configurate correttamente
- ✅ Pagine privacy escluse da traduzione
- ✅ Hook e filtri attivi

### Test Manuale

#### Test 1: Banner Multi-lingua
1. Vai su `/it/` o pagina italiana
2. **Verifica**: Banner in italiano?
3. Vai su `/en/` o pagina inglese  
4. **Verifica**: Banner in inglese?

#### Test 2: Pagine Privacy
1. Clicca sul link "Privacy Policy" nel banner in italiano
2. **Verifica**: Si apre la pagina italiana?
3. Cambia lingua e clicca di nuovo
4. **Verifica**: Si apre la pagina nella lingua corretta?

#### Test 3: Admin
1. Vai nelle impostazioni FP-Privacy
2. **Verifica**: Vedi tutte le lingue configurate?
3. Salva le impostazioni
4. **Verifica**: Nessun errore?

#### Test 4: No Duplicazioni
1. Vai in **Pagine → Tutte le pagine**
2. **Verifica**: Non ci sono pagine privacy duplicate?
3. Filtra per "Privacy Policy" o "Cookie Policy"
4. **Verifica**: Numero corretto di pagine (1 per lingua)?

---

## 📊 Configurazione Ottimale

### FP-Privacy Settings
```
Languages: it_IT, en_US
```

La lingua **it_IT viene sempre messa per prima** automaticamente, anche se l'utente inserisce `en_US, it_IT`.

### FP-Multilanguage Settings
```
Lingue attive: Italiano (it_IT), English (en_US)
Lingua default: Italiano
```

### Risultato Atteso
```
Banner cookie:
- Pagina /it/* → Testi italiani
- Pagina /en/* → Testi inglesi

Pagine privacy:
- /privacy-policy/ (italiano, gestita da FP-Privacy)
- /privacy-policy-en/ (inglese, gestita da FP-Privacy)
- NON duplicate da FP-Multilang ✅
```

---

## ⚠️ Troubleshooting

### Banner sempre in inglese
**Causa:** Cache o ordine di caricamento plugin  
**Soluzione:** 
1. Svuota cache WordPress
2. Ricarica pagina con CTRL+F5
3. Verifica che FP-Multilanguage sia attivo

### Pagine privacy duplicate
**Causa:** Hook non attivo  
**Soluzione:**
1. Disattiva FP-Privacy
2. Elimina pagine duplicate
3. Riattiva FP-Privacy
4. Verifica con test-fp-integration.php

### Link nel banner rotti
**Causa:** Pagine non create o ID errati  
**Soluzione:**
1. Vai in FP-Privacy → Tools → Policy Editor
2. Clicca "Regenerate All"
3. Verifica che le pagine esistano

---

## 🎯 Best Practices

### ✅ DO:
- Usa FP-Privacy per gestire banner cookie e policy
- Usa FP-Multilanguage per tradurre contenuto del sito
- Configura le stesse lingue in entrambi i plugin
- Testa banner su entrambe le lingue

### ❌ DON'T:
- Non cercare di tradurre le pagine privacy con FP-Multilang
- Non usare slug diversi per le lingue nei due plugin
- Non disabilitare l'integrazione automatica

---

## 📝 Note Tecniche

### Ordine di Caricamento
```
1. plugins_loaded (priorità 10): FP-Multilanguage
2. plugins_loaded (priorità 10): FP-Privacy boot()
   ↓
3. FP-Privacy rileva FP-Multilang
4. Setup hook di compatibilità
5. Hook 'locale' (priorità 5): Sincronizzazione lingua
```

### Hook Utilizzati
| Hook | Priorità | Scopo |
|------|----------|-------|
| `fpml_skip_post` | 10 | Esclude pagine privacy |
| `locale` | 5 | Sincronizza lingua |
| `fp_privacy_policy_link_url` | 10 | Traduce URL |

### Dipendenze
- ✅ Nessuna dipendenza hard (funziona anche se FP-Multilang non è attivo)
- ✅ Rilevamento automatico
- ✅ Graceful degradation

---

## 🚀 Deploy

Quando sposti in produzione:

1. **Verifica** che l'integrazione funzioni in locale (test script)
2. **Fai backup** database prima del deploy
3. **Attiva prima** FP-Multilanguage, poi FP-Privacy
4. **Verifica** che le pagine privacy non siano duplicate
5. **Testa** banner su tutte le lingue configurate

---

## 📖 Riferimenti

- FP-Privacy: `src/Plugin.php` (righe 304-418)
- FP-Multilanguage: Documentazione compatibilità plugin
- Test: `test-fp-integration.php`
- Analisi completa: `ANALISI-COMPATIBILITA-MULTILANGUAGE.md`

---

**Creato per FP-Privacy v0.1.2 + FP-Multilanguage v0.6.x**  
**Data: 28 Ottobre 2025**

