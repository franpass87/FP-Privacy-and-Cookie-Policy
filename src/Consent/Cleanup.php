<?php
/**
 * Cleanup handler.
 *
 * @package FP\Privacy\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Consent;

use FP\Privacy\Utils\Options;

/**
 * Handles retention cleanup scheduling and execution.
 */
class Cleanup {
/**
 * Log model.
 *
 * @var LogModel
 */
private $log_model;

/**
 * Options.
 *
 * @var Options
 */
private $options;

/**
 * Constructor.
 *
 * @param LogModel $log_model Log model.
 * @param Options  $options   Options.
 */
public function __construct( LogModel $log_model, Options $options ) {
$this->log_model = $log_model;
$this->options   = $options;
}

/**
 * Register hooks.
 *
 * @return void
 */
public function hooks() {
\add_action( 'fp_privacy_cleanup', array( $this, 'run' ) );
}

/**
 * Execute cleanup.
 *
 * @return void
 */
public function run() {
	try {
		$days = (int) $this->options->get( 'retention_days', \FP\Privacy\Shared\Constants::RETENTION_DAYS_DEFAULT );
		$days = $days > 0 ? $days : \FP\Privacy\Shared\Constants::RETENTION_DAYS_DEFAULT;

		$this->log_model->delete_older_than( $days );
	} catch ( \Throwable $e ) {
		if ( \function_exists( 'error_log' ) ) {
			\error_log( \sprintf( '[FP Privacy] Cleanup failed: %s', $e->getMessage() ) );
		}
	}
}
}
