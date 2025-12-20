<?php
/**
 * WordPress HTTP client adapter.
 *
 * @package FP\Privacy\Infrastructure\Http
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Infrastructure\Http;

/**
 * WordPress HTTP client that wraps wp_remote_* functions.
 */
class WpHttpClient implements HttpClientInterface {
	/**
	 * Make a GET request.
	 *
	 * @param string $url Request URL.
	 * @param array<string, mixed> $args Request arguments.
	 * @return array<string, mixed>|\WP_Error Response array or WP_Error on failure.
	 */
	public function get( string $url, array $args = array() ) {
		return wp_remote_get( $url, $args );
	}

	/**
	 * Make a POST request.
	 *
	 * @param string $url Request URL.
	 * @param array<string, mixed> $args Request arguments.
	 * @return array<string, mixed>|\WP_Error Response array or WP_Error on failure.
	 */
	public function post( string $url, array $args = array() ) {
		return wp_remote_post( $url, $args );
	}

	/**
	 * Make a PUT request.
	 *
	 * @param string $url Request URL.
	 * @param array<string, mixed> $args Request arguments.
	 * @return array<string, mixed>|\WP_Error Response array or WP_Error on failure.
	 */
	public function put( string $url, array $args = array() ) {
		$args['method'] = 'PUT';
		return wp_remote_request( $url, $args );
	}

	/**
	 * Make a DELETE request.
	 *
	 * @param string $url Request URL.
	 * @param array<string, mixed> $args Request arguments.
	 * @return array<string, mixed>|\WP_Error Response array or WP_Error on failure.
	 */
	public function delete( string $url, array $args = array() ) {
		$args['method'] = 'DELETE';
		return wp_remote_request( $url, $args );
	}
}










