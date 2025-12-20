<?php
/**
 * WP-CLI commands for consent management.
 *
 * @package FP\Privacy\CLI
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\CLI\Commands;

use FP\Privacy\Consent\Cleanup;
use FP\Privacy\Consent\LogModel;
use WP_CLI;

/**
 * Handles WP-CLI commands related to consent management.
 */
class ConsentCommands {
	/**
	 * Log model.
	 *
	 * @var LogModel
	 */
	private $log_model;

	/**
	 * Cleanup handler.
	 *
	 * @var Cleanup
	 */
	private $cleanup;

	/**
	 * Constructor.
	 *
	 * @param LogModel $log_model Log model.
	 * @param Cleanup  $cleanup   Cleanup handler.
	 */
	public function __construct( LogModel $log_model, Cleanup $cleanup ) {
		$this->log_model = $log_model;
		$this->cleanup   = $cleanup;
	}

	/**
	 * Display status information.
	 *
	 * @return void
	 */
	public function status() {
		$total   = $this->log_model->count();
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
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
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
	 *
	 * @return void
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
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 * @return void
	 */
	public function export( $args, $assoc_args ) {
		if ( empty( $assoc_args['file'] ) ) {
			WP_CLI::error( 'Missing --file parameter.' );
		}

		$file   = $assoc_args['file'];
		$handle = fopen( $file, 'w' );
		if ( ! $handle ) {
			WP_CLI::error( 'Unable to open file.' );
		}

		fputcsv( $handle, array( 'id', 'consent_id', 'event', 'states', 'lang', 'rev', 'created_at' ) );
		$batch_size = (int) \apply_filters( 'fp_privacy_csv_export_batch_size', 1000 );
		$paged      = 1;

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
}
















