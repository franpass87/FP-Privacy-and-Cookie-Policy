<?php
/**
 * Script per configurare le impostazioni di default
 * 
 * Configura categorie, pagine e impostazioni base
 */

require_once __DIR__ . '/../../../wp-load.php';

if ( ! class_exists( '\\FP\\Privacy\\Plugin' ) ) {
    die( '❌ Plugin non caricato' );
}

$options = \FP\Privacy\Utils\Options::instance();
$all = $options->all();

// Configura categorie di default se non esistono
if ( empty( $all['categories'] ) ) {
    $all['categories'] = array(
        'necessary' => array(
            'label' => array(
                'it_IT' => 'Necessari',
                'en_US' => 'Necessary',
            ),
            'description' => array(
                'it_IT' => 'Cookie necessari per il funzionamento base del sito. Sempre attivi.',
                'en_US' => 'Cookies necessary for the basic functioning of the site. Always active.',
            ),
            'locked' => true,
        ),
        'preferences' => array(
            'label' => array(
                'it_IT' => 'Preferenze',
                'en_US' => 'Preferences',
            ),
            'description' => array(
                'it_IT' => 'Cookie che memorizzano le tue preferenze sul sito.',
                'en_US' => 'Cookies that store your preferences on the site.',
            ),
            'locked' => false,
        ),
        'statistics' => array(
            'label' => array(
                'it_IT' => 'Statistiche',
                'en_US' => 'Statistics',
            ),
            'description' => array(
                'it_IT' => 'Cookie che aiutano a capire come i visitatori interagiscono con il sito.',
                'en_US' => 'Cookies that help understand how visitors interact with the site.',
            ),
            'locked' => false,
        ),
        'marketing' => array(
            'label' => array(
                'it_IT' => 'Marketing',
                'en_US' => 'Marketing',
            ),
            'description' => array(
                'it_IT' => 'Cookie utilizzati per tracciare i visitatori e mostrare annunci personalizzati.',
                'en_US' => 'Cookies used to track visitors and display personalized ads.',
            ),
            'locked' => false,
        ),
    );
}

// Configura testi banner di default
if ( empty( $all['banner_texts'] ) || empty( $all['banner_texts']['it_IT'] ) ) {
    $all['banner_texts'] = array(
        'it_IT' => array(
            'title' => 'Rispettiamo la tua privacy',
            'message' => 'Utilizziamo i cookie per migliorare la tua esperienza. Puoi accettare tutti i cookie o gestire le tue preferenze.',
            'btn_accept' => 'Accetta tutti',
            'btn_reject' => 'Rifiuta tutti',
            'btn_prefs' => 'Gestisci preferenze',
            'modal_title' => 'Preferenze privacy',
            'modal_close' => 'Chiudi preferenze',
            'modal_save' => 'Salva preferenze',
            'revision_notice' => 'Abbiamo aggiornato la nostra policy. Rivedi le tue preferenze.',
            'toggle_locked' => 'Sempre attivo',
            'toggle_enabled' => 'Abilitato',
            'link_privacy_policy' => 'Informativa sulla Privacy',
            'link_cookie_policy' => 'Cookie Policy',
        ),
        'en_US' => array(
            'title' => 'We respect your privacy',
            'message' => 'We use cookies to improve your experience. You can accept all cookies or manage your preferences.',
            'btn_accept' => 'Accept all',
            'btn_reject' => 'Reject all',
            'btn_prefs' => 'Manage preferences',
            'modal_title' => 'Privacy preferences',
            'modal_close' => 'Close preferences',
            'modal_save' => 'Save preferences',
            'revision_notice' => 'We have updated our policy. Please review your preferences.',
            'toggle_locked' => 'Always active',
            'toggle_enabled' => 'Enabled',
            'link_privacy_policy' => 'Privacy Policy',
            'link_cookie_policy' => 'Cookie Policy',
        ),
    );
}

// Configura layout banner
if ( empty( $all['banner_layout'] ) ) {
    $all['banner_layout'] = array(
        'type' => 'floating',
        'position' => 'bottom',
        'sync_modal_and_button' => false,
        'enable_dark_mode' => false,
        'palette' => array(
            'surface_bg' => '#F9FAFB',
            'surface_text' => '#1F2937',
            'button_primary_bg' => '#2563EB',
            'button_primary_tx' => '#FFFFFF',
            'button_secondary_bg' => '#FFFFFF',
            'button_secondary_tx' => '#1F2937',
            'link' => '#1D4ED8',
            'border' => '#D1D5DB',
            'focus' => '#2563EB',
        ),
    );
}

// Attiva modalità preview per test
$all['preview_mode'] = true;

// Salva le impostazioni
$options->set( $all );

// Cancella il cookie per forzare la visualizzazione
$cookie_name = \FP\Privacy\Frontend\ConsentState::COOKIE_NAME;
if ( isset( $_COOKIE[ $cookie_name ] ) ) {
    setcookie( $cookie_name, '', time() - 3600, '/', '', false, true );
    unset( $_COOKIE[ $cookie_name ] );
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Impostazioni Configurate</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #2563EB; margin-top: 0; }
        .success { background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 15px 0; border-radius: 4px; }
        .info { background: #dbeafe; border-left: 4px solid #2563EB; padding: 15px; margin: 15px 0; border-radius: 4px; }
        ul { margin: 10px 0; padding-left: 20px; }
        li { margin: 8px 0; }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #2563EB; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; }
        .btn:hover { background: #1d4ed8; }
        .btn-secondary { background: #6b7280; }
        .btn-secondary:hover { background: #4b5563; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Impostazioni Configurate!</h1>
        
        <div class="success">
            <strong>Configurazione completata con successo:</strong>
            <ul>
                <li>✓ 4 Categorie di consenso create</li>
                <li>✓ Testi del banner in italiano e inglese</li>
                <li>✓ Layout banner configurato (floating, bottom)</li>
                <li>✓ Modalità preview ATTIVATA</li>
                <li>✓ Cookie di consenso cancellato</li>
            </ul>
        </div>
        
        <div class="info">
            <strong>📌 Prossimi passi:</strong>
            <ol>
                <li>Visita la homepage del sito</li>
                <li>Il banner dovrebbe essere visibile</li>
                <li>Se non appare, apri la console (F12) e cerca errori JavaScript</li>
                <li>Vai nelle impostazioni del plugin per disattivare la modalità preview</li>
            </ol>
        </div>
        
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn">🏠 Vai alla Homepage</a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=fp-privacy-settings' ) ); ?>" class="btn btn-secondary">⚙️ Vai alle Impostazioni</a>
    </div>
</body>
</html>

