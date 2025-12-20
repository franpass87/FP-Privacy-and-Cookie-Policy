<?php
/**
 * Plugin kernel - handles bootstrap and lifecycle.
 *
 * @package FP\Privacy\Core
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Core;

/**
 * Plugin kernel for bootstrapping and lifecycle management.
 */
class Kernel {
	/**
	 * Service container.
	 *
	 * @var ContainerInterface
	 */
	private $container;

	/**
	 * Service providers.
	 *
	 * @var array<int, ServiceProviderInterface>
	 */
	private $providers = array();

	/**
	 * Whether kernel has been booted.
	 *
	 * @var bool
	 */
	private $booted = false;

	/**
	 * Kernel instance.
	 *
	 * @var Kernel|null
	 */
	private static $instance = null;

	/**
	 * Get kernel instance.
	 *
	 * @return Kernel Kernel instance.
	 */
	public static function make(): Kernel {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->container = new Container();
		$this->registerProviders();
	}

	/**
	 * Register all service providers.
	 *
	 * @return void
	 */
	private function registerProviders(): void {
		$providers = array(
			// Phase 1: Core services (Logger, Cache, Options, Database, Validation, Sanitization).
			// Always first - all other providers depend on these.
			\FP\Privacy\Providers\CoreServiceProvider::class,
			
			// Phase 1: Infrastructure adapters (Database, Options, Cache, HTTP).
			// After Core, before Domain.
			\FP\Privacy\Providers\InfrastructureServiceProvider::class,
			
			// Phase 2: Data layer (repositories, models).
			// After Infrastructure.
			\FP\Privacy\Providers\DataServiceProvider::class,
			
			// Phase 4: Domain layer (business logic).
			// After Infrastructure, before Application.
			\FP\Privacy\Providers\DomainServiceProvider::class,
			
			// Phase 3: Application layer (use case handlers).
			// After Domain.
			\FP\Privacy\Providers\ApplicationServiceProvider::class,
			
			// Presentation layer providers (after Application).
			\FP\Privacy\Providers\AdminServiceProvider::class,
			\FP\Privacy\Providers\FrontendServiceProvider::class,
			\FP\Privacy\Providers\RESTServiceProvider::class,
			
			// Integration providers.
			\FP\Privacy\Providers\IntegrationServiceProvider::class,
			
			// Conditional providers (after Core).
			// MultisiteServiceProvider - conditional, after Core.
			// MultilanguageServiceProvider - after Core.
		);
		
		// Add conditional providers.
		if ( is_multisite() ) {
			$providers[] = \FP\Privacy\Providers\MultisiteServiceProvider::class;
		}
		
		$providers[] = \FP\Privacy\Providers\MultilanguageServiceProvider::class;

		// Only register CLI provider if WP-CLI is available.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$providers[] = \FP\Privacy\Providers\CLIServiceProvider::class;
		}

		foreach ( $providers as $provider_class ) {
			if ( class_exists( $provider_class ) ) {
				$provider = new $provider_class();
				$this->providers[] = $provider;
				$provider->register( $this->container );
			}
		}
	}

	/**
	 * Boot the kernel.
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		// Boot all providers.
		foreach ( $this->providers as $provider ) {
			$provider->boot( $this->container );
		}

		$this->booted = true;
	}

	/**
	 * Handle plugin activation.
	 *
	 * @param bool $network_wide Whether network-wide activation.
	 * @return void
	 */
	public function activate( bool $network_wide = false ): void {
		// Ensure container is ready.
		if ( ! $this->booted ) {
			$this->boot();
		}

		// Get multisite manager if available (try new interface first, then old class).
		$multisite = null;
		if ( $this->container->has( \FP\Privacy\Infrastructure\Multisite\MultisiteManagerInterface::class ) ) {
			$multisite = $this->container->get( \FP\Privacy\Infrastructure\Multisite\MultisiteManagerInterface::class );
		} elseif ( $this->container->has( \FP\Privacy\MultisiteManager::class ) ) {
			$multisite = $this->container->get( \FP\Privacy\MultisiteManager::class );
		}

		if ( $multisite && method_exists( $multisite, 'activate' ) ) {
			$multisite->activate( $network_wide );
		} else {
			// Fallback to old activation method.
			\FP\Privacy\Plugin::activate( $network_wide );
		}
	}

	/**
	 * Handle plugin deactivation.
	 *
	 * @return void
	 */
	public function deactivate(): void {
		// Get multisite manager if available (try new interface first, then old class).
		$multisite = null;
		if ( $this->container->has( \FP\Privacy\Infrastructure\Multisite\MultisiteManagerInterface::class ) ) {
			$multisite = $this->container->get( \FP\Privacy\Infrastructure\Multisite\MultisiteManagerInterface::class );
		} elseif ( $this->container->has( \FP\Privacy\MultisiteManager::class ) ) {
			$multisite = $this->container->get( \FP\Privacy\MultisiteManager::class );
		}

		if ( $multisite && method_exists( $multisite, 'deactivate' ) ) {
			$multisite->deactivate();
		} else {
			// Fallback to old deactivation method.
			\FP\Privacy\Plugin::deactivate();
		}
	}

	/**
	 * Provision a new site in multisite.
	 *
	 * @param int $blog_id Blog ID.
	 * @return void
	 */
	public function provisionSite( int $blog_id ): void {
		if ( ! is_multisite() ) {
			return;
		}

		// Ensure container is ready.
		if ( ! $this->booted ) {
			$this->boot();
		}

		// Get multisite manager if available (try new interface first, then old class).
		$multisite = null;
		if ( $this->container->has( \FP\Privacy\Infrastructure\Multisite\MultisiteManagerInterface::class ) ) {
			$multisite = $this->container->get( \FP\Privacy\Infrastructure\Multisite\MultisiteManagerInterface::class );
		} elseif ( $this->container->has( \FP\Privacy\MultisiteManager::class ) ) {
			$multisite = $this->container->get( \FP\Privacy\MultisiteManager::class );
		}

		if ( $multisite && method_exists( $multisite, 'provision_new_site' ) ) {
			$multisite->provision_new_site( $blog_id );
		} else {
			// Fallback to old method.
			$plugin = \FP\Privacy\Plugin::instance();
			if ( method_exists( $plugin, 'provision_new_site' ) ) {
				$plugin->provision_new_site( $blog_id );
			}
		}
	}

	/**
	 * Get the service container.
	 *
	 * @return ContainerInterface Container instance.
	 */
	public function getContainer(): ContainerInterface {
		return $this->container;
	}
}






