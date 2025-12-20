<?php
/**
 * REST revision handler.
 *
 * @package FP\Privacy\REST
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\REST;

use FP\Privacy\Utils\Options;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles revision-related REST endpoints.
 */
class RESTRevisionHandler {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options handler.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Bump revision.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response
	 */
	public function bump_revision( WP_REST_Request $request ) {
		$this->options->bump_revision();
		$this->options->set( $this->options->all() );

		return new WP_REST_Response( array( 'revision' => $this->options->get( 'consent_revision' ) ), 200 );
	}
}















