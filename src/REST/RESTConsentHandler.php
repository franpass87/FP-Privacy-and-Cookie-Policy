<?php
/**
 * REST consent handler.
 *
 * @package FP\Privacy\REST
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\REST;

use FP\Privacy\Application\Consent\RevokeConsentHandler;
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
	 * Revoke consent handler.
	 *
	 * @var RevokeConsentHandler|null
	 */
	private $revoke_handler;

	/**
	 * Constructor.
	 *
	 * @param ConsentState              $state         Consent state.
	 * @param RevokeConsentHandler|null $revoke_handler Revoke handler (optional).
	 */
	public function __construct( ConsentState $state, ?RevokeConsentHandler $revoke_handler = null ) {
		$this->state          = $state;
		$this->revoke_handler = $revoke_handler;
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

	/**
	 * Revoke consent via REST.
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function revoke_consent( WP_REST_Request $request ) {
		// Rate limiting.
		$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$salt    = function_exists( '\fp_privacy_get_ip_salt' ) ? \fp_privacy_get_ip_salt() : 'fp-privacy-cookie-policy-salt';
		$limit   = 'fp_privacy_revoke_rate_' . hash( 'sha256', $ip . '|' . $salt );
		$attempt = (int) \get_transient( $limit );
		if ( $attempt > 10 ) {
			return new WP_Error( 'fp_privacy_rate_limited', \__( 'Too many requests. Please try again later.', 'fp-privacy' ), array( 'status' => 429 ) );
		}
		\set_transient( $limit, $attempt + 1, MINUTE_IN_SECONDS * 10 );

		$consent_id = \sanitize_text_field( $request->get_param( 'consent_id' ) ?: '' );
		$lang       = \sanitize_text_field( $request->get_param( 'lang' ) ?: '' );

		// Use revoke handler if available, otherwise fallback to state.
		if ( $this->revoke_handler ) {
			$result = $this->revoke_handler->handle( $consent_id ?: null, $lang ?: null );
		} else {
			// Fallback: log revocation event via state.
			$states = array(
				'analytics'  => false,
				'marketing'  => false,
				'functional' => false,
				'necessary'  => true, // Necessary cookies cannot be revoked.
			);
			$result = $this->state->save_event( 'consent_revoked', $states, $lang, $consent_id );
			if ( \is_wp_error( $result ) ) {
				return $result;
			}
			$result = array(
				'success'    => true,
				'consent_id' => $consent_id,
				'message'    => __( 'Consent revoked successfully.', 'fp-privacy' ),
			);
		}

		if ( isset( $result['success'] ) && ! $result['success'] ) {
			return new WP_Error(
				$result['error'] ?? 'revoke_failed',
				$result['message'] ?? __( 'Failed to revoke consent.', 'fp-privacy' ),
				array( 'status' => 400 )
			);
		}

		return new WP_REST_Response( $result, 200 );
	}
}












