# FP Privacy Plugin - Migration Guide

## Migrazione da Vecchio a Nuovo Sistema

Questo documento spiega come il nuovo sistema basato su service container sostituisce il vecchio sistema e come migrare completamente in futuro.

## Architettura Vecchia vs Nuova

### Vecchio Sistema (Plugin.php + PluginBootstrapper)

```php
// Vecchio sistema
class Plugin {
    public function boot() {
        $this->options = Options::instance();
        $this->log_model = new LogModel();
        $this->cleanup = new Cleanup($this->log_model, $this->options);
        $this->consent_state = new ConsentState($this->options, $this->log_model);
        
        $bootstrapper = new PluginBootstrapper(...);
        $bootstrapper->bootstrap();
        
        $filters = new PluginFilters($this->options);
        $filters->register();
    }
}
```

### Nuovo Sistema (Kernel + Service Providers)

```php
// Nuovo sistema
$kernel = Kernel::make();
$kernel->boot(); // Registra e boot tutti i service providers
```

## Componenti Migrati

### 1. PluginBootstrapper → Service Providers

**Vecchio**: `PluginBootstrapper` istanziava manualmente tutte le classi.

**Nuovo**: Ogni service provider registra le proprie classi nel container:

- **FrontendServiceProvider**: Banner, Shortcodes, Blocks, ScriptBlocker, ConsentState
- **AdminServiceProvider**: Settings, PolicyEditor, Analytics, etc.
- **RESTServiceProvider**: REST Controller
- **CLIServiceProvider**: CLI Commands
- **IntegrationServiceProvider**: DetectorRegistry, ConsentMode
- **DataServiceProvider**: LogModel, LogModelTable

### 2. PluginFilters → CoreServiceProvider

**Vecchio**: `PluginFilters` registrava 3 filtri WordPress.

**Nuovo**: I filtri sono registrati in `CoreServiceProvider::registerPluginFilters()`:

- `fp_privacy_enable_privacy_tools`
- `fp_privacy_enable_gpc`
- `fp_privacy_consent_ids_for_email`

### 3. Options Singleton → OptionsInterface

**Vecchio**: `Options::instance()` usato ovunque.

**Nuovo**: `OptionsInterface` dal container, con `OptionsAdapter` per compatibilità.

### 4. Database Access → DatabaseInterface

**Vecchio**: `global $wpdb` usato direttamente.

**Nuovo**: `DatabaseInterface` con `WpdbAdapter` per compatibilità.

## Migrazione Completa (Futuro)

### Step 1: Rimuovere PluginBootstrapper

**Quando**: Dopo aver verificato che il nuovo sistema funziona correttamente.

**Cosa fare**:
1. Verificare che tutti i servizi siano registrati nei service providers
2. Rimuovere `PluginBootstrapper.php`
3. Rimuovere riferimenti in `Plugin.php`

### Step 2: Rimuovere PluginFilters

**Quando**: Dopo aver verificato che i filtri sono registrati correttamente.

**Cosa fare**:
1. Verificare che `CoreServiceProvider::registerPluginFilters()` funzioni
2. Rimuovere `PluginFilters.php`
3. Rimuovere riferimenti in `Plugin.php`

### Step 3: Rimuovere Singleton Pattern da Options

**Quando**: Dopo aver migrato tutte le classi a usare OptionsInterface.

**Cosa fare**:
1. Migrare tutte le classi a usare `OptionsInterface` invece di `Options`
2. Rimuovere metodo `instance()` da `Options`
3. Rimuovere `OptionsAdapter` (non più necessario)

### Step 4: Rimuovere Custom Autoloader

**Quando**: Dopo aver verificato che Composer autoloader funziona correttamente.

**Cosa fare**:
1. Verificare che `vendor/autoload.php` carichi tutte le classi
2. Rimuovere autoloader custom da `fp-privacy-cookie-policy.php`

### Step 5: Rimuovere Funzione Globale

**Quando**: Dopo aver migrato tutto a `IpSaltService`.

**Cosa fare**:
1. Verificare che `IpSaltService` funzioni correttamente
2. Rimuovere `fp_privacy_get_ip_salt()` da `fp-privacy-cookie-policy.php`

### Step 6: Rimuovere Vecchio Sistema

**Quando**: Dopo aver verificato che il nuovo sistema è stabile.

**Cosa fare**:
1. Rimuovere feature flag `FP_PRIVACY_USE_NEW_KERNEL`
2. Rimuovere codice del vecchio sistema da `fp-privacy-cookie-policy.php`
3. Rimuovere classe `Plugin` (o mantenerla solo per compatibilità)

## Compatibilità Attuale

Il sistema attuale mantiene entrambi i sistemi per garantire:

- ✅ **Nessuna breaking change**
- ✅ **Rollback possibile** via feature flag
- ✅ **Migrazione graduale**
- ✅ **Testing sicuro**

## Testing Durante Migrazione

1. **Test Funzionalità**: Verificare che tutte le funzionalità funzionino
2. **Test Performance**: Verificare che non ci siano regressioni
3. **Test Multisite**: Verificare attivazione network-wide
4. **Test Compatibilità**: Verificare con altri plugin/temi

## Timeline Consigliata

1. **Fase Attuale**: Nuovo sistema abilitato, vecchio sistema come fallback
2. **Fase 1 (1-2 mesi)**: Monitorare nuovo sistema in produzione
3. **Fase 2 (3-6 mesi)**: Rimuovere codice deprecato gradualmente
4. **Fase 3 (6+ mesi)**: Rimuovere completamente vecchio sistema

## Note Importanti

- ⚠️ **Non rimuovere codice deprecato** finché non si è certi che il nuovo sistema funziona
- ⚠️ **Testare sempre** prima di rimuovere codice
- ⚠️ **Mantenere backup** del codice prima di rimuoverlo
- ✅ **Migrazione graduale** è più sicura di una migrazione completa

## Supporto

Per domande o problemi durante la migrazione, consultare:
- `docs/REFACTORING-ARCHITECTURE.md` - Architettura completa
- `docs/VERIFICATION-CHECKLIST.md` - Checklist di verifica
- `README-REFACTORING.md` - README refactoring






