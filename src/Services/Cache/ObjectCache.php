<?php
/**
 * Object cache implementation.
 *
 * @package FP\Privacy\Services\Cache
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Cache;

/**
 * Cache implementation using WordPress object cache.
 */
class ObjectCache implements CacheInterface {
	/**
	 * Cache group.
	 *
	 * @var string
	 */
	private $group;

	/**
	 * Constructor.
	 *
	 * @param string $group Cache group.
	 */
	public function __construct( string $group = 'fp_privacy' ) {
		$this->group = $group;
	}

	/**
	 * Get a cached value.
	 *
	 * @param string $key Cache key.
	 * @param mixed  $default Default value if not found.
	 * @return mixed Cached value or default.
	 */
	public function get( string $key, $default = null ) {
		$value = wp_cache_get( $key, $this->group );
		return false !== $value ? $value : $default;
	}

	/**
	 * Set a cached value.
	 *
	 * @param string $key Cache key.
	 * @param mixed  $value Value to cache.
	 * @param int    $ttl Time to live in seconds.
	 * @return bool True on success.
	 */
	public function set( string $key, $value, int $ttl = 3600 ): bool {
		return wp_cache_set( $key, $value, $this->group, $ttl );
	}

	/**
	 * Delete a cached value.
	 *
	 * @param string $key Cache key.
	 * @return bool True on success.
	 */
	public function delete( string $key ): bool {
		return wp_cache_delete( $key, $this->group );
	}

	/**
	 * Flush all cache.
	 *
	 * @return bool True on success.
	 */
	public function flush(): bool {
		return wp_cache_flush_group( $this->group );
	}

	/**
	 * Remember a value (get or compute and cache).
	 *
	 * @param string   $key Cache key.
	 * @param callable $callback Callback to compute value if not cached.
	 * @param int      $ttl Time to live in seconds.
	 * @return mixed Cached or computed value.
	 */
	public function remember( string $key, callable $callback, int $ttl = 3600 ) {
		$value = $this->get( $key );

		if ( null !== $value ) {
			return $value;
		}

		$value = call_user_func( $callback );
		$this->set( $key, $value, $ttl );

		return $value;
	}
}










