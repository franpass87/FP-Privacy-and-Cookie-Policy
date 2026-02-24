<?php
/**
 * REST rate limiter.
 *
 * @package FP\Privacy\REST
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\REST;

use WP_Error;

/**
 * Shared rate limiter for REST consent endpoints.
 */
class RateLimiter {
	/**
	 * Maximum requests allowed in the window.
	 *
	 * @var int
	 */
	private const MAX_REQUESTS = 10;

	/**
	 * Window duration in seconds.
	 *
	 * @var int
	 */
	private const WINDOW_SECONDS = 600; // 10 minutes.

	/**
	 * Check rate limit and return WP_Error if exceeded, true otherwise.
	 *
	 * @param string $action Action identifier (e.g. 'consent', 'revoke').
	 * @return true|WP_Error True if allowed, WP_Error if rate limited.
	 */
	public static function check( string $action = 'consent' ) {
		$ip   = self::get_client_ip();
		$salt = function_exists( '\fp_privacy_get_ip_salt' ) ? \fp_privacy_get_ip_salt() : \wp_salt( 'auth' );
		$key  = 'fp_privacy_rate_' . $action . '_' . hash( 'sha256', $ip . '|' . $salt );

		$attempt = (int) \get_transient( $key );
		if ( $attempt >= self::MAX_REQUESTS ) {
			return new WP_Error(
				'fp_privacy_rate_limited',
				\__( 'Too many requests. Please try again later.', 'fp-privacy' ),
				array( 'status' => 429 )
			);
		}

		\set_transient( $key, $attempt + 1, self::WINDOW_SECONDS );

		return true;
	}

	/**
	 * Get client IP considering trusted proxy headers.
	 *
	 * Only trusts X-Forwarded-For / X-Real-IP when the direct connection
	 * comes from a known loopback or RFC-1918 address, which covers the
	 * most common reverse-proxy and container setups.
	 *
	 * @return string Client IP address.
	 */
	public static function get_client_ip(): string {
		$remote_addr = isset( $_SERVER['REMOTE_ADDR'] )
			? \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '';

		if ( self::is_trusted_proxy( $remote_addr ) ) {
			$forwarded = self::get_forwarded_ip();
			if ( '' !== $forwarded ) {
				return $forwarded;
			}
		}

		return $remote_addr;
	}

	/**
	 * Check if the remote address is a trusted proxy (loopback or private range).
	 *
	 * @param string $ip IP address.
	 * @return bool
	 */
	private static function is_trusted_proxy( string $ip ): bool {
		if ( '' === $ip ) {
			return false;
		}

		return false === filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE
		);
	}

	/**
	 * Extract the first public IP from forwarding headers.
	 *
	 * @return string IP address or empty string.
	 */
	private static function get_forwarded_ip(): string {
		$headers = array( 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP' );

		foreach ( $headers as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$value = \sanitize_text_field( \wp_unslash( $_SERVER[ $header ] ) );
			$ips   = array_map( 'trim', explode( ',', $value ) );

			foreach ( $ips as $candidate ) {
				if ( filter_var( $candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $candidate;
				}
			}
		}

		return '';
	}
}
