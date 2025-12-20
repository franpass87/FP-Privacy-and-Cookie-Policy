<?php
/**
 * Consent log CSV exporter.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Consent\LogModel;

/**
 * Handles CSV export of consent log entries.
 */
class ConsentLogExporter {
	/**
	 * Log model.
	 *
	 * @var LogModel
	 */
	private $log_model;

	/**
	 * Constructor.
	 *
	 * @param LogModel $log_model Log model.
	 */
	public function __construct( LogModel $log_model ) {
		$this->log_model = $log_model;
	}

	/**
	 * Handle CSV export request.
	 *
	 * @return void
	 */
	public function handle_export() {
		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_die( \esc_html__( 'Permission denied.', 'fp-privacy' ) );
		}

		\check_admin_referer( 'fp_privacy_export_csv' );

		$args = array(
			'event'  => isset( $_GET['event'] ) ? \sanitize_text_field( \wp_unslash( $_GET['event'] ) ) : '',
			'search' => isset( $_GET['s'] ) ? \sanitize_text_field( \wp_unslash( $_GET['s'] ) ) : '',
			'from'   => isset( $_GET['from'] ) ? \sanitize_text_field( \wp_unslash( $_GET['from'] ) ) : '',
			'to'     => isset( $_GET['to'] ) ? \sanitize_text_field( \wp_unslash( $_GET['to'] ) ) : '',
		);

		$batch = (int) \apply_filters( 'fp_privacy_csv_export_batch_size', 1000 );
		if ( $batch < 1 ) {
			$batch = 1000;
		}

		$filename = 'fp-consent-log-' . \gmdate( 'Ymd-His' ) . '.csv';
		$handle   = \fopen( 'php://output', 'w' );

		if ( false === $handle ) {
			\wp_die( \esc_html__( 'Unable to open export stream.', 'fp-privacy' ) );
		}

		\nocache_headers();
		\header( 'Content-Type: text/csv; charset=utf-8' );
		\header( 'Content-Disposition: attachment; filename="' . \sanitize_file_name( $filename ) . '"' );

		\fputcsv( $handle, array( 'consent_id', 'event', 'lang', 'rev', 'created_at', 'ip_hash', 'user_agent', 'states' ) );

		$page = 1;

		while ( true ) {
			$entries = $this->log_model->query( array_merge( $args, array( 'paged' => $page, 'per_page' => $batch ) ) );

			if ( empty( $entries ) ) {
				break;
			}

			foreach ( $entries as $entry ) {
				\fputcsv(
					$handle,
					array(
						$entry['consent_id'],
						$entry['event'],
						$entry['lang'],
						(int) $entry['rev'],
						$entry['created_at'],
						$entry['ip_hash'],
						$entry['ua'],
						self::encode_states_for_csv( $entry['states'] ),
					)
				);
			}

			$page++;
			if ( count( $entries ) < $batch ) {
				break;
			}
		}

		\fclose( $handle );
		exit;
	}

	/**
	 * Encode states payload safely for CSV output.
	 *
	 * @param mixed $states States payload.
	 *
	 * @return string
	 */
	private static function encode_states_for_csv( $states ) {
		$encoded = \wp_json_encode( $states );

		if ( false === $encoded ) {
			return '{}';
		}

		return $encoded;
	}
}















