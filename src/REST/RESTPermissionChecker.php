<?php
/**
 * REST permission checker.
 *
 * @package FP\Privacy\REST
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\REST;

use WP_Error;
use WP_REST_Request;

/**
 * Handles permission checking for REST endpoints.
 */
class RESTPermissionChecker {
	/**
	 * Check permission for consent endpoint.
	 * Allows consent requests without nonce for public access.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function check_consent_permission( $request ) {
		// Allow consent requests from same origin without nonce
		$origin   = $request->get_header( 'Origin' );
		$referer  = $request->get_header( 'Referer' );
		$site_url = \home_url();
		$site_url = \is_string( $site_url ) ? $site_url : '';
		$site     = $site_url ? \wp_parse_url( $site_url ) : array();
		$site     = \is_array( $site ) ? $site : array();

		if ( $origin && $this->is_same_origin( $origin, $site ) ) {
			return true;
		}

		if ( $referer && $this->is_same_origin( $referer, $site ) ) {
			return true;
		}

		// Fallback: check nonce if provided
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( $nonce && \wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return true;
		}

		// Allow for localhost only when WP_DEBUG is enabled (development environments).
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG
			&& \in_array( \parse_url( $site_url, PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true )
		) {
			return true;
		}

		return new \WP_Error( 'fp_privacy_consent_permission_denied', \__( 'Consent request not allowed from this origin.', 'fp-privacy' ), array( 'status' => 403 ) );
	}

	/**
	 * Check whether the provided URL shares the same origin as the site URL.
	 *
	 * @param string               $candidate  Candidate URL.
	 * @param array<string, mixed> $site_parts Parsed components of home_url().
	 *
	 * @return bool
	 */
	public function is_same_origin( $candidate, array $site_parts ) {
		if ( ! \is_string( $candidate ) || '' === $candidate ) {
			return false;
		}

		$parts = \wp_parse_url( $candidate );

		if ( ! \is_array( $parts ) || empty( $parts['host'] ) ) {
			return false;
		}

		$site_host      = $this->normalize_host( $site_parts['host'] ?? '' );
		$candidate_host = $this->normalize_host( $parts['host'] ?? '' );

		if ( '' === $site_host || '' === $candidate_host || $candidate_host !== $site_host ) {
			return false;
		}

		$site_scheme      = isset( $site_parts['scheme'] ) ? strtolower( (string) $site_parts['scheme'] ) : '';
		$candidate_scheme = isset( $parts['scheme'] ) ? strtolower( (string) $parts['scheme'] ) : '';

		if ( $site_scheme && $candidate_scheme && $candidate_scheme !== $site_scheme ) {
			return false;
		}

		$site_port      = $this->normalize_port( $site_parts );
		$candidate_port = $this->normalize_port( $parts );

		if ( null !== $site_port && null !== $candidate_port && $site_port !== $candidate_port ) {
			return false;
		}

		return true;
	}

	/**
	 * Normalize host for comparison.
	 *
	 * @param string $host Hostname.
	 *
	 * @return string
	 */
	private function normalize_host( $host ) {
		$host = strtolower( (string) $host );
		$host = trim( $host, '.' );

		if ( 0 === strpos( $host, 'www.' ) ) {
			$host = substr( $host, 4 );
		}

		return $host;
	}

	/**
	 * Normalize port taking scheme defaults into account.
	 *
	 * @param array<string, mixed> $parts Parsed URL components.
	 *
	 * @return int|null
	 */
	private function normalize_port( array $parts ) {
		if ( isset( $parts['port'] ) ) {
			return (int) $parts['port'];
		}

		$scheme = isset( $parts['scheme'] ) ? strtolower( (string) $parts['scheme'] ) : '';

		if ( 'https' === $scheme ) {
			return 443;
		}

		if ( 'http' === $scheme ) {
			return 80;
		}

		return null;
	}
}















