<?php
/**
 * Consent log data loader.
 *
 * @package FP\Privacy\Admin
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Admin;

use FP\Privacy\Consent\LogModel;

/**
 * Handles loading and parsing of consent log data.
 */
class ConsentLogDataLoader {
	/**
	 * Log model.
	 *
	 * @var LogModel
	 */
	private $log_model;

	/**
	 * Default per-page size.
	 *
	 * @var int
	 */
	private $default_per_page;

	/**
	 * Constructor.
	 *
	 * @param LogModel $log_model        Log model.
	 * @param int      $default_per_page Default per-page size.
	 */
	public function __construct( LogModel $log_model, $default_per_page = 50 ) {
		$this->log_model        = $log_model;
		$this->default_per_page = $default_per_page;
	}

	/**
	 * Parse request args with sanitization.
	 *
	 * @return array<string, mixed>
	 */
	public function get_request_args() {
		$paged   = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$per_page = $this->default_per_page;

		return array(
			'paged'    => $paged,
			'per_page' => $per_page,
			'event'    => isset( $_GET['event'] ) ? \sanitize_text_field( \wp_unslash( $_GET['event'] ) ) : '',
			'search'   => isset( $_GET['s'] ) ? \sanitize_text_field( \wp_unslash( $_GET['s'] ) ) : '',
			'from'     => isset( $_GET['from'] ) ? \sanitize_text_field( \wp_unslash( $_GET['from'] ) ) : '',
			'to'       => isset( $_GET['to'] ) ? \sanitize_text_field( \wp_unslash( $_GET['to'] ) ) : '',
		);
	}

	/**
	 * Load data with defensive guards.
	 *
	 * @param array<string, mixed> $args Args.
	 *
	 * @return array<string, mixed>
	 */
	public function load_data_safe( array $args ) {
		$error = false;

		try {
			$entries = $this->log_model->query( $args );
		} catch ( \Throwable $e ) {
			$entries = array();
			$error   = true;
		}

		try {
			$total = $this->log_model->count( $args );
		} catch ( \Throwable $e ) {
			$total = 0;
			$error = true;
		}

		try {
			$summary = $this->log_model->summary_last_30_days();
		} catch ( \Throwable $e ) {
			$summary = array(
				'accept_all'    => 0,
				'reject_all'    => 0,
				'consent'       => 0,
				'reset'         => 0,
				'revision_bump' => 0,
			);
			$error   = true;
		}

		return array(
			'entries' => $entries,
			'total'   => $total,
			'summary' => $summary,
			'error'   => $error,
		);
	}
}















