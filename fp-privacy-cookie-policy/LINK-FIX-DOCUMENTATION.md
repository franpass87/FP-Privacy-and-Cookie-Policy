# Correzione Link Banner Cookie

## Problema Identificato

I link nel banner dei cookie ("Informativa sulla Privacy" e "Cookie Policy") puntavano entrambi alla stessa pagina, rendendo impossibile accedere a pagine diverse per le due policy.

## Causa del Problema

Il problema era nel metodo `get_page_id()` della classe `PageManager` che aveva una logica di fallback che poteva restituire lo stesso ID di pagina per entrambi i tipi di policy quando:

1. Entrambe le pagine erano configurate con lo stesso ID
2. Una delle due pagine non era configurata e il fallback restituiva l'ID dell'altra pagina

## Modifiche Apportate

### 1. PageManager.php
- **File**: `src/Utils/PageManager.php`
- **Modifica**: Migliorata la logica del metodo `get_page_id()` per evitare duplicati
- **Dettagli**: 
  - Aggiunto controllo per evitare che una pagina di policy punti allo stesso ID di un'altra
  - Se viene rilevato un duplicato, il metodo restituisce 0 invece di restituire l'ID duplicato

### 2. ConsentState.php
- **File**: `src/Frontend/ConsentState.php`
- **Modifica**: Migliorata la gestione degli URL delle policy
- **Dettagli**:
  - Aggiunto controllo per assicurarsi che gli ID delle pagine siano validi e diversi
  - Aggiunto debug logging per identificare problemi futuri
  - Migliorata la validazione dei permalink

### 3. banner.js
- **File**: `assets/js/banner.js`
- **Modifica**: Aggiunto debug logging per monitorare gli URL delle policy
- **Dettagli**:
  - Aggiunto console.log per visualizzare gli URL delle policy nel browser
  - Debug sia nel banner principale che nel modal delle preferenze

## Come Testare la Correzione

1. **Abilita WP_DEBUG**: Assicurati che `WP_DEBUG` sia abilitato nel file `wp-config.php`
2. **Controlla i log**: Verifica i log di WordPress per i messaggi di debug che iniziano con "FP Privacy Debug"
3. **Controlla la console del browser**: Apri gli strumenti per sviluppatori e controlla la console per i messaggi di debug
4. **Testa i link**: Clicca sui link "Informativa sulla Privacy" e "Cookie Policy" nel banner per verificare che puntino a pagine diverse

## Configurazione Richiesta

Per far funzionare correttamente i link, assicurati di:

1. **Creare pagine separate**: Crea due pagine distinte in WordPress:
   - Una per la Privacy Policy
   - Una per la Cookie Policy

2. **Configurare le pagine nel plugin**: Vai nelle impostazioni del plugin e configura:
   - `privacy_policy_page_id` con l'ID della pagina Privacy Policy
   - `cookie_policy_page_id` con l'ID della pagina Cookie Policy

3. **Verificare che gli ID siano diversi**: Gli ID delle due pagine devono essere diversi

## Debug

Se i link continuano a puntare alla stessa pagina:

1. Controlla i log di WordPress per i messaggi di debug
2. Controlla la console del browser per i messaggi di debug JavaScript
3. Verifica che le pagine siano configurate correttamente nelle impostazioni del plugin
4. Assicurati che gli ID delle pagine siano diversi

## Rimozione del Debug

Una volta verificato che tutto funziona correttamente, puoi rimuovere i messaggi di debug:

1. Rimuovi le righe di `error_log()` dal file `ConsentState.php`
2. Rimuovi le righe di `console.log()` dal file `banner.js`

## Note Tecniche

- La correzione mantiene la compatibilità con le versioni precedenti
- Il debug è condizionale e si attiva solo quando `WP_DEBUG` è abilitato
- La logica di fallback è stata migliorata ma non rimossa completamente
- Le modifiche sono backward-compatible

