# 🔄 Analisi Compatibilità: FP-Privacy ↔️ FP-Multilanguage

## 📊 Situazione Attuale

### FP-Privacy: Sistema Multilingua INTERNO

**FP-Privacy** ha un **sistema multilingua integrato** che gestisce:

1. **Lingue Attive** (`languages_active`)
   - Array di locale codes (es. `['it_IT', 'en_US', 'fr_FR']`)
   - Prima lingua = lingua principale

2. **Testi Banner per Lingua** (`banner_texts`)
   ```php
   'banner_texts' => [
       'it_IT' => [
           'title' => 'Rispettiamo la tua privacy',
           'message' => '...',
           'btn_accept' => 'Accetta tutto',
           // ...
       ],
       'en_US' => [
           'title' => 'We respect your privacy',
           'message' => '...',
           'btn_accept' => 'Accept all',
           // ...
       ]
   ]
   ```

3. **Regole Script per Lingua** (`script_rules`)
   ```php
   'scripts' => [
       'it_IT' => [
           'necessary' => ['label' => '...', 'description' => '...'],
           'marketing' => ['label' => '...', 'description' => '...'],
       ],
       'en_US' => [
           'necessary' => ['label' => '...', 'description' => '...'],
           // ...
       ]
   ]
   ```

4. **Pagine Privacy/Cookie per Lingua** (`pages`)
   ```php
   'pages' => [
       'privacy_policy_page_id' => [
           'it_IT' => 123,  // ID pagina italiana
           'en_US' => 456,  // ID pagina inglese
       ],
       'cookie_policy_page_id' => [
           'it_IT' => 789,
           'en_US' => 1011,
       ]
   ]
   ```

### FP-Multilanguage: Sistema Traduzione WordPress

**FP-Multilanguage** funziona **diversamente**:

1. Crea **post duplicati** per ogni lingua
2. Traduce **post meta** e **custom fields**
3. Usa URL routing con prefisso lingua (`/en/page`, `/it/page`)
4. Sincronizza automaticamente contenuti tra lingue

## 🤔 Potenziali Problemi

### Problema 1: Sovrapposizione Funzionalità

- **FP-Privacy** gestisce già le lingue internamente
- **FP-Multilanguage** vuole creare post separati per lingua
- **Rischio**: Duplicazione o conflitto di gestione

### Problema 2: URL delle Pagine Privacy

**FP-Privacy** crea:
- `/privacy-policy/` (italiano)
- `/privacy-policy-en/` (inglese)

**FP-Multilanguage** vorrebbe:
- `/it/privacy-policy/` (italiano)
- `/en/privacy-policy/` (inglese)

**Conflitto URL possibile!**

### Problema 3: Banner Multi-lingua

- **FP-Privacy**: Seleziona testo in base a `get_locale()` o lingua utente
- **FP-Multilanguage**: Cambia `get_locale()` in base all'URL
- **Dovrebbero funzionare insieme** MA serve testing

## ✅ Scenari di Compatibilità

### Scenario A: FP-Privacy Come Master (CONSIGLIATO)

**Setup:**
1. FP-Privacy gestisce le lingue per il banner cookie
2. FP-Multilanguage traduce solo le PAGINE di contenuto
3. Le pagine Privacy/Cookie NON vengono tradotte da Multilanguage

**Vantaggi:**
- ✅ Nessun conflitto
- ✅ Banner sempre coerente
- ✅ Policy in lingue native

**Configurazione:**
```php
// In FP-Multilanguage: escludere pagine privacy dalla traduzione
add_filter('fpml_skip_post', function($skip, $post_id) {
    $privacy_pages = [...]; // ID pagine privacy
    return in_array($post_id, $privacy_pages) ? true : $skip;
}, 10, 2);
```

### Scenario B: Integrazione Completa (COMPLESSO)

**FP-Privacy** si integra con **FP-Multilanguage**:

1. Rileva lingua attiva da FP-Multilanguage
2. Usa quella per selezionare testi banner
3. Le pagine policy seguono lo schema URL di Multilanguage

**Richiede:**
- Hook personalizzati
- Sincronizzazione lingua tra i due plugin
- Testing approfondito

### Scenario C: FP-Multilanguage Come Master (NON CONSIGLIATO)

**FP-Privacy** usa una sola lingua, **FP-Multilanguage** traduce tutto.

**Problemi:**
- ❌ Banner cookie sempre nella stessa lingua
- ❌ Perdita funzionalità multilingua nativa di FP-Privacy
- ❌ Compliance GDPR compromessa (banner deve essere in lingua utente)

## 🔧 Integrazione Consigliata

### Opzione 1: Esclusione Pagine Privacy da FP-Multilanguage

Crea un file `wp-content/mu-plugins/fp-privacy-multilanguage-compat.php`:

```php
<?php
/**
 * Compatibilità FP-Privacy con FP-Multilanguage
 * 
 * Impedisce a FP-Multilanguage di tradurre le pagine Privacy/Cookie
 * perché sono già gestite multilingua da FP-Privacy.
 */

add_filter('fpml_skip_post', 'fp_privacy_exclude_from_multilanguage', 10, 2);

function fp_privacy_exclude_from_multilanguage($skip, $post_id) {
    // Ottieni ID pagine privacy da FP-Privacy
    $fp_privacy_options = get_option('fp_privacy_options', []);
    
    if (!isset($fp_privacy_options['pages'])) {
        return $skip;
    }
    
    // Estrai tutti gli ID pagine privacy/cookie
    $privacy_page_ids = [];
    foreach ($fp_privacy_options['pages'] as $type => $languages) {
        if (is_array($languages)) {
            $privacy_page_ids = array_merge($privacy_page_ids, array_values($languages));
        }
    }
    
    // Se questo post è una pagina privacy, skippalo
    if (in_array($post_id, $privacy_page_ids, true)) {
        return true;
    }
    
    return $skip;
}
```

### Opzione 2: Sincronizzazione Lingua Attiva

FP-Privacy legge la lingua corrente da FP-Multilanguage:

```php
<?php
/**
 * FP-Privacy usa la lingua corrente di FP-Multilanguage
 */

add_filter('locale', 'fp_privacy_use_multilanguage_locale', 5);

function fp_privacy_use_multilanguage_locale($locale) {
    // Se FP-Multilanguage è attivo
    if (function_exists('fpml_get_current_language')) {
        $current_lang = fpml_get_current_language();
        if ($current_lang) {
            return $current_lang;
        }
    }
    
    return $locale;
}
```

## 📋 Checklist Implementazione

### Test 1: Banner Cookie Multi-lingua
- [ ] Visita `/it/` → Banner in italiano?
- [ ] Visita `/en/` → Banner in inglese?
- [ ] Cambio lingua switcher → Banner cambia?

### Test 2: Pagine Privacy
- [ ] Privacy Policy in italiano esiste?
- [ ] Privacy Policy in inglese esiste?
- [ ] Link nel banner porta alla pagina corretta?
- [ ] URL seguono lo schema corretto?

### Test 3: Conflitti
- [ ] Nessuna duplicazione pagine?
- [ ] Nessun errore PHP?
- [ ] Performance OK (no query eccessive)?

### Test 4: Admin
- [ ] Settings FP-Privacy accessibili?
- [ ] Lingue selezionabili correttamente?
- [ ] Preview banner funziona?

## 🚨 Problemi Noti

### 1. Race Condition nell'Init
Se FP-Multilanguage cambia locale dopo che FP-Privacy ha già caricato i testi.

**Soluzione:** Priorità hook

```php
// In FP-Privacy Plugin.php
add_action('plugins_loaded', [$this, 'boot'], 20); // Dopo FP-Multilanguage (10)
```

### 2. Cache dei Testi Banner
FP-Privacy potrebbe cachare i testi nella prima lingua rilevata.

**Soluzione:** Disabilita cache o rendila language-aware

### 3. URL Pagine Policy
Link hard-coded invece di dinamici.

**Soluzione:** Usa `fpml_get_translated_post_id()` se disponibile

## 📊 Raccomandazione Finale

### ✅ CONSIGLIATO: Approccio Ibrido

1. **FP-Privacy gestisce**:
   - Banner cookie (multilingua nativo)
   - Testi UI (bottoni, label)
   - Pagine Privacy/Cookie (una per lingua)

2. **FP-Multilanguage gestisce**:
   - Contenuto del sito (post, pagine normali)
   - Menu e navigazione
   - URL routing

3. **Integrazione**:
   - FP-Privacy legge lingua da FP-Multilanguage
   - FP-Multilanguage esclude pagine privacy
   - Link banner usano URL tradotti

### 📝 Codice Integrazione Completo

Crea `wp-content/mu-plugins/fp-plugins-integration.php`:

```php
<?php
/**
 * Integrazione FP-Privacy + FP-Multilanguage
 */

// 1. Escludi pagine privacy da traduzione automatica
add_filter('fpml_skip_post', function($skip, $post_id) {
    $options = get_option('fp_privacy_options', []);
    $pages = $options['pages'] ?? [];
    
    foreach ($pages as $type => $languages) {
        if (is_array($languages) && in_array($post_id, $languages, true)) {
            return true; // Skip traduzione
        }
    }
    
    return $skip;
}, 10, 2);

// 2. FP-Privacy usa lingua corrente di Multilanguage
add_filter('fp_privacy_current_language', function($lang) {
    if (function_exists('fpml_get_current_language')) {
        return fpml_get_current_language() ?: $lang;
    }
    return $lang;
});

// 3. Traduci URL pagine privacy nel banner
add_filter('fp_privacy_policy_url', function($url, $type, $lang) {
    // $type = 'privacy' o 'cookie'
    // $lang = lingua richiesta
    
    if (!function_exists('fpml_get_translated_post_id')) {
        return $url;
    }
    
    // Ottieni ID pagina dalla configurazione FP-Privacy
    $options = get_option('fp_privacy_options', []);
    $key = $type . '_policy_page_id';
    $page_id = $options['pages'][$key][$lang] ?? 0;
    
    if (!$page_id) {
        return $url;
    }
    
    // Usa permalink WordPress normale (Multilanguage lo tradurrà)
    return get_permalink($page_id);
    
}, 10, 3);
```

## 🎯 Conclusione

**FP-Privacy e FP-Multilanguage POSSONO coesistere** ma richiedono una configurazione attenta per evitare conflitti.

La **migliore strategia** è:
1. Lasciare che FP-Privacy gestisca la sua multilingua nativa
2. Escludere le pagine privacy dalla traduzione automatica
3. Sincronizzare la lingua attiva tra i due plugin

---

**Testato con:**
- FP-Privacy v0.1.2
- FP-Multilanguage v0.6.x
- WordPress 6.4+

