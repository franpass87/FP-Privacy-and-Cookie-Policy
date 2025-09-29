<?php
/**
 * WP-CLI commands.
 *
 * @package FP\Privacy\CLI
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
$next = \\wp_next_scheduled( 'fp_privacy_cleanup' );
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
if ( ! \\wp_next_scheduled( 'fp_privacy_cleanup' ) ) {
\\wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', 'fp_privacy_cleanup' );
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
fputcsv( $handle, array( $entry['id'], $entry['consent_id'], $entry['event'], json_encode( $entry['states'] ), $entry['lang'], $entry['rev'], $entry['created_at'] ) );
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
$written = file_put_contents( $file, \\wp_json_encode( $this->options->all(), JSON_PRETTY_PRINT ) );
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
\\do_action( 'fp_privacy_settings_imported', $this->options->all() );

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
$lang = isset( $assoc_args['lang'] ) ? $assoc_args['lang'] : \\get_locale();
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
}
