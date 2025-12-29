<?php
/**
 * Detector cache manager.
 *
 * @package FP\Privacy\Integrations
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Integrations;

use FP\Privacy\Services\Cache\CacheInterface;

/**
 * Manages caching for service detection results.
 * Uses CacheInterface for persistence while maintaining runtime cache for performance.
 */
class DetectorCache {
	const CACHE_KEY = 'detector_cache';
	const CACHE_TTL = 900;

	/**
	 * Cache interface for persistence.
	 *
	 * @var CacheInterface|null
	 */
	private $cache;

	/**
	 * Cached services for the current request.
	 *
	 * @var array<int, array<string, mixed>>|null
	 */
	private $runtime_cache = null;

	/**
	 * Timestamp of the runtime cache snapshot.
	 *
	 * @var int
	 */
	private $cache_timestamp = 0;

	/**
	 * Tracks whether the persisted cache has been hydrated.
	 *
	 * @var bool
	 */
	private $hydrated = false;

	/**
	 * Constructor.
	 *
	 * @param CacheInterface|null $cache Cache interface for persistence. If null, falls back to direct option access.
	 */
	public function __construct( ?CacheInterface $cache = null ) {
		$this->cache = $cache;
	}

	/**
	 * Get runtime cache.
	 *
	 * @return array<int, array<string, mixed>>|null
	 */
	public function get_runtime_cache(): ?array {
		return $this->runtime_cache;
	}

	/**
	 * Set runtime cache.
	 *
	 * @param array<int, array<string, mixed>>|null $cache Cache data.
	 *
	 * @return void
	 */
	public function set_runtime_cache( ?array $cache ): void {
		$this->runtime_cache = $cache;
	}

	/**
	 * Get cache timestamp.
	 *
	 * @return int
	 */
	public function get_cache_timestamp(): int {
		return $this->cache_timestamp;
	}

	/**
	 * Set cache timestamp.
	 *
	 * @param int $timestamp Timestamp.
	 *
	 * @return void
	 */
	public function set_cache_timestamp( int $timestamp ): void {
		$this->cache_timestamp = $timestamp;
	}

	/**
	 * Hydrate runtime cache from persisted snapshot.
	 *
	 * @return void
	 */
	public function hydrate_cache(): void {
		if ( $this->hydrated ) {
			return;
		}

		$this->hydrated = true;

		$cache = $this->get_persisted_cache();

		if ( ! is_array( $cache ) || ! isset( $cache['services'], $cache['timestamp'] ) ) {
			return;
		}

		$services  = is_array( $cache['services'] ) ? $cache['services'] : array();
		$timestamp = (int) $cache['timestamp'];

		if ( $this->is_cache_expired( $timestamp ) ) {
			return;
		}

		$this->runtime_cache  = $services;
		$this->cache_timestamp = $timestamp;
	}

	/**
	 * Get persisted cache data.
	 *
	 * @return array<string, mixed>|null Cache data or null if not found.
	 */
	private function get_persisted_cache(): ?array {
		if ( $this->cache ) {
			$cached = $this->cache->get( self::CACHE_KEY );
			return is_array( $cached ) ? $cached : null;
		}

		// Fallback to direct option access for backward compatibility.
		if ( ! function_exists( '\get_option' ) ) {
			return null;
		}

		$cache = \get_option( 'fp_privacy_detector_cache' );
		return is_array( $cache ) ? $cache : null;
	}

	/**
	 * Persist runtime cache.
	 *
	 * @return void
	 */
	public function persist_cache(): void {
		$cache_data = array(
			'services'  => is_array( $this->runtime_cache ) ? $this->runtime_cache : array(),
			'timestamp' => $this->cache_timestamp,
		);

		if ( $this->cache ) {
			$ttl = $this->get_cache_ttl();
			$this->cache->set( self::CACHE_KEY, $cache_data, $ttl );
			return;
		}

		// Fallback to direct option access for backward compatibility.
		if ( function_exists( '\update_option' ) ) {
			\update_option( 'fp_privacy_detector_cache', $cache_data, false );
		}
	}

	/**
	 * Clear detector cache.
	 *
	 * @return void
	 */
	public function invalidate_cache(): void {
		$this->runtime_cache  = null;
		$this->cache_timestamp = 0;
		$this->hydrated       = false;

		if ( $this->cache ) {
			$this->cache->delete( self::CACHE_KEY );
			return;
		}

		// Fallback to direct option access for backward compatibility.
		if ( function_exists( '\delete_option' ) ) {
			\delete_option( 'fp_privacy_detector_cache' );
		}
	}

	/**
	 * Determine if cache has expired.
	 *
	 * @param int $timestamp Timestamp to evaluate.
	 *
	 * @return bool
	 */
	public function is_cache_expired( int $timestamp ): bool {
		if ( ! $timestamp ) {
			return true;
		}

		$ttl = $this->get_cache_ttl();

		if ( $ttl <= 0 ) {
			return false;
		}

		return ( time() - $timestamp ) > $ttl;
	}

	/**
	 * Fetch cache TTL allowing filters.
	 *
	 * @return int
	 */
	private function get_cache_ttl(): int {
		$ttl = self::CACHE_TTL;

		if ( function_exists( '\apply_filters' ) ) {
			$ttl = (int) \apply_filters( 'fp_privacy_detector_cache_ttl', $ttl );
		}

		return (int) $ttl;
	}
}
















