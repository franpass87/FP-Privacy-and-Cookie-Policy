<?php
/**
 * CLI service provider.
 *
 * @package FP\Privacy\Providers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Providers;

use FP\Privacy\Core\ContainerInterface;
use FP\Privacy\Core\ServiceProviderInterface;
use FP\Privacy\Presentation\CLI\Commands\Commands;
use FP\Privacy\Consent\LogModel;
use FP\Privacy\Consent\Cleanup;
use FP\Privacy\Utils\Options;
use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Integrations\DetectorRegistry;

/**
 * CLI service provider - registers WP-CLI commands.
 */
class CLIServiceProvider implements ServiceProviderInterface {
	use ServiceProviderHelper;
	/**
	 * Register services in the container.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function register( ContainerInterface $container ): void {
		// Cleanup service.
		$container->singleton(
			Cleanup::class,
			function( ContainerInterface $c ) {
				$log_model = $c->get( LogModel::class );
				$provider = new self();
				$options = $provider->getOptions( $c );
				return new Cleanup( $log_model, $options );
			}
		);

		// CLI commands (new location in Presentation layer).
		$container->singleton(
			Commands::class,
			function( ContainerInterface $c ) {
				$log_model = $c->get( LogModel::class );
				$provider = new self();
				$options = $provider->getOptions( $c );
				$generator = $c->get( PolicyGenerator::class );
				$detector = $c->get( DetectorRegistry::class );
				$cleanup = $c->get( Cleanup::class );
				// Use new namespace if available, fallback to old.
				if ( class_exists( '\\FP\\Privacy\\Presentation\\CLI\\Commands\\Commands' ) ) {
					return new \FP\Privacy\Presentation\CLI\Commands\Commands( $log_model, $options, $generator, $detector, $cleanup );
				}
				// Fallback to old location during migration.
				return new \FP\Privacy\CLI\Commands( $log_model, $options, $generator, $detector, $cleanup );
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
		// Only register CLI commands if WP-CLI is available.
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		// Register WP-CLI command.
		$commands = $container->get( Commands::class );
		if ( class_exists( '\WP_CLI' ) ) {
			\WP_CLI::add_command( 'fp-privacy', $commands );
		}
	}
}
