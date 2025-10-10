# Custom Services Detection - Examples

Il detector FP Privacy è ora in grado di rilevare sia servizi conosciuti che sconosciuti!

## 🎯 Funzionalità di Rilevamento

### 1. Servizi Predefiniti (151)
Il plugin rileva automaticamente 151 servizi comuni come Google Analytics, Facebook Pixel, Hotjar, ecc.

### 2. Rilevamento Automatico di Servizi Sconosciuti ✨
Il detector scansiona automaticamente:
- ✅ **Script esterni** caricati tramite WordPress (`wp_enqueue_script`)
- ✅ **Domini di terze parti** non presenti nel registry
- ⏳ **Cookie di terze parti** (richiede implementazione JavaScript lato client)

I servizi sconosciuti vengono marcati con `is_unknown: true` e includono:
- Nome del servizio (dedotto dal dominio)
- Dominio rilevato
- Categoria predefinita: "marketing"
- Legal basis: "Consent"

### 3. Servizi Personalizzati via Filtro WordPress 🔧

Puoi registrare servizi personalizzati utilizzando il filtro `fp_privacy_custom_services`.

## 📝 Esempi di Utilizzo

### Esempio 1: Aggiungere un Servizio Personalizzato

```php
add_filter( 'fp_privacy_custom_services', function( $services ) {
    $services['my_analytics'] = array(
        'name'          => 'My Custom Analytics',
        'category'      => 'statistics',
        'provider'      => 'My Company Inc.',
        'policy_url'    => 'https://mycompany.com/privacy',
        'cookies'       => array( 'my_analytics_*', 'my_session' ),
        'legal_basis'   => 'Consent',
        'purpose'       => 'Custom analytics tracking',
        'retention'     => '1 year',
        'data_location' => 'European Union',
        'detector'      => function() {
            return defined( 'MY_ANALYTICS_ENABLED' );
        }
    );
    
    return $services;
} );
```

### Esempio 2: Aggiungere un Servizio SaaS Proprietario

```php
add_filter( 'fp_privacy_custom_services', function( $services ) {
    $services['internal_crm'] = array(
        'name'          => 'Internal CRM System',
        'category'      => 'marketing',
        'provider'      => 'ACME Corp',
        'policy_url'    => 'https://acme.com/privacy',
        'cookies'       => array( 'crm_session', 'crm_user_id' ),
        'legal_basis'   => 'Legitimate interest',
        'purpose'       => 'Customer relationship management',
        'retention'     => '3 years',
        'data_location' => 'Italy',
        'detector'      => function() {
            return class_exists( 'ACME_CRM' );
        }
    );
    
    return $services;
} );
```

### Esempio 3: Servizio con Rilevamento di Script Esterni

```php
add_filter( 'fp_privacy_custom_services', function( $services ) {
    $services['custom_chat'] = array(
        'name'          => 'CustomChat Widget',
        'category'      => 'marketing',
        'provider'      => 'CustomChat Ltd.',
        'policy_url'    => 'https://customchat.io/privacy',
        'cookies'       => array( 'chat_*' ),
        'legal_basis'   => 'Consent',
        'purpose'       => 'Live customer support',
        'retention'     => '6 months',
        'data_location' => 'United States',
        'detector'      => function() {
            return wp_script_is( 'customchat-widget', 'enqueued' );
        }
    );
    
    return $services;
} );
```

### Esempio 4: Servizio con Embed Detection

```php
add_filter( 'fp_privacy_custom_services', function( $services ) {
    $services['custom_video'] = array(
        'name'          => 'Custom Video Platform',
        'category'      => 'marketing',
        'provider'      => 'VideoHost Inc.',
        'policy_url'    => 'https://videohost.com/privacy',
        'cookies'       => array( 'vh_player_*' ),
        'legal_basis'   => 'Consent',
        'purpose'       => 'Video hosting and playback',
        'retention'     => '1 year',
        'data_location' => 'Canada',
        'detector'      => array( $this, 'detect_custom_video' )
    );
    
    return $services;
} );

// Nel tuo plugin/tema
class My_Custom_Detector {
    public function detect_custom_video() {
        $query = new WP_Query( array(
            'post_type'      => 'any',
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            's'              => 'videohost.com',
        ) );
        
        return $query->have_posts();
    }
}
```

### Esempio 5: Aggiungere Multipli Servizi Personalizzati

```php
add_filter( 'fp_privacy_custom_services', function( $services ) {
    $custom = array(
        'service_one' => array(
            'name'          => 'Service One',
            'category'      => 'statistics',
            'provider'      => 'Company A',
            'policy_url'    => 'https://company-a.com/privacy',
            'cookies'       => array( 'srv1_*' ),
            'legal_basis'   => 'Consent',
            'purpose'       => 'Analytics',
            'retention'     => '1 year',
            'data_location' => 'EU',
            'detector'      => function() { return defined( 'SERVICE_ONE_ID' ); }
        ),
        'service_two' => array(
            'name'          => 'Service Two',
            'category'      => 'marketing',
            'provider'      => 'Company B',
            'policy_url'    => 'https://company-b.com/privacy',
            'cookies'       => array( 'srv2_*' ),
            'legal_basis'   => 'Consent',
            'purpose'       => 'Marketing automation',
            'retention'     => '2 years',
            'data_location' => 'US',
            'detector'      => function() { return wp_script_is( 'service-two', 'enqueued' ); }
        )
    );
    
    return array_merge( $services, $custom );
} );
```

## 🔍 Come Funziona il Rilevamento Automatico

1. **Scansione Script WordPress**
   - Analizza tutti gli script registrati con `wp_enqueue_script()`
   - Identifica URL esterni (non localhost)
   - Estrae il dominio

2. **Filtro Domini Conosciuti**
   - Confronta con lista di ~50 domini noti (Google, Facebook, etc)
   - Esclude domini già nel registry dei 151 servizi

3. **Generazione Automatica**
   - Crea un servizio "sconosciuto" per ogni dominio non riconosciuto
   - Deduce il nome dal dominio (es: `cdn.example.com` → "Example")
   - Marca con flag `is_unknown: true`

4. **Merge Finale**
   - Combina servizi predefiniti + sconosciuti + personalizzati
   - Cache per 15 minuti

## ⚙️ Parametri Servizio Personalizzato

```php
array(
    'name'          => string,  // Required - Nome visualizzato
    'category'      => string,  // Required - necessary|statistics|marketing
    'provider'      => string,  // Required - Nome azienda fornitrice
    'policy_url'    => string,  // Required - URL privacy policy
    'cookies'       => array,   // Required - Array nomi cookie
    'legal_basis'   => string,  // Required - Consent|Contract|Legitimate interest
    'purpose'       => string,  // Required - Scopo del servizio
    'retention'     => string,  // Required - Periodo conservazione
    'data_location' => string,  // Required - Località dati
    'detector'      => callable // Required - Funzione rilevamento
)
```

## 🎨 Best Practices

1. **Usa slug descrittivi** per i servizi personalizzati
2. **Testa il detector** per verificare che funzioni correttamente
3. **Documenta i cookie** utilizzati dal servizio
4. **Specifica la legal basis corretta** secondo GDPR
5. **Usa cache** per detector complessi (query al database)

## 🚀 Prossimi Sviluppi

- [ ] Rilevamento cookie JavaScript lato client
- [ ] Analisi richieste AJAX/Fetch
- [ ] Suggerimenti intelligenti basati su pattern comuni
- [ ] UI admin per gestire servizi personalizzati
- [ ] Export/import configurazioni personalizzate
