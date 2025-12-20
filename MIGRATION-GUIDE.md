# FP Privacy Plugin - Migration Guide

## Overview

This guide documents the refactoring progress and provides instructions for completing the migration to the new clean architecture.

**Current Status**: Foundation complete, services migrated, bootstrap unified. Module migration in progress.

---

## Completed Phases

### Phase 1: Foundation ✅

**All infrastructure created**:
- New directory structure (Domain, Application, Presentation, Shared, Infrastructure)
- All core interfaces (OptionsRepositoryInterface, ConsentRepositoryInterface, etc.)
- Infrastructure adapters (WpOptionsAdapter, ConsentRepository, etc.)
- Service providers (InfrastructureServiceProvider, DomainServiceProvider, ApplicationServiceProvider)

### Phase 2: Service Migration ✅

**All services migrated to container**:
- Logger, Cache, Database, Options (via InfrastructureServiceProvider)
- OptionsRepositoryInterface created and registered
- Backward compatibility maintained via OptionsAdapter

### Phase 5: Bootstrap Unification ✅

**Bootstrap system unified**:
- Bootstrap class created and integrated
- Main plugin file simplified
- Old bootstrap removed
- Fallback chain maintained for compatibility

---

## In Progress: Phase 3 - Module Migration

### Current Status

**Structure Created**:
- ✅ Application layer structure and handlers
- ✅ Presentation layer directory structure
- ✅ Domain layer interfaces
- ✅ Infrastructure adapters

**Files Moved**:
- ✅ Admin renderers copied to `Presentation/Admin/Views/` (namespaces updated)
- ✅ CLI commands copied to `Presentation/CLI/Commands/` (namespaces updated)
- ✅ MultisiteManager moved to `Infrastructure/Multisite/`

**Files Still Need Moving**:
- Admin handlers → `Presentation/Admin/Controllers/`
- Frontend renderers → `Presentation/Frontend/Views/`
- REST handlers → `Presentation/REST/Controllers/`
- Consent models → `Domain/Consent/`
- Policy logic → `Domain/Policy/`

---

## Migration Instructions

### Step 1: Update References to Moved Files

**Admin Renderers**:
- Old: `FP\Privacy\Admin\Renderer\*`
- New: `FP\Privacy\Presentation\Admin\Views\*`

**CLI Commands**:
- Old: `FP\Privacy\CLI\*`
- New: `FP\Privacy\Presentation\CLI\Commands\*`

**MultisiteManager**:
- Old: `FP\Privacy\MultisiteManager`
- New: `FP\Privacy\Infrastructure\Multisite\MultisiteManager`

Update all `use` statements and class references.

### Step 2: Update Service Providers

**CLIServiceProvider**:
```php
// Update to use new namespace
use FP\Privacy\Presentation\CLI\Commands\Commands;
```

**AdminServiceProvider**:
```php
// Update to use new renderer namespace
use FP\Privacy\Presentation\Admin\Views\BannerTabRenderer;
// etc.
```

### Step 3: Move Remaining Files

Follow the pattern established:
1. Copy file to new location
2. Update namespace
3. Update `use` statements
4. Update references in other files
5. Test functionality
6. Remove old file (after all references updated)

---

## Remaining Work

### Phase 3 Continuation

1. **Admin Module**:
   - Move handlers to `Presentation/Admin/Controllers/`
   - Update Settings to use Application handlers
   - Update PolicyEditor to use Application handlers
   - Update AdminServiceProvider

2. **Frontend Module**:
   - Move renderers to `Presentation/Frontend/Views/`
   - Move ConsentState to Domain layer
   - Update FrontendServiceProvider

3. **REST Module**:
   - Move handlers to `Presentation/REST/Controllers/`
   - Update to use Application handlers
   - Update RESTServiceProvider

### Phase 4: Domain Migration

1. Move Consent domain files
2. Create ConsentService
3. Move Policy generation to Domain
4. Split DetectorRegistry

### Phase 6: Cleanup

1. Remove Options singleton
2. Remove all `global $wpdb`
3. Remove adapters
4. Remove deprecated functions
5. Final testing

---

## Testing

After each migration step:
1. Test plugin activation
2. Test admin pages
3. Test frontend banner
4. Test REST API
5. Test WP-CLI commands
6. Check for PHP errors

---

## Rollback

If issues occur:
1. Git revert to last working commit
2. Feature flags can disable new code paths
3. Old code remains as fallback

---

## Notes

- All migrations maintain backward compatibility
- Old and new code coexist during migration
- Gradual migration reduces risk
- Comprehensive testing at each step










