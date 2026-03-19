<?php
/**
 * Get consent summary query handler.
 *
 * @package FP\Privacy\Application\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

declare(strict_types=1);

namespace FP\Privacy\Application\Consent;

use FP\Privacy\Consent\LogModel;

/**
 * Query handler for retrieving consent summary data.
 */
class GetConsentSummaryQuery {
	/**
	 * Log model (summary aggregation until repository exposes equivalent API).
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
	 * Get consent summary.
	 *
	 * @return array<string, mixed> Summary data.
	 */
	public function handle(): array {
		$summary = $this->log_model->summary_last_30_days();

		if ( ! is_array( $summary ) ) {
			$summary = array();
		}

		$total = array_sum( $summary );

		return array(
			'summary' => $summary,
			'total'   => $total,
			'period'  => '30_days',
		);
	}
}
