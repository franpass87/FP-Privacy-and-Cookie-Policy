<?php
/**
 * Script per forzare la visualizzazione del banner
 * 
 * Esegui questo script per attivare la modalità preview e cancellare il cookie
 */

require_once __DIR__ . '/../../../wp-load.php';

if ( ! class_exists( '\\FP\\Privacy\\Plugin' ) ) {
    die( '❌ Plugin non caricato' );
}

// Attiva modalità preview
$options = \FP\Privacy\Utils\Options::instance();
$all = $options->all();
$all['preview_mode'] = true;
$options->set( $all );

// Cancella il cookie
$cookie_name = \FP\Privacy\Frontend\ConsentState::COOKIE_NAME;
if ( isset( $_COOKIE[ $cookie_name ] ) ) {
    setcookie( $cookie_name, '', time() - 3600, '/', '', false, true );
    unset( $_COOKIE[ $cookie_name ] );
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Banner Forzato</title>
    <style>
        body { font-family: system-ui; padding: 40px; text-align: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .box { background: white; color: #333; padding: 30px; border-radius: 12px; max-width: 500px; margin: 0 auto; box-shadow: 0 10px 40px rgba(0,0,0,0.3); }
        h1 { margin-top: 0; }
        .success { color: #10b981; font-size: 48px; }
        a { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #2563EB; color: white; text-decoration: none; border-radius: 6px; font-weight: bold; }
        a:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="box">
        <div class="success">✓</div>
        <h1>Banner Forzato!</h1>
        <p><strong>✅ Modalità preview attivata</strong></p>
        <p><strong>✅ Cookie cancellato</strong></p>
        <p>Ora visita il tuo sito in frontend per vedere il banner.</p>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Vai alla Homepage</a>
    </div>
</body>
</html>

