<?php
/**
 * Ripristina fp_privacy_options['pages'] verso ID pagine con slug canonici (es. privacy-policy, cookie-policy).
 * Uso: php tools/fix-policy-pages-option-mapping.php <WP_ROOT>
 *
 * @package FP\Privacy\Tools
 */

declare( strict_types=1 );

$root = $argv[1] ?? getenv( 'WORDPRESS_ROOT' );
if ( ! is_string( $root ) || '' === $root ) {
	fwrite( STDERR, "Uso: php fix-policy-pages-option-mapping.php <WP_ROOT>\n" );
	exit( 1 );
}

$root = rtrim( $root, '/\\' );
$load = $root . '/wp-load.php';
if ( ! is_readable( $load ) ) {
	fwrite( STDERR, "wp-load non trovato.\n" );
	exit( 1 );
}

require $load;

$key     = 'fp_privacy_options';
$options = get_option( $key, array() );
if ( ! is_array( $options ) ) {
	$options = array();
}

// ID target: privacy IT, cookie IT, cookie EN (adatta se nel tuo sito sono diversi).
$privacy_it = (int) ( $argv[2] ?? 99 );
$cookie_it  = (int) ( $argv[3] ?? 282 );
$cookie_en  = (int) ( $argv[4] ?? 100 );

$options['pages'] = array(
	'privacy_policy_page_id' => array(
		'it_IT' => $privacy_it,
	),
	'cookie_policy_page_id'  => array(
		'it_IT'  => $cookie_it,
		'en'     => $cookie_en,
		'en_US'  => $cookie_en,
	),
);

// Non forzare en_US in languages_active: con WPML, ensure_pages_exist creerebbe una privacy EN duplicata.
// Per rigenerare solo la cookie EN usa uno script dedicato o aggiorna il post 100 dal generatore.

update_option( $key, $options, false );

echo "Aggiornato {$key}['pages']: privacy it_IT={$privacy_it}, cookie it_IT={$cookie_it}, en/en_US={$cookie_en}\n";
