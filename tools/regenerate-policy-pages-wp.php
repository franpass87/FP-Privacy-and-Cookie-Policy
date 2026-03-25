<?php
/**
 * Rigenera privacy/cookie policy su tutte le lingue configurate (stessa logica admin "Detect & regenerate").
 *
 * Uso: php tools/regenerate-policy-pages-wp.php /percorso/root/wordpress
 *      oppure: WORDPRESS_ROOT=/path php tools/regenerate-policy-pages-wp.php
 *
 * @package FP\Privacy\Tools
 */

declare( strict_types=1 );

$root = $argv[1] ?? getenv( 'WORDPRESS_ROOT' );
if ( ! is_string( $root ) || '' === $root ) {
	fwrite( STDERR, "Specificare la root WordPress: argv[1] o WORDPRESS_ROOT.\n" );
	exit( 1 );
}

$root = rtrim( $root, '/\\' );
$load = $root . '/wp-load.php';
if ( ! is_readable( $load ) ) {
	fwrite( STDERR, "wp-load.php non trovato in: {$root}\n" );
	exit( 1 );
}

require $load;

if ( ! class_exists( \FP\Privacy\Core\Kernel::class ) ) {
	fwrite( STDERR, "FP Privacy non caricato (Kernel assente).\n" );
	exit( 1 );
}

\wp_set_current_user( 1 );
if ( ! \current_user_can( 'manage_options' ) ) {
	fwrite( STDERR, "L'utente ID 1 non ha manage_options.\n" );
	exit( 1 );
}

$kernel    = \FP\Privacy\Core\Kernel::make();
$kernel->boot();
$container = $kernel->getContainer();

/** @var \FP\Privacy\Utils\Options $options */
$options = $container->get( \FP\Privacy\Utils\Options::class );
/** @var \FP\Privacy\Admin\PolicyGenerator $generator */
$generator        = $container->get( \FP\Privacy\Admin\PolicyGenerator::class );
$doc_gen          = new \FP\Privacy\Admin\PolicyDocumentGenerator( $options );
$snapshot_manager = new \FP\Privacy\Admin\PolicySnapshotManager( $options );

$options->ensure_pages_exist();

$languages = $options->get_languages();
if ( empty( $languages ) ) {
	$languages = array( \get_locale() );
}

$generated_privacy      = array();
$generated_cookie       = array();
$updated_cookie_post_ids = array();

foreach ( $languages as $language ) {
	$language = $options->normalize_language( $language );
	$privacy_id = $options->get_page_id( 'privacy_policy', $language );
	$cookie_id  = $options->get_page_id( 'cookie_policy', $language );

	$privacy = $generator->generate_privacy_policy( $language );
	$cookie  = $generator->generate_cookie_policy( $language );

	$generated_privacy[ $language ] = $privacy;
	$generated_cookie[ $language ]  = $cookie;

	$ph_privacy = $doc_gen->get_page_placeholder( 'privacy', $language );
	$ph_cookie  = $doc_gen->get_page_placeholder( 'cookie', $language );

	if ( $privacy_id ) {
		\wp_update_post(
			array(
				'ID'           => $privacy_id,
				'post_content' => $ph_privacy,
			)
		);
		\update_post_meta( $privacy_id, \FP\Privacy\Utils\Options::PAGE_MANAGED_META_KEY, \hash( 'sha256', $ph_privacy ) );
		echo "OK privacy post {$privacy_id} ({$language}) shortcode\n";
	} else {
		echo "SKIP privacy: nessun ID per {$language}\n";
	}

	if ( $cookie_id ) {
		\wp_update_post(
			array(
				'ID'           => $cookie_id,
				'post_content' => $ph_cookie,
			)
		);
		\update_post_meta( $cookie_id, \FP\Privacy\Utils\Options::PAGE_MANAGED_META_KEY, \hash( 'sha256', $ph_cookie ) );
		$updated_cookie_post_ids[] = $cookie_id;
		echo "OK cookie post {$cookie_id} ({$language}) shortcode\n";
	} else {
		echo "SKIP cookie: nessun ID per {$language}\n";
	}
}

// Voci cookie solo in mappa (es. en / en_US) non coperte dal loop principale: usa ID diretto (evita normalizer che collassa en_US→it_IT).
$all_opts   = $options->all();
$cookie_map = isset( $all_opts['pages']['cookie_policy_page_id'] ) && \is_array( $all_opts['pages']['cookie_policy_page_id'] )
	? $all_opts['pages']['cookie_policy_page_id']
	: array();
foreach ( $cookie_map as $raw_lang => $cookie_post_id ) {
	$cookie_post_id = (int) $cookie_post_id;
	if ( $cookie_post_id <= 0 || \in_array( $cookie_post_id, $updated_cookie_post_ids, true ) ) {
		continue;
	}
	$locale_for_gen = \FP\Privacy\Utils\Validator::locale( (string) $raw_lang, 'en_US' );
	$cookie         = $generator->generate_cookie_policy( $locale_for_gen );
	$ph_cookie_map  = $doc_gen->get_page_placeholder( 'cookie', $locale_for_gen );
	\wp_update_post(
		array(
			'ID'           => $cookie_post_id,
			'post_content' => $ph_cookie_map,
		)
	);
	\update_post_meta( $cookie_post_id, \FP\Privacy\Utils\Options::PAGE_MANAGED_META_KEY, \hash( 'sha256', $ph_cookie_map ) );
	$updated_cookie_post_ids[]     = $cookie_post_id;
	$generated_cookie[ $locale_for_gen ] = $cookie;
	echo "OK cookie post {$cookie_post_id} ({$locale_for_gen}, solo mappa)\n";
}

$options->bump_revision();
$timestamp = time();
$services  = $generator->snapshot( true );
$options->prime_script_rules_from_services( $services );
$snapshot_manager->save_snapshot( $services, $generated_privacy, $generated_cookie, $timestamp );

echo "Revisione consenso: " . (string) $options->get( 'consent_revision', '?' ) . "\n";
echo "Rigenerazione completata.\n";
