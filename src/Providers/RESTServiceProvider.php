<?php
/**
 * REST service provider.
 *
 * @package FP\Privacy\Providers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Providers;

use FP\Privacy\Core\ContainerInterface;
use FP\Privacy\Core\ServiceProviderInterface;
use FP\Privacy\REST\Controller;
use FP\Privacy\Presentation\REST\Controllers\ConsentController;
use FP\Privacy\Presentation\REST\Controllers\SettingsController;
use FP\Privacy\Presentation\REST\Controllers\SummaryController;
use FP\Privacy\Application\Consent\GetConsentSummaryQuery;
use FP\Privacy\Application\Consent\LogConsentHandler;
use FP\Privacy\Application\Settings\GetSettingsHandler;
use FP\Privacy\Application\Settings\UpdateSettingsHandler;
use FP\Privacy\Frontend\ConsentState;
use FP\Privacy\Infrastructure\Options\OptionsRepositoryInterface;
use FP\Privacy\Utils\Options;
use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Consent\LogModel;

/**
 * REST service provider - registers REST API services.
 */
class RESTServiceProvider implements ServiceProviderInterface {
	use ServiceProviderHelper;
	/**
	 * Register services in the container.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function register( ContainerInterface $container ): void {
		// REST controller (legacy, for backward compatibility).
		$container->singleton(
			Controller::class,
			function( ContainerInterface $c ) {
				$state = $c->get( ConsentState::class );
				$provider = new self();
				$options = $provider->getOptions( $c );
				$generator = $c->get( \FP\Privacy\Admin\PolicyGenerator::class );
				$log_model = $c->get( LogModel::class );
				return new Controller( $state, $options, $generator, $log_model );
			}
		);

		// New Presentation layer controllers.
		$container->singleton(
			ConsentController::class,
			function( ContainerInterface $c ) {
				$state = $c->get( ConsentState::class );
				$log_handler = $c->get( LogConsentHandler::class );
				return new ConsentController( $state, $log_handler );
			}
		);

		$container->singleton(
			SummaryController::class,
			function( ContainerInterface $c ) {
				$summary_query = $c->get( GetConsentSummaryQuery::class );
				$options = $c->get( OptionsRepositoryInterface::class );
				$generator = $c->get( PolicyGenerator::class );
				return new SummaryController( $summary_query, $options, $generator );
			}
		);

		// Settings controller.
		$container->singleton(
			SettingsController::class,
			function( ContainerInterface $c ) {
				$get_handler = $c->get( GetSettingsHandler::class );
				$update_handler = $c->get( UpdateSettingsHandler::class );
				return new SettingsController( $get_handler, $update_handler );
			}
		);
	}

	/**
	 * Boot services after all providers are registered.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function boot( ContainerInterface $container ): void {
		// Register REST routes via old Controller (backward compatibility).
		$controller = $container->get( Controller::class );
		if ( method_exists( $controller, 'hooks' ) ) {
			$controller->hooks();
		}

		// Register new Presentation layer routes.
		add_action(
			'rest_api_init',
			function() use ( $container ) {
				// Register settings routes if controller is available.
				if ( $container->has( SettingsController::class ) ) {
					$settings_controller = $container->get( SettingsController::class );
					
					register_rest_route(
						'fp-privacy/v1',
						'/settings',
						array(
							array(
								'methods'             => \WP_REST_Server::READABLE,
								'callback'            => array( $settings_controller, 'get_settings' ),
								'permission_callback' => function() {
									return \current_user_can( 'manage_options' );
								},
							),
							array(
								'methods'             => \WP_REST_Server::EDITABLE,
								'callback'            => array( $settings_controller, 'update_settings' ),
								'permission_callback' => function() {
									return \current_user_can( 'manage_options' );
								},
							),
						)
					);
				}
			}
		);
	}
}
