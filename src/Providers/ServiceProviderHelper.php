<?php
/**
 * Helper trait for service providers to get Options instance.
 *
 * @package FP\Privacy\Providers
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Providers;

use FP\Privacy\Core\ContainerInterface;
use FP\Privacy\Services\Options\OptionsInterface;
use FP\Privacy\Infrastructure\Options\OptionsAdapter;
use FP\Privacy\Utils\Options as LegacyOptions;

/**
 * Helper trait for service providers.
 */
trait ServiceProviderHelper {
	/**
	 * Get Options instance from container or fallback to singleton.
	 *
	 * This is a static method to avoid creating unnecessary instances.
	 *
	 * @param ContainerInterface $container Service container.
	 * @return LegacyOptions Options instance.
	 */
	protected static function resolveOptions( ContainerInterface $container ): LegacyOptions {
		// Try to get from container first.
		if ( $container->has( OptionsInterface::class ) ) {
			$options = $container->get( OptionsInterface::class );
			// If it's an adapter, get the legacy instance.
			if ( $options instanceof OptionsAdapter ) {
				return $options->getLegacy();
			}
		}

		// Fallback to singleton for backward compatibility.
		// Try to get from container first, then fallback to singleton.
		if ( class_exists( '\\FP\\Privacy\\Core\\Kernel' ) ) {
			try {
				$kernel = \FP\Privacy\Core\Kernel::make();
				$fallback_container = $kernel->getContainer();
				if ( $fallback_container->has( LegacyOptions::class ) ) {
					return $fallback_container->get( LegacyOptions::class );
				}
			} catch ( \Throwable $e ) {
				// Fall through to singleton.
			}
		}
		return LegacyOptions::instance();
	}

	/**
	 * Get Options instance from container or fallback to singleton.
	 *
	 * @deprecated Use resolveOptions() instead - this method creates unnecessary instances.
	 * @param ContainerInterface $container Service container.
	 * @return LegacyOptions Options instance.
	 */
	protected function getOptions( ContainerInterface $container ): LegacyOptions {
		return self::resolveOptions( $container );
	}
}






