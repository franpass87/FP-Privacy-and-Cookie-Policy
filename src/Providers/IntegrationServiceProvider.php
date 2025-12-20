<?php
/**
 * Integration service provider.
 *
 * @package FP\Privacy\Providers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Providers;

use FP\Privacy\Core\ContainerInterface;
use FP\Privacy\Core\ServiceProviderInterface;
use FP\Privacy\Integrations\ConsentMode;
use FP\Privacy\Integrations\DetectorRegistry;
use FP\Privacy\Domain\Services\ServiceRegistry;
use FP\Privacy\Integrations\ServiceRegistry as LegacyServiceRegistry;
use FP\Privacy\Services\Options\OptionsInterface;

/**
 * Integration service provider - registers integration services.
 */
class IntegrationServiceProvider implements ServiceProviderInterface {
	use ServiceProviderHelper;
	/**
	 * Register services in the container.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function register( ContainerInterface $container ): void {
		// Service registry (new Domain location, with fallback for compatibility).
		$container->singleton(
			ServiceRegistry::class,
			function() {
				// Use new Domain\Services\ServiceRegistry if available.
				if ( class_exists( '\\FP\\Privacy\\Domain\\Services\\ServiceRegistry' ) ) {
					return new \FP\Privacy\Domain\Services\ServiceRegistry();
				}
				// Fallback to old location during migration.
				return new LegacyServiceRegistry();
			}
		);

		// Detector registry.
		$container->singleton(
			DetectorRegistry::class,
			function( ContainerInterface $c ) {
				return new DetectorRegistry();
			}
		);

		// Consent mode.
		$container->singleton(
			ConsentMode::class,
			function( ContainerInterface $c ) {
				$provider = new self();
				$options = $provider->getOptions( $c );
				return new ConsentMode( $options );
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
		// Register consent mode hooks.
		$consent_mode = $container->get( ConsentMode::class );
		if ( method_exists( $consent_mode, 'hooks' ) ) {
			$consent_mode->hooks();
		}
	}
}
