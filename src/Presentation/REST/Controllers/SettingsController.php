<?php
/**
 * REST settings controller.
 *
 * @package FP\Privacy\Presentation\REST\Controllers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\REST\Controllers;

use FP\Privacy\Application\Settings\GetSettingsHandler;
use FP\Privacy\Application\Settings\UpdateSettingsHandler;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * REST controller for settings endpoints.
 */
class SettingsController {
	/**
	 * Get settings handler.
	 *
	 * @var GetSettingsHandler
	 */
	private $get_handler;

	/**
	 * Update settings handler.
	 *
	 * @var UpdateSettingsHandler
	 */
	private $update_handler;

	/**
	 * Constructor.
	 *
	 * @param GetSettingsHandler    $get_handler Get settings handler.
	 * @param UpdateSettingsHandler $update_handler Update settings handler.
	 */
	public function __construct(
		GetSettingsHandler $get_handler,
		UpdateSettingsHandler $update_handler
	) {
		$this->get_handler    = $get_handler;
		$this->update_handler = $update_handler;
	}

	/**
	 * Get settings endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function get_settings( WP_REST_Request $request ): WP_REST_Response {
		$settings = $this->get_handler->all();
		return new WP_REST_Response( $settings, 200 );
	}

	/**
	 * Update settings endpoint.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response or error.
	 */
	public function update_settings( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$settings = $request->get_param( 'settings' );

		if ( ! is_array( $settings ) ) {
			return new WP_Error(
				'fp_privacy_invalid_settings',
				__( 'Settings must be an array.', 'fp-privacy' ),
				array( 'status' => 400 )
			);
		}

		$result = $this->update_handler->handle( $settings );

		if ( ! $result ) {
			return new WP_Error(
				'fp_privacy_update_failed',
				__( 'Failed to update settings.', 'fp-privacy' ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Settings updated successfully.', 'fp-privacy' ),
			),
			200
		);
	}
}













