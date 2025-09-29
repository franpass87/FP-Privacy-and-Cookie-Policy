<?php
/**
 * Cleanup handler.
 *
 * @package FP\Privacy\Consent
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
$days = (int) $this->options->get( 'retention_days', 180 );
$days = $days > 0 ? $days : 180;

$this->log_model->delete_older_than( $days );
}
}
