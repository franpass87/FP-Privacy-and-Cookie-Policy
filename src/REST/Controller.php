<?php
/**
 * REST controller.
 *
 * @package FP\Privacy\REST
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\REST;

use FP\Privacy\Application\Consent\RevokeConsentHandler;
use FP\Privacy\Consent\LogModel;
use FP\Privacy\Core\ContainerInterface;
use FP\Privacy\Frontend\ConsentState;
use FP\Privacy\Presentation\REST\Controllers\ConsentController;
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
	 * @param object|null     $container Optional service container for dependency injection.
	 */
	public function __construct( ConsentState $state, Options $options, PolicyGenerator $generator, LogModel $log_model, $container = null ) {
		$permission_checker = new RESTPermissionChecker();
		$summary_handler    = new RESTSummaryHandler( $log_model, $options, $generator );

		if ( $container && $container->has( ConsentController::class ) ) {
			$consent_handler = $container->get( ConsentController::class );
		} else {
			$revoke_handler = null;
			if ( $container && $container->has( RevokeConsentHandler::class ) ) {
				$revoke_handler = $container->get( RevokeConsentHandler::class );
			}
			$consent_handler = new RESTConsentHandler( $state, $revoke_handler );
		}

		$revision_handler       = new RESTRevisionHandler( $options );
		$detected_handler       = new RESTDetectedServicesHandler( $state );
		$this->route_registrar = new RESTRouteRegistrar( $summary_handler, $consent_handler, $revision_handler, $permission_checker, $detected_handler );
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
