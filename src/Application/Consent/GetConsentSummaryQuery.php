<?php
/**
 * Get consent summary query handler.
 *
 * @package FP\Privacy\Application\Consent
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Application\Consent;

use FP\Privacy\Consent\LogModel;
use FP\Privacy\Domain\Consent\ConsentRepositoryInterface;
use FP\Privacy\Services\Options\OptionsInterface;

/**
 * Query handler for retrieving consent summary data.
 */
class GetConsentSummaryQuery {
	/**
	 * Consent repository.
	 *
	 * @var ConsentRepositoryInterface
	 */
	private $repository;

	/**
	 * Log model (legacy, for backward compatibility).
	 *
	 * @var LogModel
	 */
	private $log_model;

	/**
	 * Options handler.
	 *
	 * @var OptionsInterface
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param ConsentRepositoryInterface $repository Consent repository.
	 * @param LogModel                   $log_model Log model (legacy).
	 * @param OptionsInterface           $options Options handler.
	 */
	public function __construct( ConsentRepositoryInterface $repository, LogModel $log_model, OptionsInterface $options ) {
		$this->repository = $repository;
		$this->log_model  = $log_model;
		$this->options    = $options;
	}

	/**
	 * Get consent summary.
	 *
	 * @return array<string, mixed> Summary data.
	 */
	public function handle(): array {
		// Use LogModel for now (has summary_last_30_days method).
		// In future, this will use repository directly.
		$summary = $this->log_model->summary_last_30_days();

		$total = array_sum( $summary );

		return array(
			'summary' => $summary,
			'total'   => $total,
			'period'  => '30_days',
		);
	}
}










