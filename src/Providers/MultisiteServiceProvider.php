<?php
/**
 * Multisite service provider.
 *
 * @package FP\Privacy\Providers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Providers;

use FP\Privacy\Core\ContainerInterface;
use FP\Privacy\Core\ServiceProviderInterface;
use FP\Privacy\Infrastructure\Multisite\MultisiteManager;
use FP\Privacy\Infrastructure\Multisite\MultisiteManagerInterface;
use FP\Privacy\Services\Options\OptionsInterface;
use FP\Privacy\Consent\LogModel;

/**
 * Multisite service provider - handles multisite functionality.
 */
class MultisiteServiceProvider implements ServiceProviderInterface {
	/**
	 * Register services in the container.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function register( ContainerInterface $container ): void {
		// Multisite manager (new location).
		$container->singleton(
			MultisiteManagerInterface::class,
			function( ContainerInterface $c ) {
				// Get legacy Options from container if available, otherwise use singleton.
				$legacy_options = $c->has( \FP\Privacy\Utils\Options::class )
					? $c->get( \FP\Privacy\Utils\Options::class )
					: \FP\Privacy\Utils\Options::instance();
				$log_model = $c->has( LogModel::class ) ? $c->get( LogModel::class ) : null;
				return new MultisiteManager( $legacy_options, $log_model );
			}
		);

		// Alias for backward compatibility.
		$container->alias( MultisiteManagerInterface::class, MultisiteManager::class );
		$container->alias( MultisiteManagerInterface::class, \FP\Privacy\MultisiteManager::class );
	}

	/**
	 * Boot services after all providers are registered.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function boot( ContainerInterface $container ): void {
		// Multisite hooks are handled by Kernel during activation/deactivation.
	}
}
