# PluginFilters Migration

## Migrazione Completata ✅

La funzionalità di `PluginFilters` è stata migrata nel nuovo sistema.

## Vecchio Sistema

**File**: `src/PluginFilters.php`

```php
class PluginFilters {
    public function register() {
        // 3 filtri WordPress
        add_filter('fp_privacy_enable_privacy_tools', '__return_true', 10, 2);
        add_filter('fp_privacy_enable_gpc', ...);
        add_filter('fp_privacy_consent_ids_for_email', ...);
    }
}
```

**Uso**: Istanziazione manuale in `Plugin::boot()`

## Nuovo Sistema

**File**: `src/Providers/CoreServiceProvider.php`

```php
class CoreServiceProvider {
    public function boot(ContainerInterface $container): void {
        // ...
        $this->registerPluginFilters($container);
    }
    
    private function registerPluginFilters(ContainerInterface $container): void {
        // Stessi 3 filtri, ora registrati nel service provider
    }
}
```

**Uso**: Registrazione automatica durante il boot del kernel

## Filtri Migrati

### 1. `fp_privacy_enable_privacy_tools`
- **Scopo**: Abilita integrazione con WordPress privacy tools
- **Default**: `true`
- **Status**: ✅ Migrato

### 2. `fp_privacy_enable_gpc`
- **Scopo**: Abilita/disabilita Global Privacy Control (GPC)
- **Source**: Opzione salvata `gpc_enabled`
- **Status**: ✅ Migrato

### 3. `fp_privacy_consent_ids_for_email`
- **Scopo**: Mappa email a consent_ids usando user meta
- **Source**: User meta `fp_consent_ids`
- **Status**: ✅ Migrato

## Compatibilità

- ✅ **Vecchio sistema**: `PluginFilters` ancora funzionante (fallback)
- ✅ **Nuovo sistema**: Filtri registrati in `CoreServiceProvider`
- ✅ **Nessuna breaking change**: Entrambi i sistemi funzionano

## Rimozione Futura

Quando il nuovo sistema sarà completamente stabile:

1. Verificare che i filtri siano registrati correttamente
2. Rimuovere `src/PluginFilters.php`
3. Rimuovere istanziazione in `Plugin::boot()`

## Testing

Verificare che i filtri funzionino correttamente:

```php
// Test 1: Privacy tools enabled
$enabled = apply_filters('fp_privacy_enable_privacy_tools', false);
// Expected: true

// Test 2: GPC enabled (se opzione salvata)
$gpc = apply_filters('fp_privacy_enable_gpc', false);
// Expected: valore da opzione

// Test 3: Consent IDs per email
$ids = apply_filters('fp_privacy_consent_ids_for_email', [], 'user@example.com');
// Expected: array di consent IDs se user esiste
```






