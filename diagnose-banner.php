<?php
/**
 * Script diagnostico per verificare perché il banner non compare
 * 
 * Esegui questo script visitando: /wp-content/plugins/FP-Privacy-and-Cookie-Policy-1/diagnose-banner.php
 */

// Carica WordPress
require_once __DIR__ . '/../../../wp-load.php';

// Verifica che il plugin sia attivo
if ( ! class_exists( '\\FP\\Privacy\\Plugin' ) ) {
    die( '❌ Plugin FP-Privacy non caricato!' );
}

header( 'Content-Type: text/html; charset=utf-8' );
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnostica Banner FP-Privacy</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #2563EB; padding-bottom: 10px; }
        h2 { color: #2563EB; margin-top: 30px; }
        .check { margin: 15px 0; padding: 15px; background: #f9fafb; border-left: 4px solid #10b981; }
        .error { margin: 15px 0; padding: 15px; background: #fef2f2; border-left: 4px solid #ef4444; }
        .warning { margin: 15px 0; padding: 15px; background: #fffbeb; border-left: 4px solid #f59e0b; }
        .success { color: #10b981; font-weight: bold; }
        .fail { color: #ef4444; font-weight: bold; }
        pre { background: #1f2937; color: #f9fafb; padding: 15px; border-radius: 4px; overflow-x: auto; }
        code { background: #e5e7eb; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Diagnostica Banner FP-Privacy</h1>
    
    <?php
    // 1. Verifica che il plugin sia attivo
    echo '<h2>1️⃣ Stato Plugin</h2>';
    echo '<div class="check"><span class="success">✓</span> Plugin caricato correttamente</div>';
    
    // 2. Verifica opzioni del plugin
    echo '<h2>2️⃣ Opzioni del Plugin</h2>';
    $options = \FP\Privacy\Utils\Options::instance();
    $all_options = $options->all();
    
    echo '<div class="check">';
    echo '<strong>Lingue attive:</strong> ';
    $languages = $options->get_languages();
    echo '<code>' . implode( ', ', $languages ) . '</code><br>';
    
    echo '<strong>Revisione consenso:</strong> ';
    $revision = (int) $options->get( 'consent_revision', 1 );
    echo '<code>' . $revision . '</code><br>';
    
    echo '<strong>Modalità preview:</strong> ';
    $preview = (bool) $options->get( 'preview_mode', false );
    echo $preview ? '<code>ATTIVA</code>' : '<code>DISATTIVA</code><br>';
    
    echo '<strong>Banner abilitato:</strong> ';
    $banner_enabled = (bool) $options->get( 'banner_enabled', true );
    echo $banner_enabled ? '<span class="success">✓ SÌ</span>' : '<span class="fail">✗ NO</span>';
    echo '</div>';
    
    // 3. Verifica stato frontend
    echo '<h2>3️⃣ Stato Frontend</h2>';
    $consent_state = new \FP\Privacy\Frontend\ConsentState(
        $options,
        new \FP\Privacy\Consent\LogModel()
    );
    
    $lang = determine_locale();
    $frontend_state = $consent_state->get_frontend_state( $lang );
    
    echo '<div class="check">';
    echo '<strong>Lingua rilevata:</strong> <code>' . esc_html( $lang ) . '</code><br>';
    echo '<strong>Should display banner:</strong> ';
    
    if ( ! empty( $frontend_state['state']['should_display'] ) ) {
        echo '<span class="success">✓ SÌ</span> (Il banner DOVREBBE essere visibile)';
    } else {
        echo '<span class="fail">✗ NO</span> (Il banner NON verrà mostrato)';
    }
    echo '<br>';
    
    echo '<strong>Consent ID presente:</strong> ';
    $consent_id = $frontend_state['state']['consent_id'] ?? '';
    if ( $consent_id ) {
        echo '<code>' . esc_html( substr( $consent_id, 0, 16 ) ) . '...</code>';
    } else {
        echo '<span class="warning">Nessuno</span>';
    }
    echo '<br>';
    
    echo '<strong>Modalità preview:</strong> ';
    echo ! empty( $frontend_state['state']['preview_mode'] ) ? '<code>ATTIVA</code>' : '<code>DISATTIVA</code>';
    echo '</div>';
    
    // 4. Verifica cookie
    echo '<h2>4️⃣ Cookie Browser</h2>';
    echo '<div class="check">';
    $cookie_name = \FP\Privacy\Frontend\ConsentState::COOKIE_NAME;
    if ( isset( $_COOKIE[ $cookie_name ] ) ) {
        echo '<strong>Cookie trovato:</strong> <code>' . esc_html( $_COOKIE[ $cookie_name ] ) . '</code><br>';
        $parts = explode( '|', $_COOKIE[ $cookie_name ] );
        echo '<strong>Consent ID:</strong> <code>' . esc_html( $parts[0] ?? 'N/A' ) . '</code><br>';
        echo '<strong>Revisione cookie:</strong> <code>' . esc_html( $parts[1] ?? '0' ) . '</code>';
    } else {
        echo '<span class="warning">⚠️ Nessun cookie di consenso trovato</span>';
    }
    echo '</div>';
    
    // 5. Verifica pagine policy
    echo '<h2>5️⃣ Pagine Policy</h2>';
    echo '<div class="check">';
    $pages = $all_options['pages'] ?? array();
    
    foreach ( array( 'privacy_policy_page_id', 'cookie_policy_page_id' ) as $key ) {
        $type = str_replace( '_page_id', '', $key );
        echo '<strong>' . ucfirst( str_replace( '_', ' ', $type ) ) . ':</strong><br>';
        
        if ( ! empty( $pages[ $key ] ) && is_array( $pages[ $key ] ) ) {
            foreach ( $pages[ $key ] as $lang_code => $page_id ) {
                $page = get_post( $page_id );
                if ( $page ) {
                    $url = get_permalink( $page_id );
                    echo '&nbsp;&nbsp;▸ ' . $lang_code . ': <a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $page->post_title ) . '</a> (ID: ' . $page_id . ')<br>';
                } else {
                    echo '&nbsp;&nbsp;▸ ' . $lang_code . ': <span class="fail">✗ Pagina non trovata (ID: ' . $page_id . ')</span><br>';
                }
            }
        } else {
            echo '&nbsp;&nbsp;<span class="warning">⚠️ Nessuna pagina configurata</span><br>';
        }
    }
    echo '</div>';
    
    // 6. Verifica categorie
    echo '<h2>6️⃣ Categorie Consenso</h2>';
    $categories = $options->get( 'categories', array() );
    if ( ! empty( $categories ) ) {
        echo '<div class="check">';
        foreach ( $categories as $cat_id => $cat_data ) {
            $label = $cat_data['label'][ $lang ] ?? $cat_data['label']['en_US'] ?? $cat_id;
            $locked = ! empty( $cat_data['locked'] ) ? ' 🔒 (Locked)' : '';
            echo '<strong>' . esc_html( $label ) . '</strong>' . $locked . '<br>';
        }
        echo '</div>';
    } else {
        echo '<div class="error"><span class="fail">✗</span> Nessuna categoria configurata!</div>';
    }
    
    // 7. Test rendering banner
    echo '<h2>7️⃣ Test Rendering Banner</h2>';
    echo '<div class="check">';
    echo '<p>Verifica se il contenitore del banner viene renderizzato:</p>';
    echo '<div id="fp-privacy-banner-root" style="border: 2px dashed #2563EB; padding: 20px; background: #f0f9ff; min-height: 100px;"></div>';
    echo '<p style="margin-top: 10px;"><em>Il contenitore sopra dovrebbe contenere il banner se è configurato correttamente.</em></p>';
    echo '</div>';
    
    // 8. Verifica asset
    echo '<h2>8️⃣ Asset JavaScript/CSS</h2>';
    echo '<div class="check">';
    
    $banner_js = FP_PRIVACY_PLUGIN_URL . 'assets/js/banner.js';
    $banner_css = FP_PRIVACY_PLUGIN_URL . 'assets/css/banner.css';
    $consent_js = FP_PRIVACY_PLUGIN_URL . 'assets/js/consent-mode.js';
    
    echo '<strong>File Asset:</strong><br>';
    echo '▸ <a href="' . esc_url( $banner_js ) . '" target="_blank">banner.js</a><br>';
    echo '▸ <a href="' . esc_url( $banner_css ) . '" target="_blank">banner.css</a><br>';
    echo '▸ <a href="' . esc_url( $consent_js ) . '" target="_blank">consent-mode.js</a><br>';
    echo '</div>';
    
    // 9. Stato completo (JSON)
    echo '<h2>9️⃣ Stato Completo (JSON)</h2>';
    echo '<pre>' . wp_json_encode( $frontend_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</pre>';
    
    // 10. Raccomandazioni
    echo '<h2>🔧 Raccomandazioni</h2>';
    
    $issues = array();
    
    if ( empty( $frontend_state['state']['should_display'] ) && empty( $consent_id ) ) {
        $issues[] = 'Il banner non viene mostrato perché non c\'è un consent_id. Prova a cancellare i cookie del browser e ricaricare la pagina.';
    }
    
    if ( empty( $categories ) ) {
        $issues[] = 'Non ci sono categorie di consenso configurate. Vai nelle impostazioni del plugin e configura almeno una categoria.';
    }
    
    if ( empty( $pages['privacy_policy_page_id'] ) && empty( $pages['cookie_policy_page_id'] ) ) {
        $issues[] = 'Non ci sono pagine policy configurate. Configura le pagine Privacy Policy e Cookie Policy nelle impostazioni.';
    }
    
    if ( ! empty( $issues ) ) {
        foreach ( $issues as $issue ) {
            echo '<div class="warning">⚠️ ' . esc_html( $issue ) . '</div>';
        }
    } else {
        echo '<div class="check"><span class="success">✓</span> Tutto sembra configurato correttamente!</div>';
    }
    
    // 11. Azioni suggerite
    echo '<h2>💡 Azioni Suggerite</h2>';
    echo '<div class="check">';
    echo '<ol>';
    echo '<li><strong>Cancella i cookie del browser</strong> per questo sito e ricarica la pagina</li>';
    echo '<li><strong>Attiva la modalità preview</strong> nelle impostazioni del plugin per forzare la visualizzazione del banner</li>';
    echo '<li><strong>Verifica la console del browser</strong> (F12) per eventuali errori JavaScript</li>';
    echo '<li><strong>Controlla che il tema chiami</strong> <code>wp_body_open()</code> e <code>wp_footer()</code></li>';
    echo '</ol>';
    echo '</div>';
    ?>
    
</div>
</body>
</html>

