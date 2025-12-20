<?php
/**
 * Transient cache implementation.
 *
 * @package FP\Privacy\Services\Cache
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Cache;

/**
 * Cache implementation using WordPress transients.
 */
class TransientCache implements CacheInterface {
	/**
	 * Cache key prefix.
	 *
	 * @var string
	 */
	private $prefix;

	/**
	 * Constructor.
	 *
	 * @param string $prefix Cache key prefix.
	 */
	public function __construct( string $prefix = 'fp_privacy_' ) {
		$this->prefix = $prefix;
	}

	/**
	 * Get a cached value.
	 *
	 * @param string $key Cache key.
	 * @param mixed  $default Default value if not found.
	 * @return mixed Cached value or default.
	 */
	public function get( string $key, $default = null ) {
		$value = get_transient( $this->prefix . $key );
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
		return set_transient( $this->prefix . $key, $value, $ttl );
	}

	/**
	 * Delete a cached value.
	 *
	 * @param string $key Cache key.
	 * @return bool True on success.
	 */
	public function delete( string $key ): bool {
		return delete_transient( $this->prefix . $key );
	}

	/**
	 * Flush all cache.
	 *
	 * Note: WordPress doesn't provide a way to flush all transients,
	 * so this is a no-op. Use ObjectCache for proper flushing.
	 *
	 * @return bool True on success.
	 */
	public function flush(): bool {
		// WordPress doesn't support flushing all transients.
		// This would require iterating through all transients which is expensive.
		return true;
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










