<?php
/**
 * Data service provider.
 *
 * @package FP\Privacy\Providers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Providers;

use FP\Privacy\Core\ContainerInterface;
use FP\Privacy\Core\ServiceProviderInterface;
use FP\Privacy\Consent\LogModel;
use FP\Privacy\Consent\LogModelTable;
use FP\Privacy\Infrastructure\Database\ConsentTable;
use FP\Privacy\Consent\LogModelTable as LegacyLogModelTable;
use FP\Privacy\Domain\Consent\ConsentRepositoryInterface;
use FP\Privacy\Infrastructure\Database\ConsentRepository;
use FP\Privacy\Services\Database\DatabaseInterface;

/**
 * Data service provider - registers data layer services.
 */
class DataServiceProvider implements ServiceProviderInterface {
	use ServiceProviderHelper;
	/**
	 * Register services in the container.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function register( ContainerInterface $container ): void {
		// Log model table (new Infrastructure location, with fallback for compatibility).
		$container->singleton(
			LogModelTable::class,
			function( ContainerInterface $c ) {
				$database = $c->get( DatabaseInterface::class );
				global $wpdb;
				$table_name = $wpdb->prefix . 'fp_privacy_consent_log';
				// Use new ConsentTable if available, fallback to old LogModelTable.
				if ( class_exists( '\\FP\\Privacy\\Infrastructure\\Database\\ConsentTable' ) ) {
					return new \FP\Privacy\Infrastructure\Database\ConsentTable( $table_name, $database );
				}
				return new LegacyLogModelTable( $table_name, $database );
			}
		);

		// Log model.
		$container->singleton(
			LogModel::class,
			function( ContainerInterface $c ) {
				$table = $c->get( LogModelTable::class );
				$database = $c->get( DatabaseInterface::class );
				return new LogModel( $table, $database );
			}
		);

		// Consent repository (new interface-based approach).
		$container->singleton(
			ConsentRepositoryInterface::class,
			function( ContainerInterface $c ) {
				$log_model = $c->get( LogModel::class );
				return new ConsentRepository( $log_model );
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
		// Ensure database tables exist.
		$table = $container->get( LogModelTable::class );
		if ( method_exists( $table, 'ensure_table_exists' ) ) {
			$table->ensure_table_exists();
		}

		// Register ExporterEraser hooks.
		$log_model = $container->get( LogModel::class );
		$options = self::resolveOptions( $container );
		$exporter_eraser = new \FP\Privacy\Consent\ExporterEraser( $log_model, $options );
		if ( method_exists( $exporter_eraser, 'hooks' ) ) {
			$exporter_eraser->hooks();
		}

		// Register Cleanup hooks.
		$cleanup = new \FP\Privacy\Consent\Cleanup( $log_model, $options );
		if ( method_exists( $cleanup, 'hooks' ) ) {
			$cleanup->hooks();
		}
	}
}





