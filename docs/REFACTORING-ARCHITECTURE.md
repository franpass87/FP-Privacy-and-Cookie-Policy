# FP Privacy Plugin - Refactoring Architecture Documentation

## Overview

This plugin has been refactored to use a clean architecture with service container, service providers, and dependency injection. The refactoring maintains full backward compatibility while providing a modern, maintainable codebase.

## Architecture

### Service Container

The service container (`src/Core/Container.php`) provides dependency injection and service management:

- **Bind**: Register a service (new instance each time)
- **Singleton**: Register a service as singleton (shared instance)
- **Make/Get**: Resolve and return service instances
- **Alias**: Create aliases for services

### Plugin Kernel

The plugin kernel (`src/Core/Kernel.php`) handles:

- Bootstrap and lifecycle management
- Service provider registration
- Activation/deactivation hooks
- Multisite provisioning

### Service Providers

Service providers register and boot services:

- **CoreServiceProvider**: Logger, Cache, Options, Database, Validation, Sanitization
- **DataServiceProvider**: LogModel, LogModelTable, database tables
- **IntegrationServiceProvider**: DetectorRegistry, ServiceRegistry, ConsentMode
- **MultisiteServiceProvider**: MultisiteManager
- **MultilanguageServiceProvider**: MultilanguageCompatibility
- **FrontendServiceProvider**: Banner, Shortcodes, Blocks, ScriptBlocker
- **AdminServiceProvider**: Settings, PolicyEditor, Analytics, etc.
- **RESTServiceProvider**: REST API controllers
- **CLIServiceProvider**: WP-CLI commands

## Usage

### Getting Services from Container

```php
// Get kernel instance
$kernel = \FP\Privacy\Core\Kernel::make();

// Get container
$container = $kernel->getContainer();

// Get a service
$logger = $container->get( \FP\Privacy\Services\Logger\LoggerInterface::class );
$cache = $container->get( \FP\Privacy\Services\Cache\CacheInterface::class );
```

### Feature Flag

The new system is enabled by default. To use the old system:

```php
define( 'FP_PRIVACY_USE_NEW_KERNEL', false );
```

## Migration Status

### Completed
- ✅ Service container and kernel
- ✅ All service providers created
- ✅ Cross-cutting services (Logger, Cache, Options, Database, Validation, Sanitization)
- ✅ Options adapter for backward compatibility
- ✅ Database abstraction (partial - LogModelTable and LogModel updated)
- ✅ IP salt service (replaces global function)

### In Progress / Future
- ⏳ Complete migration of LogModel methods to use DatabaseInterface
- ⏳ Remove singleton pattern from Options class (after full migration)
- ⏳ Remove PluginBootstrapper (kept for backward compatibility)
- ⏳ Remove PluginFilters (functionality moved to providers)
- ⏳ Remove custom autoloader (Composer handles this)

## Backward Compatibility

All existing code continues to work:

- `Options::instance()` still works (via OptionsAdapter)
- `Plugin::instance()` still works (fallback in Kernel)
- Global function `fp_privacy_get_ip_salt()` still works (delegates to service)
- Custom autoloader still works (fallback if Kernel not loaded)

## Testing

To test the new system:

1. Ensure `FP_PRIVACY_USE_NEW_KERNEL` is `true` (default)
2. Activate the plugin
3. Verify all functionality works
4. Check WordPress debug log for any errors

To rollback:

1. Set `define( 'FP_PRIVACY_USE_NEW_KERNEL', false );` in `wp-config.php`
2. The old system will be used

## Service Interfaces

### LoggerInterface
PSR-3 compatible logger with methods: `emergency()`, `alert()`, `critical()`, `error()`, `warning()`, `notice()`, `info()`, `debug()`

### CacheInterface
Cache service with methods: `get()`, `set()`, `delete()`, `flush()`, `remember()`

### OptionsInterface
Options service with methods: `get()`, `set()`, `delete()`, `all()`

### DatabaseInterface
Database abstraction with methods: `query()`, `get_var()`, `get_row()`, `get_col()`, `get_results()`, `insert()`, `update()`, `delete()`, `get_table_name()`

### ValidatorInterface
Validation service with methods: `validate()`, `validateField()`

### SanitizerInterface
Sanitization service with methods: `sanitize()`, `sanitizeArray()`

## Directory Structure

```
src/
├── Core/                    # Core infrastructure
│   ├── Container.php       # Service container
│   ├── ContainerInterface.php
│   ├── Kernel.php          # Plugin kernel
│   └── ServiceProviderInterface.php
├── Providers/              # Service providers
│   ├── CoreServiceProvider.php
│   ├── DataServiceProvider.php
│   ├── IntegrationServiceProvider.php
│   ├── MultisiteServiceProvider.php
│   ├── MultilanguageServiceProvider.php
│   ├── FrontendServiceProvider.php
│   ├── AdminServiceProvider.php
│   ├── RESTServiceProvider.php
│   └── CLIServiceProvider.php
├── Services/               # Cross-cutting services
│   ├── Logger/
│   ├── Cache/
│   ├── Options/
│   ├── Database/
│   ├── Validation/
│   ├── Sanitization/
│   └── Security/
└── Infrastructure/         # WordPress-specific implementations
    ├── Database/
    └── Options/
```

## Next Steps

1. Complete migration of all LogModel methods to use DatabaseInterface
2. Gradually migrate classes from `Options::instance()` to container
3. Remove deprecated code in a future major version
4. Add comprehensive unit tests for services
5. Document service provider creation for other FP plugins






