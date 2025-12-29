<?php
/**
 * Options factory - creates appropriate options repository.
 *
 * @package FP\Privacy\Infrastructure\Options
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Infrastructure\Options;

/**
 * Factory for creating options repository instances.
 */
class OptionsFactory {
	/**
	 * Create options repository instance.
	 *
	 * @param string $type Repository type ('wp' or 'legacy').
	 * @return OptionsRepositoryInterface Options repository instance.
	 */
	public static function create( string $type = 'wp' ): OptionsRepositoryInterface {
		if ( 'legacy' === $type ) {
			// During migration, we can use the adapter that wraps the singleton.
			return new OptionsAdapter();
		}

		// Default: use WordPress options adapter.
		return new WpOptionsAdapter();
	}
}













