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
	 * Consent handler.
	 *
	 * @var RESTConsentHandler
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
	 * Constructor.
	 *
	 * @param RESTSummaryHandler    $summary_handler    Summary handler.
	 * @param RESTConsentHandler    $consent_handler    Consent handler.
	 * @param RESTRevisionHandler   $revision_handler   Revision handler.
	 * @param RESTPermissionChecker $permission_checker Permission checker.
	 */
	public function __construct( RESTSummaryHandler $summary_handler, RESTConsentHandler $consent_handler, RESTRevisionHandler $revision_handler, RESTPermissionChecker $permission_checker ) {
		$this->summary_handler    = $summary_handler;
		$this->consent_handler     = $consent_handler;
		$this->revision_handler    = $revision_handler;
		$this->permission_checker   = $permission_checker;
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
	}
}















