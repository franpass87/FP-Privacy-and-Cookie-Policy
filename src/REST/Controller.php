<?php
/**
 * REST controller.
 *
 * @package FP\Privacy\REST
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\REST;

use FP\Privacy\Consent\LogModel;
use FP\Privacy\Frontend\ConsentState;
use FP\Privacy\Utils\Options;
use FP\Privacy\Admin\PolicyGenerator;

/**
 * Registers REST API routes.
 */
class Controller {
	/**
	 * Route registrar.
	 *
	 * @var RESTRouteRegistrar
	 */
	private $route_registrar;

	/**
	 * Constructor.
	 *
	 * @param ConsentState    $state     State handler.
	 * @param Options         $options   Options handler.
	 * @param PolicyGenerator $generator Generator.
	 * @param LogModel        $log_model Log model.
	 */
	public function __construct( ConsentState $state, Options $options, PolicyGenerator $generator, LogModel $log_model ) {
		$permission_checker = new RESTPermissionChecker();
		// Use old handlers for now (backward compatibility).
		// New controllers will be registered separately via RESTServiceProvider.
		$summary_handler    = new RESTSummaryHandler( $log_model, $options, $generator );
		$consent_handler    = new RESTConsentHandler( $state );
		$revision_handler   = new RESTRevisionHandler( $options );
		$this->route_registrar = new RESTRouteRegistrar( $summary_handler, $consent_handler, $revision_handler, $permission_checker );
	}

	/**
	 * Hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		\add_action( 'rest_api_init', array( $this->route_registrar, 'register_routes' ) );
	}
}
