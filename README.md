# FP Privacy and Cookie Policy

Plugin WordPress per la gestione GDPR-compliant dei cookie con integrazione nativa per FP Performance Suite.

## Caratteristiche

- ✅ **Banner Cookie personalizzabile** - Conforme GDPR
- ✅ **Cookie Scanner automatico** - Rileva cookie di terze parti
- ✅ **Privacy Policy Generator** - Genera privacy policy conformi
- ✅ **Gestione Consensi** - Tracciamento completo dei consensi utente
- ✅ **Integrazione FP Performance** - Ottimizzazione performance nativa
- ✅ **Categorie Cookie** - Necessari, Analitici, Marketing
- ✅ **Lazy Loading Scripts** - Carica script solo dopo consenso
- ✅ **Cache-Aware** - Esclusione intelligente dalla cache

## Integrazione con FP Performance Suite

Il plugin si integra automaticamente con FP Performance Suite per:

- **Esclusione dalla Cache**: Il banner non viene cachato finché l'utente non dà il consenso
- **Esclusione dalla Minificazione**: CSS/JS del banner esclusi dall'ottimizzazione
- **Lazy Load Scripts**: Script di terze parti caricati solo dopo consenso
- **Resource Hints**: DNS prefetch intelligente basato sui consensi
- **Critical CSS**: Inline automatico dello stile essenziale del banner

## Installazione

1. Copia la cartella `FP-Privacy-and-Cookie-Policy-1` in `wp-content/plugins/` (o crea junction)
2. Esegui `composer install --no-dev` dalla cartella del plugin
3. Attiva il plugin da WordPress admin
4. Vai su **Privacy & Cookie > Dashboard** per configurare

## Configurazione

### 1. Impostazioni Banner

- **Posizione**: Top o Bottom
- **Stile**: Classic, Minimal, Modern
- **Colori**: Personalizza il colore primario
- **Testi**: Personalizza i testi dei pulsanti

### 2. Cookie Scanner

- Scansiona automaticamente il sito
- Rileva cookie di terze parti
- Categorizza automaticamente i cookie

### 3. Privacy Policy

- Genera automaticamente una privacy policy
- Conforme al GDPR
- Personalizzabile

## Sviluppo

### Requisiti

- PHP >= 7.4
- WordPress >= 5.8
- Composer

### Struttura

```
FP-Privacy-and-Cookie-Policy-1/
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── banner.css
│   └── js/
│       ├── admin.js
│       └── banner.js
├── src/
│   ├── Admin/
│   │   ├── Menu.php
│   │   └── Assets.php
│   ├── Services/
│   │   ├── CookieBanner.php
│   │   ├── ConsentManager.php
│   │   ├── PrivacyPolicyManager.php
│   │   ├── CookieScanner.php
│   │   └── PerformanceIntegration.php
│   ├── Utils/
│   │   └── Logger.php
│   ├── Plugin.php
│   └── ServiceContainer.php
├── composer.json
├── fp-privacy-cookie-policy.php
└── README.md
```

### Coding Standards

```bash
composer cs      # Check coding standards
composer cbf     # Auto-fix coding standards
```

## API e Hooks

### Filtri

```php
// Escludi pattern dalla cache
add_filter('fp_ps_page_cache_exclude_patterns', function($patterns) {
    $patterns[] = 'custom-pattern';
    return $patterns;
});

// Registra script di terze parti
add_action('init', function() {
    if (class_exists('FP\\Privacy\\Services\\PerformanceIntegration')) {
        $integration = FP\Privacy\Plugin::container()->get(
            FP\Privacy\Services\PerformanceIntegration::class
        );
        
        $integration->registerThirdPartyScript('custom-analytics', [
            'domain' => 'analytics.example.com',
            'category' => 'analytics',
        ]);
    }
});
```

### Azioni

```php
// Dopo il salvataggio del consenso
add_action('fp_privacy_consent_saved', function($consent) {
    // Il tuo codice qui
});

// Dopo l'attivazione del plugin
add_action('fp_privacy_activated', function($version) {
    // Il tuo codice qui
});
```

### JavaScript Events

```javascript
// Listener per consenso dato
document.addEventListener('fpPrivacyConsentGiven', function(e) {
    const consent = e.detail;
    
    if (consent.analytics) {
        // Carica analytics
    }
    
    if (consent.marketing) {
        // Carica marketing scripts
    }
});
```

## License

Proprietario - Francesco Passeri

## Autore

**Francesco Passeri**  
[https://francescopasseri.com](https://francescopasseri.com)

## Changelog

### 1.0.0 (2025-10-28)
- Release iniziale
- Banner cookie GDPR-compliant
- Cookie scanner automatico
- Privacy policy generator
- Integrazione FP Performance Suite
- Gestione consensi con log database
- Lazy loading script di terze parti
