<?php
/**
 * Core service provider.
 *
 * @package FP\Privacy\Providers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Providers;

use FP\Privacy\Core\ContainerInterface;
use FP\Privacy\Core\ServiceProviderInterface;
// Cache and Database are now in InfrastructureServiceProvider.
use FP\Privacy\Services\Logger\LoggerInterface;
use FP\Privacy\Services\Logger\Logger;
use FP\Privacy\Services\Logger\LogLevel;
use FP\Privacy\Services\Options\OptionsInterface;
use FP\Privacy\Services\Options\Options;
use FP\Privacy\Infrastructure\Options\OptionsAdapter;
use FP\Privacy\Utils\Options as LegacyOptions;
use FP\Privacy\Services\Sanitization\SanitizerInterface;
use FP\Privacy\Services\Sanitization\Sanitizer;
use FP\Privacy\Services\Validation\ValidatorInterface;
use FP\Privacy\Services\Validation\Validator;
// Database adapter moved to InfrastructureServiceProvider.
use FP\Privacy\Services\Security\IpSaltService;

/**
 * Core service provider - registers cross-cutting services.
 */
class CoreServiceProvider implements ServiceProviderInterface {
	use ServiceProviderHelper;
	/**
	 * Register services in the container.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	public function register( ContainerInterface $container ): void {
		// Logger service.
		$container->singleton(
			LoggerInterface::class,
			function() {
				$min_level = defined( 'WP_DEBUG' ) && WP_DEBUG ? LogLevel::DEBUG : LogLevel::ERROR;
				return new Logger( $min_level );
			}
		);

		// Options service - use adapter to bridge legacy Options singleton.
		// This is kept for backward compatibility during migration.
		// New code should use OptionsRepositoryInterface from InfrastructureServiceProvider.
		$container->singleton(
			OptionsInterface::class,
			function() {
				// For now, use adapter to maintain compatibility with legacy Options singleton.
				// In Phase 6, we'll fully migrate to new Options service.
				return new OptionsAdapter();
			}
		);

		// Note: Cache and Database are now registered in InfrastructureServiceProvider.
		// They are registered there to follow the proper provider order (Core -> Infrastructure -> Domain).

		// Validation service.
		$container->singleton(
			ValidatorInterface::class,
			function() {
				return new Validator();
			}
		);

		// Sanitization service.
		$container->singleton(
			SanitizerInterface::class,
			function() {
				return new Sanitizer();
			}
		);

		// IP salt service.
		$container->singleton(
			IpSaltService::class,
			function( ContainerInterface $c ) {
				$options = $c->get( OptionsInterface::class );
				return new IpSaltService( $options );
			}
		);

		// Legacy Options singleton - registered for backward compatibility.
		// New code should use OptionsInterface or OptionsRepositoryInterface.
		// This will be deprecated in a future version.
		$container->singleton(
			LegacyOptions::class,
			function() {
				return LegacyOptions::instance();
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
		try {
			// Register I18n hooks.
			$i18n = new \FP\Privacy\Utils\I18n();
			$i18n->load_textdomain();
			$i18n->hooks();

			// Force update banner texts translations.
			$options = self::resolveOptions( $container );
			if ( $options && method_exists( $options, 'force_update_banner_texts_translations' ) ) {
				try {
					$options->force_update_banner_texts_translations();
				} catch ( \Throwable $e ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( sprintf( 'FP Privacy: Error updating banner texts translations: %s', $e->getMessage() ) );
					}
				}
			}

			// Register plugin filters (migrated from PluginFilters class).
			$this->registerPluginFilters( $container );
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'FP Privacy: Error in CoreServiceProvider boot: %s', $e->getMessage() ) );
			}
		}
	}

	/**
	 * Register plugin filters (migrated from PluginFilters class).
	 *
	 * @param ContainerInterface $container Service container.
	 * @return void
	 */
	private function registerPluginFilters( ContainerInterface $container ): void {
		try {
			$options = self::resolveOptions( $container );
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( sprintf( 'FP Privacy: Error getting options in registerPluginFilters: %s', $e->getMessage() ) );
			}
			$options = null;
		}

		if ( ! $options ) {
			return;
		}

		// Enable WordPress privacy tools integration by default.
		\add_filter( 'fp_privacy_enable_privacy_tools', '__return_true', 10, 2 );

		// Wire GPC enablement to saved option.
		$options_ref = $options;
		\add_filter(
			'fp_privacy_enable_gpc',
			static function ( $enabled ) use ( $options_ref ) {
				$value = $options_ref ? (bool) $options_ref->get( 'gpc_enabled', false ) : false;
				return (bool) $value;
			},
			10,
			1
		);

		// Map email -> consent_ids using user meta recorded at consent time.
		\add_filter(
			'fp_privacy_consent_ids_for_email',
			static function ( $ids, $email ) {
				if ( ! \is_array( $ids ) ) {
					$ids = array();
				}

				if ( ! \is_email( $email ) || ! function_exists( '\get_user_by' ) ) {
					return $ids;
				}

				$user = \get_user_by( 'email', $email );

				if ( ! $user || ! isset( $user->ID ) ) {
					return $ids;
				}

				if ( function_exists( '\get_user_meta' ) ) {
					$stored = \get_user_meta( (int) $user->ID, 'fp_consent_ids', true );

					if ( \is_array( $stored ) ) {
						foreach ( $stored as $candidate ) {
							$candidate = \substr( (string) $candidate, 0, 64 );

							if ( '' !== $candidate ) {
								$ids[] = $candidate;
							}
						}
					}
				}

				return array_values( array_unique( $ids ) );
			},
			10,
			2
		);
	}
}

