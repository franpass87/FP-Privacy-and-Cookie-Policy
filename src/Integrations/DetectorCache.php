<?php
/**
 * Detector cache manager.
 *
 * @package FP\Privacy\Integrations
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Integrations;

/**
 * Manages caching for service detection results.
 */
class DetectorCache {
	const CACHE_OPTION = 'fp_privacy_detector_cache';
	const CACHE_TTL = 900;

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
	 * Get runtime cache.
	 *
	 * @return array<int, array<string, mixed>>|null
	 */
	public function get_runtime_cache() {
		return $this->runtime_cache;
	}

	/**
	 * Set runtime cache.
	 *
	 * @param array<int, array<string, mixed>>|null $cache Cache data.
	 *
	 * @return void
	 */
	public function set_runtime_cache( $cache ) {
		$this->runtime_cache = $cache;
	}

	/**
	 * Get cache timestamp.
	 *
	 * @return int
	 */
	public function get_cache_timestamp() {
		return $this->cache_timestamp;
	}

	/**
	 * Set cache timestamp.
	 *
	 * @param int $timestamp Timestamp.
	 *
	 * @return void
	 */
	public function set_cache_timestamp( $timestamp ) {
		$this->cache_timestamp = $timestamp;
	}

	/**
	 * Hydrate runtime cache from persisted snapshot.
	 *
	 * @return void
	 */
	public function hydrate_cache() {
		if ( $this->hydrated ) {
			return;
		}

		$this->hydrated = true;

		if ( ! function_exists( '\get_option' ) ) {
			return;
		}

		$cache = \get_option( self::CACHE_OPTION );

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
	 * Persist runtime cache.
	 *
	 * @return void
	 */
	public function persist_cache() {
		if ( ! function_exists( '\update_option' ) ) {
			return;
		}

		\update_option(
			self::CACHE_OPTION,
			array(
				'services'  => is_array( $this->runtime_cache ) ? $this->runtime_cache : array(),
				'timestamp' => $this->cache_timestamp,
			),
			false
		);
	}

	/**
	 * Clear detector cache.
	 *
	 * @return void
	 */
	public function invalidate_cache() {
		$this->runtime_cache  = null;
		$this->cache_timestamp = 0;
		$this->hydrated       = false;

		if ( function_exists( '\delete_option' ) ) {
			\delete_option( self::CACHE_OPTION );
		}
	}

	/**
	 * Determine if cache has expired.
	 *
	 * @param int $timestamp Timestamp to evaluate.
	 *
	 * @return bool
	 */
	public function is_cache_expired( $timestamp ) {
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
	private function get_cache_ttl() {
		$ttl = self::CACHE_TTL;

		if ( function_exists( '\apply_filters' ) ) {
			$ttl = (int) \apply_filters( 'fp_privacy_detector_cache_ttl', $ttl );
		}

		return (int) $ttl;
	}
}
















