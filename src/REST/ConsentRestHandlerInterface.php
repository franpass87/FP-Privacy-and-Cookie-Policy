<?php
/**
 * Contract for fp-privacy/v1 consent REST callbacks.
 *
 * @package FP\Privacy\REST
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\REST;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Single entry for POST /consent and POST /consent/revoke route handlers.
 */
interface ConsentRestHandlerInterface {
	/**
	 * Persist a consent event from REST.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function post_consent( WP_REST_Request $request );

	/**
	 * Revoke consent from REST.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function revoke_consent( WP_REST_Request $request );
}
