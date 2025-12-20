<?php
/**
 * REST summary controller.
 *
 * @package FP\Privacy\Presentation\REST\Controllers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\REST\Controllers;

use FP\Privacy\Application\Consent\GetConsentSummaryQuery;
use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Infrastructure\Options\OptionsRepositoryInterface;
use WP_REST_Response;

/**
 * REST controller for summary endpoint.
 * 
 * This is the new controller structure. The old RESTSummaryHandler is kept for backward compatibility.
 */
class SummaryController {
	/**
	 * Get consent summary query handler (new Application layer).
	 *
	 * @var GetConsentSummaryQuery
	 */
	private $summary_query;

	/**
	 * Options repository.
	 *
	 * @var OptionsRepositoryInterface
	 */
	private $options;

	/**
	 * Policy generator.
	 *
	 * @var PolicyGenerator
	 */
	private $generator;

	/**
	 * Constructor.
	 *
	 * @param GetConsentSummaryQuery    $summary_query Summary query handler.
	 * @param OptionsRepositoryInterface $options Options repository.
	 * @param PolicyGenerator            $generator Policy generator.
	 */
	public function __construct(
		GetConsentSummaryQuery $summary_query,
		OptionsRepositoryInterface $options,
		PolicyGenerator $generator
	) {
		$this->summary_query = $summary_query;
		$this->options       = $options;
		$this->generator     = $generator;
	}

	/**
	 * Summary endpoint.
	 *
	 * @return WP_REST_Response
	 */
	public function get_summary(): WP_REST_Response {
		$summary_data = $this->summary_query->handle();

		$data = array(
			'summary'  => $summary_data['summary'],
			'total'    => $summary_data['total'],
			'period'   => $summary_data['period'],
			'options'  => $this->options->all(),
			'snapshot' => $this->generator->snapshot(),
		);

		return new WP_REST_Response( $data, 200 );
	}
}















