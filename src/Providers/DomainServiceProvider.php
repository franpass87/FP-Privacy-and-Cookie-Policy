<?php
/**
 * Domain service provider.
 *
 * @package FP\Privacy\Providers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Providers;

use FP\Privacy\Core\ContainerInterface;
use FP\Privacy\Core\ServiceProviderInterface;
use FP\Privacy\Domain\Consent\ConsentRepositoryInterface;
use FP\Privacy\Domain\Consent\ConsentService;
use FP\Privacy\Domain\Policy\PolicyRepositoryInterface;
use FP\Privacy\Domain\Policy\PolicyService;
use FP\Privacy\Infrastructure\Options\OptionsRepositoryInterface;
use FP\Privacy\Infrastructure\Options\PolicyRepository;

/**
 * Domain service provider - registers domain services and business logic.
 * 
 * This provider will be populated as we migrate domain logic from existing classes.
 */
class DomainServiceProvider implements ServiceProviderInterface {
	/**
	 * Register services in the container.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function register( ContainerInterface $container ): void {
		// Consent domain service.
		$container->singleton(
			ConsentService::class,
			function( ContainerInterface $c ) {
				$repository = $c->get( ConsentRepositoryInterface::class );
				return new ConsentService( $repository );
			}
		);

		// Policy repository.
		$container->singleton(
			PolicyRepositoryInterface::class,
			function( ContainerInterface $c ) {
				$options = $c->get( OptionsRepositoryInterface::class );
				return new PolicyRepository( $options );
			}
		);

		// Policy domain service.
		$container->singleton(
			PolicyService::class,
			function( ContainerInterface $c ) {
				$repository = $c->get( PolicyRepositoryInterface::class );
				return new PolicyService( $repository );
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
		// Domain-specific initialization if needed.
	}
}

