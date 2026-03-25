<?php
/**
 * REST route registrar.
 *
 * @package FP\Privacy\REST
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\REST;

/**
 * Handles REST route registration.
 */
class RESTRouteRegistrar {
	/**
	 * Summary handler.
	 *
	 * @var RESTSummaryHandler
	 */
	private $summary_handler;

	/**
	 * Consent handler (Presentation controller or legacy handler).
	 *
	 * @var ConsentRestHandlerInterface
	 */
	private $consent_handler;

	/**
	 * Revision handler.
	 *
	 * @var RESTRevisionHandler
	 */
	private $revision_handler;

	/**
	 * Permission checker.
	 *
	 * @var RESTPermissionChecker
	 */
	private $permission_checker;

	/**
	 * Detected services (banner / polling).
	 *
	 * @var RESTDetectedServicesHandler
	 */
	private $detected_services_handler;

	/**
	 * Constructor.
	 *
	 * @param RESTSummaryHandler           $summary_handler           Summary handler.
	 * @param ConsentRestHandlerInterface  $consent_handler           Consent REST handler.
	 * @param RESTRevisionHandler          $revision_handler          Revision handler.
	 * @param RESTPermissionChecker        $permission_checker        Permission checker.
	 * @param RESTDetectedServicesHandler  $detected_services_handler Servizi rilevati (GET).
	 */
	public function __construct( RESTSummaryHandler $summary_handler, ConsentRestHandlerInterface $consent_handler, RESTRevisionHandler $revision_handler, RESTPermissionChecker $permission_checker, RESTDetectedServicesHandler $detected_services_handler ) {
		$this->summary_handler           = $summary_handler;
		$this->consent_handler           = $consent_handler;
		$this->revision_handler          = $revision_handler;
		$this->permission_checker        = $permission_checker;
		$this->detected_services_handler = $detected_services_handler;
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		\register_rest_route(
			'fp-privacy/v1',
			'/consent/summary',
			array(
				'permission_callback' => function () {
					return \current_user_can( 'manage_options' );
				},
				'methods'             => 'GET',
				'callback'            => array( $this->summary_handler, 'get_summary' ),
			)
		);

		\register_rest_route(
			'fp-privacy/v1',
			'/consent',
			array(
				'permission_callback' => array( $this->permission_checker, 'check_consent_permission' ),
				'methods'             => 'POST',
				'callback'            => array( $this->consent_handler, 'post_consent' ),
			)
		);

		\register_rest_route(
			'fp-privacy/v1',
			'/consent/revoke',
			array(
				'permission_callback' => array( $this->permission_checker, 'check_consent_permission' ),
				'methods'             => 'POST',
				'callback'            => array( $this->consent_handler, 'revoke_consent' ),
			)
		);

		\register_rest_route(
			'fp-privacy/v1',
			'/revision/bump',
			array(
				'permission_callback' => function () {
					return \current_user_can( 'manage_options' );
				},
				'methods'             => 'POST',
				'callback'            => array( $this->revision_handler, 'bump_revision' ),
			)
		);

		\register_rest_route(
			'fp-privacy/v1',
			'/detected-services',
			array(
				'methods'             => 'GET',
				'permission_callback' => function () {
					return (bool) \apply_filters( 'fp_privacy_rest_detected_services_permission', true );
				},
				'callback'            => array( $this->detected_services_handler, 'get_detected_services' ),
				'args'                => array(
					'lang' => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}
}















