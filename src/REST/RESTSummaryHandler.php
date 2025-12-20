<?php
/**
 * REST summary handler.
 *
 * @package FP\Privacy\REST
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\REST;

use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Consent\LogModel;
use FP\Privacy\Utils\Options;
use WP_REST_Response;

/**
 * Handles summary REST endpoint.
 */
class RESTSummaryHandler {
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
	 * Policy generator.
	 *
	 * @var PolicyGenerator
	 */
	private $generator;

	/**
	 * Constructor.
	 *
	 * @param LogModel        $log_model Log model.
	 * @param Options         $options   Options handler.
	 * @param PolicyGenerator $generator Generator.
	 */
	public function __construct( LogModel $log_model, Options $options, PolicyGenerator $generator ) {
		$this->log_model = $log_model;
		$this->options   = $options;
		$this->generator = $generator;
	}

	/**
	 * Summary endpoint.
	 *
	 * @return WP_REST_Response
	 */
	public function get_summary() {
		$data = array(
			'summary'  => $this->log_model->summary_last_30_days(),
			'total'    => $this->log_model->count(),
			'options'  => $this->options->all(),
			'snapshot' => $this->generator->snapshot(),
		);

		return new WP_REST_Response( $data, 200 );
	}
}















