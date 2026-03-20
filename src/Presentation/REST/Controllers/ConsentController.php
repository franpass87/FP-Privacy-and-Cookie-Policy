<?php
/**
 * REST consent controller.
 *
 * @package FP\Privacy\Presentation\REST\Controllers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\REST\Controllers;

use FP\Privacy\Application\Consent\RevokeConsentHandler;
use FP\Privacy\Frontend\ConsentState;
use FP\Privacy\REST\ConsentRestHandlerInterface;
use FP\Privacy\REST\RateLimiter;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST controller for consent endpoints (canonical implementation for fp-privacy/v1 consent routes).
 */
class ConsentController implements ConsentRestHandlerInterface {
	/**
	 * Consent state.
	 *
	 * @var ConsentState
	 */
	private $state;

	/**
	 * Revoke consent handler (optional).
	 *
	 * @var RevokeConsentHandler|null
	 */
	private $revoke_handler;

	/**
	 * Constructor.
	 *
	 * @param ConsentState              $state          Consent state.
	 * @param RevokeConsentHandler|null $revoke_handler Revoke handler.
	 */
	public function __construct( ConsentState $state, ?RevokeConsentHandler $revoke_handler = null ) {
		$this->state          = $state;
		$this->revoke_handler = $revoke_handler;
	}

	/**
	 * Handle POST /consent endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function post_consent( WP_REST_Request $request ) {
		$rate_check = RateLimiter::check( 'consent' );
		if ( \is_wp_error( $rate_check ) ) {
			return $rate_check;
		}

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

	/**
	 * Revoke consent via REST.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function revoke_consent( WP_REST_Request $request ) {
		$rate_check = RateLimiter::check( 'revoke' );
		if ( \is_wp_error( $rate_check ) ) {
			return $rate_check;
		}

		$consent_id = \sanitize_text_field( $request->get_param( 'consent_id' ) ?: '' );
		$lang       = \sanitize_text_field( $request->get_param( 'lang' ) ?: '' );

		if ( $this->revoke_handler ) {
			$result = $this->revoke_handler->handle( $consent_id ?: null, $lang ?: null );
		} else {
			$states = array(
				'analytics'  => false,
				'marketing'  => false,
				'functional' => false,
				'necessary'  => true,
			);
			$result = $this->state->save_event( 'consent_revoked', $states, $lang, $consent_id );
			if ( \is_wp_error( $result ) ) {
				return $result;
			}
			$result = array(
				'success'    => true,
				'consent_id' => $consent_id,
				'message'    => \__( 'Consent revoked successfully.', 'fp-privacy' ),
			);
		}

		if ( isset( $result['success'] ) && ! $result['success'] ) {
			return new WP_Error(
				$result['error'] ?? 'revoke_failed',
				$result['message'] ?? \__( 'Failed to revoke consent.', 'fp-privacy' ),
				array( 'status' => 400 )
			);
		}

		return new WP_REST_Response( $result, 200 );
	}
}














