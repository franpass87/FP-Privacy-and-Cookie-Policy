<?php
/**
 * Cache factory - creates appropriate cache implementation.
 *
 * @package FP\Privacy\Infrastructure\Cache
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Infrastructure\Cache;

use FP\Privacy\Services\Cache\CacheInterface;
use FP\Privacy\Services\Cache\ObjectCache;
use FP\Privacy\Services\Cache\TransientCache;

/**
 * Factory for creating cache instances.
 */
class CacheFactory {
	/**
	 * Create cache instance.
	 *
	 * Auto-detects best implementation: object cache if available, otherwise transients.
	 *
	 * @param string $prefix Cache key prefix.
	 * @return CacheInterface Cache instance.
	 */
	public static function create( string $prefix = 'fp_privacy_' ): CacheInterface {
		if ( wp_using_ext_object_cache() ) {
			return new ObjectCache( $prefix );
		}

		return new TransientCache( $prefix );
	}
}














