<?php
/**
 * Debug script per verificare lo stato del consenso
 * 
 * Questo script aiuta a identificare problemi con la gestione del consenso
 * e può essere utilizzato per il debugging.
 */

// Assicurati che WordPress sia caricato
if ( ! defined( 'ABSPATH' ) ) {
    require_once( '../../../wp-load.php' );
}

// Carica il plugin se non è già caricato
if ( ! class_exists( 'FP\Privacy\Frontend\ConsentState' ) ) {
    require_once( 'src/Frontend/ConsentState.php' );
    require_once( 'src/Utils/Options.php' );
    require_once( 'src/Consent/LogModel.php' );
}

use FP\Privacy\Frontend\ConsentState;
use FP\Privacy\Utils\Options;
use FP\Privacy\Consent\LogModel;

echo "<h2>Debug Stato Consenso FP Privacy</h2>\n";

try {
    // Inizializza le classi necessarie
    $options = new Options();
    $log_model = new LogModel();
    $consent_state = new ConsentState( $options, $log_model );
    
    // Ottieni lo stato del frontend
    $frontend_state = $consent_state->get_frontend_state( 'it_IT' );
    
    echo "<h3>Stato Frontend:</h3>\n";
    echo "<pre>" . print_r( $frontend_state, true ) . "</pre>\n";
    
    // Verifica i cookie
    echo "<h3>Cookie Attuali:</h3>\n";
    echo "<pre>";
    foreach ( $_COOKIE as $name => $value ) {
        if ( strpos( $name, 'fp_consent' ) !== false ) {
            echo "Cookie: $name = $value\n";
        }
    }
    echo "</pre>\n";
    
    // Verifica le opzioni del plugin
    echo "<h3>Opzioni Plugin:</h3>\n";
    echo "<pre>";
    echo "Consent Revision: " . $options->get( 'consent_revision', 1 ) . "\n";
    echo "Preview Mode: " . ( $options->get( 'preview_mode', false ) ? 'true' : 'false' ) . "\n";
    echo "</pre>\n";
    
    // Verifica se il banner dovrebbe essere mostrato
    $should_display = $frontend_state['state']['should_display'];
    echo "<h3>Risultato:</h3>\n";
    echo "<p><strong>Il banner " . ( $should_display ? "DOVREBBE" : "NON DOVREBBE" ) . " essere mostrato.</strong></p>\n";
    
    if ( $should_display ) {
        echo "<p style='color: red;'>⚠️ PROBLEMA: Il banner viene mostrato anche se il consenso è già stato dato!</p>\n";
    } else {
        echo "<p style='color: green;'>✅ OK: Il banner non viene mostrato correttamente.</p>\n";
    }
    
} catch ( Exception $e ) {
    echo "<p style='color: red;'>Errore: " . $e->getMessage() . "</p>\n";
}

echo "<hr>\n";
echo "<p><small>Script di debug generato il " . date( 'Y-m-d H:i:s' ) . "</small></p>\n";
?>
