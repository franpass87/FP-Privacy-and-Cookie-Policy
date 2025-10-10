<?php
/**
 * WP-CLI commands.
 *
 * @package FP\Privacy\CLI
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\CLI;

use FP\Privacy\Consent\Cleanup;
use FP\Privacy\Consent\LogModel;
use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Utils\Options;
use WP_CLI;
use WP_CLI_Command;

/**
 * Implements fp-privacy CLI commands.
 */
class Commands extends WP_CLI_Command {
/**
 * Log model.
 *
 * @var LogModel
 */
private $log_model;

/**
 * Options handler.
 *
 * @var Options
 */
private $options;

/**
 * Generator.
 *
 * @var PolicyGenerator
 */
private $generator;

/**
 * Detector registry.
 *
 * @var DetectorRegistry
 */
private $detector;

/**
 * Cleanup handler.
 *
 * @var Cleanup
 */
private $cleanup;

/**
 * Constructor.
 *
 * @param LogModel        $log_model Log model.
 * @param Options         $options   Options.
 * @param PolicyGenerator $generator Generator.
 * @param DetectorRegistry $detector Detector.
 * @param Cleanup         $cleanup   Cleanup handler.
 */
public function __construct( LogModel $log_model, Options $options, PolicyGenerator $generator, DetectorRegistry $detector, Cleanup $cleanup ) {
$this->log_model = $log_model;
$this->options   = $options;
$this->generator = $generator;
$this->detector  = $detector;
$this->cleanup   = $cleanup;
}

/**
 * Display status information.
 */
public function status() {
$total = $this->log_model->count();
$summary = $this->log_model->summary_last_30_days();
WP_CLI::log( 'Consent log table: ' . $this->log_model->get_table() );
WP_CLI::log( 'Total events: ' . $total );
foreach ( $summary as $event => $count ) {
WP_CLI::log( ucfirst( str_replace( '_', ' ', $event ) ) . ': ' . $count );
}
$next = \wp_next_scheduled( 'fp_privacy_cleanup' );
WP_CLI::log( 'Next cleanup: ' . ( $next ? gmdate( 'c', $next ) : 'not scheduled' ) );
}

/**
 * Recreate database table.
 *
 * ## OPTIONS
 *
 * [--force]
 * : Drop and recreate the table.
 */
public function recreate( $args, $assoc_args ) {
if ( isset( $assoc_args['force'] ) && $assoc_args['force'] ) {
global $wpdb;
$wpdb->query( 'DROP TABLE IF EXISTS ' . $this->log_model->get_table() );
WP_CLI::warning( 'Existing table dropped.' );
}

$this->log_model->maybe_create_table();
if ( ! \wp_next_scheduled( 'fp_privacy_cleanup' ) ) {
\wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'fp_privacy_cleanup' );
}
WP_CLI::success( 'Consent log table ready.' );
}

/**
 * Run cleanup immediately.
 */
public function cleanup() {
$this->cleanup->run();
WP_CLI::success( 'Cleanup completed.' );
}

/**
 * Export CSV.
 *
 * ## OPTIONS
 *
 * --file=<path>
 * : Destination file path.
 */
public function export( $args, $assoc_args ) {
if ( empty( $assoc_args['file'] ) ) {
WP_CLI::error( 'Missing --file parameter.' );
}

$file = $assoc_args['file'];
$handle = fopen( $file, 'w' );
if ( ! $handle ) {
WP_CLI::error( 'Unable to open file.' );
}

fputcsv( $handle, array( 'id', 'consent_id', 'event', 'states', 'lang', 'rev', 'created_at' ) );
$batch_size = (int) \apply_filters( 'fp_privacy_csv_export_batch_size', 1000 );
$paged = 1;

while ( true ) {
$entries = $this->log_model->query(
array(
'paged'    => $paged,
'per_page' => $batch_size,
)
);

if ( empty( $entries ) ) {
break;
}

        foreach ( $entries as $entry ) {
            $states = \wp_json_encode( $entry['states'] );

            if ( false === $states ) {
                $states = '{}';
            }

            fputcsv( $handle, array( $entry['id'], $entry['consent_id'], $entry['event'], $states, $entry['lang'], $entry['rev'], $entry['created_at'] ) );
        }

$paged++;
}

fclose( $handle );
WP_CLI::success( 'Export completed: ' . $file );
}

/**
 * Export settings to JSON.
 *
 * --file=<path>
 */
public function settings_export( $args, $assoc_args ) {
if ( empty( $assoc_args['file'] ) ) {
WP_CLI::error( 'Missing --file parameter.' );
}

$file = $assoc_args['file'];
$written = file_put_contents( $file, \wp_json_encode( $this->options->all(), JSON_PRETTY_PRINT ) );
if ( ! $written ) {
WP_CLI::error( 'Unable to write file.' );
}

WP_CLI::success( 'Settings exported to ' . $file );
}

/**
 * Import settings from JSON.
 *
 * --file=<path>
 */
public function settings_import( $args, $assoc_args ) {
if ( empty( $assoc_args['file'] ) || ! file_exists( $assoc_args['file'] ) ) {
WP_CLI::error( 'File not found.' );
}

$data = json_decode( file_get_contents( $assoc_args['file'] ), true );
if ( ! \is_array( $data ) ) {
WP_CLI::error( 'Invalid JSON file.' );
}

$this->options->set( $data );
\do_action( 'fp_privacy_settings_imported', $this->options->all() );

WP_CLI::success( 'Settings imported.' );
}

/**
 * Detect services.
 */
public function detect() {
$services = $this->detector->detect_services();
foreach ( $services as $service ) {
WP_CLI::log( sprintf( '%s [%s] - %s', $service['name'], $service['category'], $service['detected'] ? 'detected' : 'not detected' ) );
}
}

/**
 * Regenerate policy documents.
 *
 * ## OPTIONS
 *
 * [--lang=<lang>]
 * : Language to use.
 *
 * [--bump-revision]
 * : Increment consent revision after regeneration.
 */
public function regenerate( $args, $assoc_args ) {
$lang = isset( $assoc_args['lang'] ) ? $assoc_args['lang'] : \get_locale();
$privacy = $this->generator->generate_privacy_policy( $lang );
$cookie  = $this->generator->generate_cookie_policy( $lang );

WP_CLI::log( '--- Privacy Policy ---' );
WP_CLI::log( $privacy );
WP_CLI::log( '--- Cookie Policy ---' );
WP_CLI::log( $cookie );

if ( isset( $assoc_args['bump-revision'] ) ) {
$this->options->bump_revision();
$this->options->set( $this->options->all() );
WP_CLI::success( 'Consent revision bumped.' );
}
}

/**
 * Genera automaticamente le pagine Cookie Policy e Privacy Policy.
 *
 * ## DESCRIZIONE
 *
 * Questo comando:
 * - Crea automaticamente le pagine Privacy Policy e Cookie Policy se non esistono
 * - Rileva i servizi integrati sul sito
 * - Genera il contenuto delle policy basato sui servizi rilevati
 * - Aggiorna le pagine WordPress con il contenuto generato
 * - Salva uno snapshot dei servizi per riferimento futuro
 *
 * ## OPZIONI
 *
 * [--force]
 * : Forza la rigenerazione anche se le pagine sono state modificate manualmente.
 *
 * [--all-languages]
 * : Genera le policy per tutte le lingue configurate.
 *
 * [--lang=<lang>]
 * : Genera le policy solo per la lingua specificata (es. it_IT, en_US).
 *
 * [--bump-revision]
 * : Incrementa la revisione del consenso dopo la generazione.
 *
 * [--dry-run]
 * : Mostra solo cosa verrebbe fatto senza modificare nulla.
 *
 * ## ESEMPI
 *
 *     # Genera le pagine per tutte le lingue
 *     wp fp-privacy generate-pages --all-languages
 *
 *     # Genera solo per l'italiano
 *     wp fp-privacy generate-pages --lang=it_IT
 *
 *     # Forza la rigenerazione e incrementa la revisione
 *     wp fp-privacy generate-pages --force --bump-revision
 *
 *     # Verifica cosa verrebbe fatto senza modificare
 *     wp fp-privacy generate-pages --dry-run
 *
 * @param array $args Argomenti posizionali.
 * @param array $assoc_args Argomenti associativi.
 */
public function generate_pages( $args, $assoc_args ) {
	$dry_run = isset( $assoc_args['dry-run'] );
	$force = isset( $assoc_args['force'] );
	$all_languages = isset( $assoc_args['all-languages'] );
	$single_lang = isset( $assoc_args['lang'] ) ? $assoc_args['lang'] : null;
	$bump_revision = isset( $assoc_args['bump-revision'] );

	WP_CLI::log( '🚀 Avvio generazione automatica delle policy...' );

	// Determina le lingue da processare
	if ( $single_lang ) {
		$languages = array( $single_lang );
		WP_CLI::log( sprintf( '📝 Lingua selezionata: %s', $single_lang ) );
	} elseif ( $all_languages ) {
		$languages = $this->options->get_languages();
		if ( empty( $languages ) ) {
			$languages = array( \get_locale() );
		}
		WP_CLI::log( sprintf( '🌍 Generazione per tutte le lingue: %s', implode( ', ', $languages ) ) );
	} else {
		$languages = array( \get_locale() );
		WP_CLI::log( sprintf( '📝 Lingua predefinita: %s', $languages[0] ) );
	}

	if ( $dry_run ) {
		WP_CLI::warning( '🔍 Modalità DRY-RUN: nessuna modifica verrà salvata' );
	}

	// Assicura che le pagine esistano
	if ( ! $dry_run ) {
		WP_CLI::log( '📄 Verifica esistenza delle pagine...' );
		$this->options->ensure_pages_exist();
		WP_CLI::success( 'Pagine verificate/create' );
	}

	// Rileva i servizi integrati
	WP_CLI::log( '🔍 Rilevamento servizi integrati...' );
	$services = $this->detector->detect_services( true );
	$detected_count = 0;
	foreach ( $services as $service ) {
		if ( ! empty( $service['detected'] ) ) {
			$detected_count++;
			WP_CLI::log( sprintf( '  ✓ %s [%s]', $service['name'], $service['category'] ) );
		}
	}
	WP_CLI::success( sprintf( 'Rilevati %d servizi', $detected_count ) );

	// Genera e salva le policy per ogni lingua
	$timestamp = time();
	$generated_privacy = array();
	$generated_cookie = array();

	foreach ( $languages as $language ) {
		$language = $this->options->normalize_language( $language );

		WP_CLI::log( '' );
		WP_CLI::log( sprintf( '🌐 Elaborazione lingua: %s', $language ) );

		// Ottieni gli ID delle pagine
		$privacy_id = $this->options->get_page_id( 'privacy_policy', $language );
		$cookie_id = $this->options->get_page_id( 'cookie_policy', $language );

		if ( ! $privacy_id || ! $cookie_id ) {
			WP_CLI::warning( sprintf( 'Pagine non trovate per la lingua %s', $language ) );
			continue;
		}

		WP_CLI::log( sprintf( '  Privacy Policy ID: %d', $privacy_id ) );
		WP_CLI::log( sprintf( '  Cookie Policy ID: %d', $cookie_id ) );

		// Verifica se le pagine sono state modificate manualmente
		if ( ! $force ) {
			$privacy_post = \get_post( $privacy_id );
			$cookie_post = \get_post( $cookie_id );

			$privacy_managed = \get_post_meta( $privacy_id, Options::PAGE_MANAGED_META_KEY, true );
			$cookie_managed = \get_post_meta( $cookie_id, Options::PAGE_MANAGED_META_KEY, true );

			if ( $privacy_post && $cookie_post ) {
				$privacy_content = trim( (string) $privacy_post->post_content );
				$cookie_content = trim( (string) $cookie_post->post_content );

				$privacy_is_shortcode = false !== strpos( $privacy_content, '[fp_privacy_policy' );
				$cookie_is_shortcode = false !== strpos( $cookie_content, '[fp_cookie_policy' );

				if ( ! $privacy_is_shortcode && ! $privacy_managed && strlen( $privacy_content ) > 100 ) {
					WP_CLI::warning( '  Privacy Policy sembra modificata manualmente. Usa --force per sovrascrivere.' );
					continue;
				}

				if ( ! $cookie_is_shortcode && ! $cookie_managed && strlen( $cookie_content ) > 100 ) {
					WP_CLI::warning( '  Cookie Policy sembra modificata manualmente. Usa --force per sovrascrivere.' );
					continue;
				}
			}
		}

		// Genera il contenuto
		WP_CLI::log( '  📝 Generazione Privacy Policy...' );
		$privacy = $this->generator->generate_privacy_policy( $language );
		$generated_privacy[ $language ] = $privacy;

		WP_CLI::log( '  📝 Generazione Cookie Policy...' );
		$cookie = $this->generator->generate_cookie_policy( $language );
		$generated_cookie[ $language ] = $cookie;

		WP_CLI::log( sprintf( '  📊 Privacy Policy: %d caratteri', strlen( $privacy ) ) );
		WP_CLI::log( sprintf( '  📊 Cookie Policy: %d caratteri', strlen( $cookie ) ) );

		// Aggiorna le pagine
		if ( ! $dry_run ) {
			WP_CLI::log( '  💾 Aggiornamento pagine...' );

			$privacy_result = \wp_update_post(
				array(
					'ID'           => $privacy_id,
					'post_content' => $privacy,
					'post_status'  => 'publish',
				)
			);

			$cookie_result = \wp_update_post(
				array(
					'ID'           => $cookie_id,
					'post_content' => $cookie,
					'post_status'  => 'publish',
				)
			);

			if ( ! \is_wp_error( $privacy_result ) && ! \is_wp_error( $cookie_result ) ) {
				\delete_post_meta( $privacy_id, Options::PAGE_MANAGED_META_KEY );
				\delete_post_meta( $cookie_id, Options::PAGE_MANAGED_META_KEY );
				WP_CLI::success( '  Pagine aggiornate con successo!' );
			} else {
				WP_CLI::error( '  Errore nell\'aggiornamento delle pagine' );
			}
		}
	}

	// Salva lo snapshot dei servizi
	if ( ! $dry_run ) {
		WP_CLI::log( '' );
		WP_CLI::log( '💾 Salvataggio snapshot servizi...' );

		$this->options->prime_script_rules_from_services( $services );

		$snapshots = array(
			'services' => array(
				'detected'     => array_values( array_filter( $services, static function( $s ) {
					return ! empty( $s['detected'] );
				} ) ),
				'generated_at' => $timestamp,
			),
			'policies' => array(
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

		$payload = $this->options->all();
		$payload['snapshots'] = $snapshots;
		$payload['detector_alert'] = array_merge(
			$this->options->get_default_detector_alert(),
			array( 'last_checked' => $timestamp )
		);

		$this->options->set( $payload );

		\do_action( 'fp_privacy_snapshots_refreshed', $snapshots );

		WP_CLI::success( 'Snapshot salvato' );
	}

	// Incrementa la revisione se richiesto
	if ( $bump_revision && ! $dry_run ) {
		WP_CLI::log( '' );
		WP_CLI::log( '🔄 Incremento revisione consenso...' );
		$this->options->bump_revision();
		$payload = $this->options->all();
		$this->options->set( $payload );
		WP_CLI::success( sprintf( 'Revisione aggiornata a: %s', $this->options->get( 'consent_revision', '1.0' ) ) );
	}

	// Riepilogo finale
	WP_CLI::log( '' );
	WP_CLI::log( '═══════════════════════════════════════════' );
	WP_CLI::success( '✅ Generazione completata!' );
	WP_CLI::log( sprintf( '📊 Lingue processate: %d', count( $languages ) ) );
	WP_CLI::log( sprintf( '🔌 Servizi rilevati: %d', $detected_count ) );
	WP_CLI::log( sprintf( '⏰ Timestamp: %s', gmdate( 'Y-m-d H:i:s', $timestamp ) ) );

	if ( $dry_run ) {
		WP_CLI::log( '' );
		WP_CLI::warning( 'Modalità DRY-RUN: nessuna modifica è stata salvata' );
		WP_CLI::log( 'Rimuovi --dry-run per applicare le modifiche' );
	}
}
}
