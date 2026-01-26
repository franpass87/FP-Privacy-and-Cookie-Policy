<?php
/**
 * REST consent controller.
 *
 * @package FP\Privacy\Presentation\REST\Controllers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\REST\Controllers;

use FP\Privacy\Application\Consent\LogConsentHandler;
use FP\Privacy\Frontend\ConsentState;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST controller for consent endpoints.
 * 
 * This is the new controller structure. The old RESTConsentHandler is kept for backward compatibility.
 */
class ConsentController {
	/**
	 * Consent state (legacy, will be replaced with Application handlers).
	 *
	 * @var ConsentState
	 */
	private $state;

	/**
	 * Log consent handler (new Application layer).
	 *
	 * @var LogConsentHandler
	 */
	private $log_handler;

	/**
	 * Constructor.
	 *
	 * @param ConsentState     $state      Consent state (legacy).
	 * @param LogConsentHandler $log_handler Log consent handler (new).
	 */
	public function __construct( ConsentState $state, LogConsentHandler $log_handler ) {
		$this->state       = $state;
		$this->log_handler = $log_handler;
	}

	/**
	 * Handle POST /consent endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function post_consent( WP_REST_Request $request ) {
		// Rate limiting.
		$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$salt    = function_exists( '\fp_privacy_get_ip_salt' ) ? \fp_privacy_get_ip_salt() : 'fp-privacy-cookie-policy-salt';
		$limit   = 'fp_privacy_rate_' . hash( 'sha256', $ip . '|' . $salt );
		$attempt = (int) \get_transient( $limit );
		if ( $attempt > 10 ) {
			return new WP_Error( 'fp_privacy_rate_limited', \__( 'Too many requests. Please try again later.', 'fp-privacy' ), array( 'status' => 429 ) );
		}
		\set_transient( $limit, $attempt + 1, MINUTE_IN_SECONDS * 10 );

		// Get request data.
		$event      = \sanitize_text_field( $request->get_param( 'event' ) );
		$states     = $request->get_param( 'states' );
		$lang       = \sanitize_text_field( $request->get_param( 'lang' ) );
		$consent_id = \sanitize_text_field( $request->get_param( 'consent_id' ) ?? '' );

		if ( ! \is_array( $states ) ) {
			$states = array();
		}

		// Use legacy ConsentState for now (manages cookies and state).
		// In future, this will be split into Application handlers.
		$result = $this->state->save_event( $event, $states, $lang, $consent_id );

		if ( \is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 200 );
	}
}














