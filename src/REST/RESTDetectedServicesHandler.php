<?php
/**
 * REST: elenco servizi rilevati dal detector (banner tab Dettagli + polling).
 *
 * @package FP\Privacy\REST
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

declare( strict_types=1 );

namespace FP\Privacy\REST;

use FP\Privacy\Frontend\ConsentState;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Handler GET /fp-privacy/v1/detected-services
 */
final class RESTDetectedServicesHandler {
	/**
	 * Consent / detector state facade.
	 *
	 * @var ConsentState
	 */
	private ConsentState $state;

	/**
	 * @param ConsentState $state Consent state (espone payload servizi rilevati).
	 */
	public function __construct( ConsentState $state ) {
		$this->state = $state;
	}

	/**
	 * Restituisce i servizi con flag detected=true (stessa logica del banner).
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_detected_services( WP_REST_Request $request ) {
		if ( ! (bool) \apply_filters( 'fp_privacy_rest_detected_services_enabled', true ) ) {
			return new WP_Error(
				'fp_privacy_feature_disabled',
				\__( 'This endpoint is disabled.', 'fp-privacy' ),
				array( 'status' => 404 )
			);
		}

		$lang = $request->get_param( 'lang' );
		$lang = \is_string( $lang ) ? \sanitize_text_field( $lang ) : '';

		if ( '' === $lang ) {
			$lang = \function_exists( '\determine_locale' ) ? (string) \determine_locale() : 'en_US';
		}

		$services = $this->state->get_detected_services_payload( $lang );

		$response = \rest_ensure_response(
			array(
				'services'     => $services,
				'generated_at' => \time(),
			)
		);

		if ( $response instanceof WP_REST_Response ) {
			$response->set_headers( array( 'Cache-Control' => 'private, max-age=60' ) );
		}

		return $response;
	}
}
