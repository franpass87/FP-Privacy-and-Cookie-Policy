<?php
/**
 * REST consent handler.
 *
 * @package FP\Privacy\REST
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\REST;

use FP\Privacy\Frontend\ConsentState;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handles consent-related REST endpoints.
 */
class RESTConsentHandler {
	/**
	 * Consent state.
	 *
	 * @var ConsentState
	 */
	private $state;

	/**
	 * Constructor.
	 *
	 * @param ConsentState $state Consent state.
	 */
	public function __construct( ConsentState $state ) {
		$this->state = $state;
	}

	/**
	 * Save consent via REST.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function post_consent( WP_REST_Request $request ) {
		// Nonce validation is now handled by check_consent_permission
		// This allows same-origin requests without nonce for better compatibility

		$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : ''; // for rate limiting only.
		$salt    = function_exists( '\fp_privacy_get_ip_salt' ) ? \fp_privacy_get_ip_salt() : 'fp-privacy-cookie-policy-salt';
		$limit   = 'fp_privacy_rate_' . hash( 'sha256', $ip . '|' . $salt );
		$attempt = (int) \get_transient( $limit );
		if ( $attempt > 10 ) {
			return new WP_Error( 'fp_privacy_rate_limited', \__( 'Too many requests. Please try again later.', 'fp-privacy' ), array( 'status' => 429 ) );
		}
		\set_transient( $limit, $attempt + 1, MINUTE_IN_SECONDS * 10 );

		$event  = \sanitize_text_field( $request->get_param( 'event' ) );
		$states = $request->get_param( 'states' );
		$lang   = \sanitize_text_field( $request->get_param( 'lang' ) );
		$consent_id = $request->get_param( 'consent_id' );
		$consent_id = $consent_id ? \sanitize_text_field( $consent_id ) : '';

		if ( ! \is_array( $states ) ) {
			$states = array();
		}

		$result = $this->state->save_event( $event, $states, $lang, $consent_id );

		if ( \is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}
}












