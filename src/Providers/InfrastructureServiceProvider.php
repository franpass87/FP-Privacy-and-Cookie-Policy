<?php
/**
 * Infrastructure service provider.
 *
 * @package FP\Privacy\Providers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Providers;

use FP\Privacy\Core\ContainerInterface;
use FP\Privacy\Core\ServiceProviderInterface;
use FP\Privacy\Infrastructure\Cache\CacheFactory;
use FP\Privacy\Infrastructure\Database\WpdbAdapter;
use FP\Privacy\Infrastructure\Http\RequestFactory;
use FP\Privacy\Infrastructure\Options\OptionsFactory;
use FP\Privacy\Infrastructure\Options\OptionsRepositoryInterface;
use FP\Privacy\Services\Cache\CacheInterface;
use FP\Privacy\Services\Database\DatabaseInterface;
use FP\Privacy\Infrastructure\Http\HttpClientInterface;

/**
 * Infrastructure service provider - registers WordPress adapters.
 */
class InfrastructureServiceProvider implements ServiceProviderInterface {
	/**
	 * Register services in the container.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function register( ContainerInterface $container ): void {
		// Database adapter.
		$container->singleton(
			DatabaseInterface::class,
			function () {
				return new WpdbAdapter();
			}
		);

		// Options repository - use adapter during migration, will switch to WpOptionsAdapter later.
		$container->singleton(
			OptionsRepositoryInterface::class,
			function () {
				// During migration, use adapter that wraps singleton.
				// In Phase 3, this will switch to WpOptionsAdapter.
				return OptionsFactory::create( 'legacy' );
			}
		);

		// Cache service - use factory to auto-detect best implementation.
		$container->singleton(
			CacheInterface::class,
			function () {
				return CacheFactory::create();
			}
		);

		// HTTP client.
		$container->singleton(
			HttpClientInterface::class,
			function () {
				return RequestFactory::create();
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
		// Infrastructure-specific initialization if needed.
	}
}










