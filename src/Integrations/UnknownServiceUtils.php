<?php
/**
 * Unknown service utility functions.
 *
 * @package FP\Privacy\Integrations
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Integrations;

use function parse_url;
use function sanitize_key;
use function strtolower;

/**
 * Utility functions for unknown service detection.
 */
class UnknownServiceUtils {
	/**
	 * Check if a domain is in the known domains list.
	 *
	 * @param string              $domain        Domain to check.
	 * @param array<int, string> $known_domains List of known domains.
	 *
	 * @return bool
	 */
	public static function is_known_domain( $domain, array $known_domains ) {
		if ( empty( $domain ) || empty( $known_domains ) ) {
			return false;
		}

		$domain_lower = strtolower( $domain );

		foreach ( $known_domains as $known ) {
			$known_lower = strtolower( (string) $known );

			if ( $domain_lower === $known_lower ) {
				return true;
			}

			// Check if domain is a subdomain of known domain
			if ( \strlen( $known_lower ) < \strlen( $domain_lower ) ) {
				if ( \substr( $domain_lower, -\strlen( $known_lower ) - 1 ) === '.' . $known_lower ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Extract domain from URL.
	 *
	 * @param string $url URL.
	 *
	 * @return string
	 */
	public static function extract_domain( $url ) {
		if ( empty( $url ) ) {
			return '';
		}

		$parsed = parse_url( $url );

		if ( ! $parsed || ! isset( $parsed['host'] ) ) {
			return '';
		}

		$host = $parsed['host'];

		// Remove port if present
		if ( false !== \strpos( $host, ':' ) ) {
			$host = \explode( ':', $host )[0];
		}

		// Remove www. prefix for consistency
		if ( 0 === \strpos( $host, 'www.' ) ) {
			$host = \substr( $host, 4 );
		}

		return $host;
	}

	/**
	 * Check if URL is local (same domain or relative).
	 *
	 * @param string $url URL to check.
	 *
	 * @return bool
	 */
	public static function is_local_url( $url ) {
		if ( empty( $url ) ) {
			return true;
		}

		// Relative URLs are local
		if ( 0 === \strpos( $url, '/' ) || 0 === \strpos( $url, '#' ) || 0 === \strpos( $url, '?' ) ) {
			return true;
		}

		// Check if URL is on same domain
		$parsed = parse_url( $url );

		if ( ! $parsed || ! isset( $parsed['host'] ) ) {
			return true;
		}

		$host = $parsed['host'];

		// Remove port if present
		if ( false !== \strpos( $host, ':' ) ) {
			$host = \explode( ':', $host )[0];
		}

		// Get current site domain
		$site_url = \home_url();
		$site_parsed = parse_url( $site_url );

		if ( ! $site_parsed || ! isset( $site_parsed['host'] ) ) {
			return false;
		}

		$site_host = $site_parsed['host'];

		// Remove port if present
		if ( false !== \strpos( $site_host, ':' ) ) {
			$site_host = \explode( ':', $site_host )[0];
		}

		// Remove www. prefix for comparison
		if ( 0 === \strpos( $host, 'www.' ) ) {
			$host = \substr( $host, 4 );
		}
		if ( 0 === \strpos( $site_host, 'www.' ) ) {
			$site_host = \substr( $site_host, 4 );
		}

		return $host === $site_host;
	}
}















