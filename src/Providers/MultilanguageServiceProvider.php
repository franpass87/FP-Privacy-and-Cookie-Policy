<?php
/**
 * Multilanguage service provider.
 *
 * @package FP\Privacy\Providers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Providers;

use FP\Privacy\Core\ContainerInterface;
use FP\Privacy\Core\ServiceProviderInterface;
use FP\Privacy\MultilanguageCompatibility;
use FP\Privacy\Services\Options\OptionsInterface;

/**
 * Multilanguage service provider - handles multilanguage compatibility.
 */
class MultilanguageServiceProvider implements ServiceProviderInterface {
	use ServiceProviderHelper;
	/**
	 * Register services in the container.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function register( ContainerInterface $container ): void {
		// Multilanguage compatibility.
		$container->singleton(
			MultilanguageCompatibility::class,
			function( ContainerInterface $c ) {
				$provider = new self();
				$options = $provider->getOptions( $c );
				return new MultilanguageCompatibility( $options );
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
		// Setup multilanguage compatibility hooks.
		$multilanguage = $container->get( MultilanguageCompatibility::class );
		if ( method_exists( $multilanguage, 'setup' ) ) {
			$multilanguage->setup();
		}
	}
}
