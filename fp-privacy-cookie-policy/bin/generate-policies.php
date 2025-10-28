#!/usr/bin/env php
<?php
/**
 * Script standalone per generare automaticamente le pagine Privacy e Cookie Policy.
 *
 * Questo script può essere eseguito direttamente da linea di comando senza WP-CLI.
 *
 * Uso:
 *   php bin/generate-policies.php [opzioni]
 *
 * Opzioni:
 *   --force              Forza la rigenerazione anche se le pagine sono state modificate
 *   --all-languages      Genera per tutte le lingue configurate
 *   --lang=it_IT         Genera solo per la lingua specificata
 *   --bump-revision      Incrementa la revisione del consenso
 *   --dry-run            Mostra solo cosa verrebbe fatto
 *   --help               Mostra questo messaggio di aiuto
 *
 * Esempi:
 *   php bin/generate-policies.php --all-languages
 *   php bin/generate-policies.php --lang=it_IT --force
 *   php bin/generate-policies.php --dry-run
 *
 * @package FP\Privacy
 * @author Francesco Passeri
 */

// Trova il file wp-load.php
$wp_load = null;
$search_paths = array(
	__DIR__ . '/../../../../wp-load.php',  // Plugin installato in wp-content/plugins
	__DIR__ . '/../../../wp-load.php',
	__DIR__ . '/../../wp-load.php',
	__DIR__ . '/../wp-load.php',
);

foreach ( $search_paths as $path ) {
	if ( file_exists( $path ) ) {
		$wp_load = $path;
		break;
	}
}

if ( ! $wp_load ) {
	echo "❌ ERRORE: impossibile trovare wp-load.php\n";
	echo "Assicurati di eseguire lo script dalla directory corretta.\n";
	exit( 1 );
}

// Carica WordPress
define( 'WP_USE_THEMES', false );
require_once $wp_load;

// Verifica che il plugin sia attivo
if ( ! class_exists( '\\FP\\Privacy\\Plugin' ) ) {
	echo "❌ ERRORE: il plugin FP Privacy & Cookie Policy non è attivo\n";
	exit( 1 );
}

/**
 * Classe per l'output colorato.
 */
class ConsoleOutput {
	const COLOR_RESET = "\033[0m";
	const COLOR_RED = "\033[31m";
	const COLOR_GREEN = "\033[32m";
	const COLOR_YELLOW = "\033[33m";
	const COLOR_BLUE = "\033[34m";
	const COLOR_CYAN = "\033[36m";

	public static function success( $message ) {
		echo self::COLOR_GREEN . '✓ ' . $message . self::COLOR_RESET . "\n";
	}

	public static function error( $message ) {
		echo self::COLOR_RED . '✗ ' . $message . self::COLOR_RESET . "\n";
	}

	public static function warning( $message ) {
		echo self::COLOR_YELLOW . '⚠ ' . $message . self::COLOR_RESET . "\n";
	}

	public static function info( $message ) {
		echo self::COLOR_CYAN . 'ℹ ' . $message . self::COLOR_RESET . "\n";
	}

	public static function log( $message ) {
		echo $message . "\n";
	}
}

/**
 * Parser per gli argomenti della riga di comando.
 */
function parse_arguments( $argv ) {
	$args = array(
		'force'         => false,
		'all-languages' => false,
		'lang'          => null,
		'bump-revision' => false,
		'dry-run'       => false,
		'help'          => false,
	);

	foreach ( $argv as $arg ) {
		if ( '--force' === $arg ) {
			$args['force'] = true;
		} elseif ( '--all-languages' === $arg ) {
			$args['all-languages'] = true;
		} elseif ( 0 === strpos( $arg, '--lang=' ) ) {
			$args['lang'] = substr( $arg, 7 );
		} elseif ( '--bump-revision' === $arg ) {
			$args['bump-revision'] = true;
		} elseif ( '--dry-run' === $arg ) {
			$args['dry-run'] = true;
		} elseif ( '--help' === $arg || '-h' === $arg ) {
			$args['help'] = true;
		}
	}

	return $args;
}

/**
 * Mostra l'help.
 */
function show_help() {
	echo "\n";
	echo "╔══════════════════════════════════════════════════════════════════════╗\n";
	echo "║  Generatore Automatico Privacy e Cookie Policy                       ║\n";
	echo "╚══════════════════════════════════════════════════════════════════════╝\n";
	echo "\n";
	echo "UTILIZZO:\n";
	echo "  php bin/generate-policies.php [opzioni]\n";
	echo "\n";
	echo "OPZIONI:\n";
	echo "  --force              Forza la rigenerazione anche per pagine modificate\n";
	echo "  --all-languages      Genera le policy per tutte le lingue configurate\n";
	echo "  --lang=<codice>      Genera solo per la lingua specificata (es. it_IT)\n";
	echo "  --bump-revision      Incrementa la revisione del consenso\n";
	echo "  --dry-run            Simula l'esecuzione senza modificare nulla\n";
	echo "  --help, -h           Mostra questo messaggio di aiuto\n";
	echo "\n";
	echo "ESEMPI:\n";
	echo "  # Genera le pagine per tutte le lingue\n";
	echo "  php bin/generate-policies.php --all-languages\n";
	echo "\n";
	echo "  # Genera solo per l'italiano\n";
	echo "  php bin/generate-policies.php --lang=it_IT\n";
	echo "\n";
	echo "  # Forza la rigenerazione e incrementa la revisione\n";
	echo "  php bin/generate-policies.php --force --bump-revision\n";
	echo "\n";
	echo "  # Verifica cosa verrebbe fatto senza modificare\n";
	echo "  php bin/generate-policies.php --dry-run\n";
	echo "\n";
}

// Parse argomenti
$args = parse_arguments( array_slice( $argv, 1 ) );

// Mostra help se richiesto
if ( $args['help'] ) {
	show_help();
	exit( 0 );
}

// Inizia l'esecuzione
ConsoleOutput::log( '' );
ConsoleOutput::log( '═══════════════════════════════════════════════════════════════════════' );
ConsoleOutput::info( '🚀 Generatore Automatico Privacy e Cookie Policy' );
ConsoleOutput::log( '═══════════════════════════════════════════════════════════════════════' );
ConsoleOutput::log( '' );

// Ottieni le istanze necessarie
$options = \FP\Privacy\Utils\Options::instance();
$detector = new \FP\Privacy\Integrations\DetectorRegistry();
$view = new \FP\Privacy\Utils\View();
$generator = new \FP\Privacy\Admin\PolicyGenerator( $options, $detector, $view );

// Determina le lingue
if ( $args['lang'] ) {
	$languages = array( $args['lang'] );
	ConsoleOutput::info( sprintf( '📝 Lingua selezionata: %s', $args['lang'] ) );
} elseif ( $args['all-languages'] ) {
	$languages = $options->get_languages();
	if ( empty( $languages ) ) {
		$languages = array( get_locale() );
	}
	ConsoleOutput::info( sprintf( '🌍 Generazione per tutte le lingue: %s', implode( ', ', $languages ) ) );
} else {
	$languages = array( get_locale() );
	ConsoleOutput::info( sprintf( '📝 Lingua predefinita: %s', $languages[0] ) );
}

if ( $args['dry-run'] ) {
	ConsoleOutput::warning( '🔍 Modalità DRY-RUN: nessuna modifica verrà salvata' );
}
ConsoleOutput::log( '' );

// Assicura che le pagine esistano
if ( ! $args['dry-run'] ) {
	ConsoleOutput::info( '📄 Verifica esistenza delle pagine...' );
	$options->ensure_pages_exist();
	ConsoleOutput::success( 'Pagine verificate/create' );
}

// Rileva i servizi
ConsoleOutput::log( '' );
ConsoleOutput::info( '🔍 Rilevamento servizi integrati...' );
$services = $detector->detect_services( true );
$detected_count = 0;

foreach ( $services as $service ) {
	if ( ! empty( $service['detected'] ) ) {
		$detected_count++;
		ConsoleOutput::log( sprintf( '  ✓ %s [%s]', $service['name'], $service['category'] ) );
	}
}

ConsoleOutput::success( sprintf( 'Rilevati %d servizi', $detected_count ) );

// Genera le policy per ogni lingua
$timestamp = time();
$generated_privacy = array();
$generated_cookie = array();
$pages_updated = 0;

foreach ( $languages as $language ) {
	$language = $options->normalize_language( $language );

	ConsoleOutput::log( '' );
	ConsoleOutput::log( '───────────────────────────────────────────────────────────────────────' );
	ConsoleOutput::info( sprintf( '🌐 Elaborazione lingua: %s', $language ) );

	// Ottieni gli ID delle pagine
	$privacy_id = $options->get_page_id( 'privacy_policy', $language );
	$cookie_id = $options->get_page_id( 'cookie_policy', $language );

	if ( ! $privacy_id || ! $cookie_id ) {
		ConsoleOutput::warning( sprintf( 'Pagine non trovate per la lingua %s', $language ) );
		continue;
	}

	ConsoleOutput::log( sprintf( '  Privacy Policy ID: %d', $privacy_id ) );
	ConsoleOutput::log( sprintf( '  Cookie Policy ID: %d', $cookie_id ) );

	// Verifica se le pagine sono state modificate manualmente
	if ( ! $args['force'] ) {
		$privacy_post = get_post( $privacy_id );
		$cookie_post = get_post( $cookie_id );

		$privacy_managed = get_post_meta( $privacy_id, \FP\Privacy\Utils\Options::PAGE_MANAGED_META_KEY, true );
		$cookie_managed = get_post_meta( $cookie_id, \FP\Privacy\Utils\Options::PAGE_MANAGED_META_KEY, true );

		if ( $privacy_post && $cookie_post ) {
			$privacy_content = trim( (string) $privacy_post->post_content );
			$cookie_content = trim( (string) $cookie_post->post_content );

			$privacy_is_shortcode = false !== strpos( $privacy_content, '[fp_privacy_policy' );
			$cookie_is_shortcode = false !== strpos( $cookie_content, '[fp_cookie_policy' );

			if ( ! $privacy_is_shortcode && ! $privacy_managed && strlen( $privacy_content ) > 100 ) {
				ConsoleOutput::warning( '  Privacy Policy modificata manualmente. Usa --force per sovrascrivere.' );
				continue;
			}

			if ( ! $cookie_is_shortcode && ! $cookie_managed && strlen( $cookie_content ) > 100 ) {
				ConsoleOutput::warning( '  Cookie Policy modificata manualmente. Usa --force per sovrascrivere.' );
				continue;
			}
		}
	}

	// Genera il contenuto
	ConsoleOutput::info( '  📝 Generazione Privacy Policy...' );
	$privacy = $generator->generate_privacy_policy( $language );
	$generated_privacy[ $language ] = $privacy;

	ConsoleOutput::info( '  📝 Generazione Cookie Policy...' );
	$cookie = $generator->generate_cookie_policy( $language );
	$generated_cookie[ $language ] = $cookie;

	ConsoleOutput::log( sprintf( '  📊 Privacy Policy: %s caratteri', number_format( strlen( $privacy ) ) ) );
	ConsoleOutput::log( sprintf( '  📊 Cookie Policy: %s caratteri', number_format( strlen( $cookie ) ) ) );

	// Aggiorna le pagine
	if ( ! $args['dry-run'] ) {
		ConsoleOutput::info( '  💾 Aggiornamento pagine...' );

		$privacy_result = wp_update_post(
			array(
				'ID'           => $privacy_id,
				'post_content' => $privacy,
				'post_status'  => 'publish',
			)
		);

		$cookie_result = wp_update_post(
			array(
				'ID'           => $cookie_id,
				'post_content' => $cookie,
				'post_status'  => 'publish',
			)
		);

		if ( ! is_wp_error( $privacy_result ) && ! is_wp_error( $cookie_result ) ) {
			delete_post_meta( $privacy_id, \FP\Privacy\Utils\Options::PAGE_MANAGED_META_KEY );
			delete_post_meta( $cookie_id, \FP\Privacy\Utils\Options::PAGE_MANAGED_META_KEY );
			ConsoleOutput::success( '  Pagine aggiornate con successo!' );
			$pages_updated++;
		} else {
			ConsoleOutput::error( '  Errore nell\'aggiornamento delle pagine' );
		}
	} else {
		$pages_updated++;
	}
}

// Salva lo snapshot
if ( ! $args['dry-run'] && $pages_updated > 0 ) {
	ConsoleOutput::log( '' );
	ConsoleOutput::info( '💾 Salvataggio snapshot servizi...' );

	$options->prime_script_rules_from_services( $services );

	$snapshots = array(
		'services' => array(
			'detected'     => array_values(
				array_filter(
					$services,
					static function ( $s ) {
						return ! empty( $s['detected'] );
					}
				)
			),
			'generated_at' => $timestamp,
		),
		'policies'  => array(
			'privacy' => array(),
			'cookie'  => array(),
		),
	);

	foreach ( $generated_privacy as $lang => $content ) {
		$snapshots['policies']['privacy'][ $lang ] = array(
			'content'      => $content,
			'generated_at' => $timestamp,
		);
	}

	foreach ( $generated_cookie as $lang => $content ) {
		$snapshots['policies']['cookie'][ $lang ] = array(
			'content'      => $content,
			'generated_at' => $timestamp,
		);
	}

	$payload = $options->all();
	$payload['snapshots'] = $snapshots;
	$payload['detector_alert'] = array_merge(
		$options->get_default_detector_alert(),
		array( 'last_checked' => $timestamp )
	);

	$options->set( $payload );

	do_action( 'fp_privacy_snapshots_refreshed', $snapshots );

	ConsoleOutput::success( 'Snapshot salvato' );
}

// Incrementa la revisione
if ( $args['bump-revision'] && ! $args['dry-run'] ) {
	ConsoleOutput::log( '' );
	ConsoleOutput::info( '🔄 Incremento revisione consenso...' );
	$options->bump_revision();
	$payload = $options->all();
	$options->set( $payload );
	$new_revision = $options->get( 'consent_revision', '1.0' );
	ConsoleOutput::success( sprintf( 'Revisione aggiornata a: %s', $new_revision ) );
}

// Riepilogo finale
ConsoleOutput::log( '' );
ConsoleOutput::log( '═══════════════════════════════════════════════════════════════════════' );
ConsoleOutput::success( '✅ Generazione completata!' );
ConsoleOutput::log( '' );
ConsoleOutput::log( sprintf( '📊 Lingue processate:   %d', count( $languages ) ) );
ConsoleOutput::log( sprintf( '📄 Pagine aggiornate:   %d', $pages_updated * 2 ) );
ConsoleOutput::log( sprintf( '🔌 Servizi rilevati:    %d', $detected_count ) );
ConsoleOutput::log( sprintf( '⏰ Timestamp:           %s', gmdate( 'Y-m-d H:i:s', $timestamp ) ) );
ConsoleOutput::log( '' );

if ( $args['dry-run'] ) {
	ConsoleOutput::warning( '⚠  Modalità DRY-RUN: nessuna modifica è stata salvata' );
	ConsoleOutput::log( '   Rimuovi --dry-run per applicare le modifiche' );
	ConsoleOutput::log( '' );
}

// Mostra i link alle pagine
if ( ! $args['dry-run'] && $pages_updated > 0 ) {
	ConsoleOutput::log( '🔗 Link alle pagine generate:' );
	ConsoleOutput::log( '' );
	foreach ( $languages as $language ) {
		$privacy_id = $options->get_page_id( 'privacy_policy', $language );
		$cookie_id = $options->get_page_id( 'cookie_policy', $language );

		if ( $privacy_id ) {
			ConsoleOutput::log( sprintf( '   Privacy Policy (%s): %s', $language, get_permalink( $privacy_id ) ) );
		}
		if ( $cookie_id ) {
			ConsoleOutput::log( sprintf( '   Cookie Policy (%s):  %s', $language, get_permalink( $cookie_id ) ) );
		}
	}
	ConsoleOutput::log( '' );
}

ConsoleOutput::log( '═══════════════════════════════════════════════════════════════════════' );
ConsoleOutput::log( '' );

exit( 0 );
