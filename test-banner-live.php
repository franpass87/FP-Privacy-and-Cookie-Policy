<?php
/**
 * Test LIVE del banner
 * Carica WordPress + Plugin e mostra il banner forzato
 */

require_once __DIR__ . '/../../../wp-load.php';

if ( ! class_exists( '\\FP\\Privacy\\Plugin' ) ) {
    die( '❌ Plugin non caricato' );
}

// Forza modalità preview
add_filter( 'option_fp_privacy', function( $value ) {
    if ( is_array( $value ) ) {
        $value['preview_mode'] = true;
    }
    return $value;
});

// Cancella il cookie
$cookie_name = \FP\Privacy\Frontend\ConsentState::COOKIE_NAME;
setcookie( $cookie_name, '', time() - 3600, '/', '', false, true );
unset( $_COOKIE[ $cookie_name ] );

// Simula una pagina WordPress
get_header();
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Banner FP-Privacy</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #2563EB;
            margin-top: 0;
        }
        .alert {
            background: #dbeafe;
            border-left: 4px solid #2563EB;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success {
            background: #d1fae5;
            border-left-color: #10b981;
        }
        .code {
            background: #1f2937;
            color: #f9fafb;
            padding: 20px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
            margin: 20px 0;
        }
    </style>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    
    <?php
    // Chiama manualmente i hook per il rendering del banner
    do_action( 'wp_body_open' );
    do_action( 'nectar_hook_after_body_open' );
    ?>
    
    <div class="container">
        <h1>🧪 Test Banner FP-Privacy</h1>
        
        <div class="alert success">
            <strong>✅ WordPress caricato</strong><br>
            <strong>✅ Plugin FP-Privacy attivo</strong><br>
            <strong>✅ Modalità preview ATTIVA</strong><br>
            <strong>✅ Hook renderizzati</strong>
        </div>
        
        <div class="alert">
            <strong>📋 Verifica:</strong>
            <ol>
                <li>Il banner dovrebbe apparire in basso alla pagina</li>
                <li>Apri la console del browser (F12) per vedere i log di debug</li>
                <li>Cerca eventuali errori JavaScript</li>
                <li>Verifica che gli asset siano caricati correttamente</li>
            </ol>
        </div>
        
        <div class="code">
            <strong>Scripts caricati:</strong><br>
            <?php
            global $wp_scripts;
            if ( $wp_scripts ) {
                foreach ( $wp_scripts->queue as $handle ) {
                    if ( strpos( $handle, 'fp-privacy' ) !== false ) {
                        echo '✓ ' . esc_html( $handle ) . '<br>';
                    }
                }
            }
            ?>
            <br>
            <strong>Styles caricati:</strong><br>
            <?php
            global $wp_styles;
            if ( $wp_styles ) {
                foreach ( $wp_styles->queue as $handle ) {
                    if ( strpos( $handle, 'fp-privacy' ) !== false ) {
                        echo '✓ ' . esc_html( $handle ) . '<br>';
                    }
                }
            }
            ?>
        </div>
        
        <div class="alert">
            <strong>🔍 Container del banner:</strong><br>
            <em>Il div sottostante dovrebbe contenere il banner se JavaScript funziona:</em>
        </div>
        
    </div>
    
    <?php wp_footer(); ?>
    
    <script>
        // Debug aggiuntivo
        console.log('=== FP Privacy Test ===');
        console.log('FP_PRIVACY_DATA:', window.FP_PRIVACY_DATA);
        console.log('Banner root:', document.getElementById('fp-privacy-banner-root'));
        console.log('Scripts loaded:', performance.getEntriesByType('resource')
            .filter(r => r.name.includes('fp-privacy'))
            .map(r => r.name));
    </script>
</body>
</html>

