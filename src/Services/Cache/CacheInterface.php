<?php
/**
 * Cache interface.
 *
 * @package FP\Privacy\Services\Cache
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Cache;

/**
 * Cache interface for storing and retrieving cached data.
 */
interface CacheInterface {
	/**
	 * Get a cached value.
	 *
	 * @param string $key Cache key.
	 * @param mixed  $default Default value if not found.
	 * @return mixed Cached value or default.
	 */
	public function get( string $key, $default = null );

	/**
	 * Set a cached value.
	 *
	 * @param string $key Cache key.
	 * @param mixed  $value Value to cache.
	 * @param int    $ttl Time to live in seconds.
	 * @return bool True on success.
	 */
	public function set( string $key, $value, int $ttl = 3600 ): bool;

	/**
	 * Delete a cached value.
	 *
	 * @param string $key Cache key.
	 * @return bool True on success.
	 */
	public function delete( string $key ): bool;

	/**
	 * Flush all cache.
	 *
	 * @return bool True on success.
	 */
	public function flush(): bool;

	/**
	 * Remember a value (get or compute and cache).
	 *
	 * @param string   $key Cache key.
	 * @param callable $callback Callback to compute value if not cached.
	 * @param int      $ttl Time to live in seconds.
	 * @return mixed Cached or computed value.
	 */
	public function remember( string $key, callable $callback, int $ttl = 3600 );
}










