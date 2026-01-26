<?php
/**
 * HTTP client interface.
 *
 * @package FP\Privacy\Infrastructure\Http
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Infrastructure\Http;

/**
 * Interface for HTTP client implementations.
 */
interface HttpClientInterface {
	/**
	 * Make a GET request.
	 *
	 * @param string $url Request URL.
	 * @param array<string, mixed> $args Request arguments.
	 * @return array<string, mixed>|WP_Error Response array or WP_Error on failure.
	 */
	public function get( string $url, array $args = array() );

	/**
	 * Make a POST request.
	 *
	 * @param string $url Request URL.
	 * @param array<string, mixed> $args Request arguments.
	 * @return array<string, mixed>|WP_Error Response array or WP_Error on failure.
	 */
	public function post( string $url, array $args = array() );

	/**
	 * Make a PUT request.
	 *
	 * @param string $url Request URL.
	 * @param array<string, mixed> $args Request arguments.
	 * @return array<string, mixed>|WP_Error Response array or WP_Error on failure.
	 */
	public function put( string $url, array $args = array() );

	/**
	 * Make a DELETE request.
	 *
	 * @param string $url Request URL.
	 * @param array<string, mixed> $args Request arguments.
	 * @return array<string, mixed>|WP_Error Response array or WP_Error on failure.
	 */
	public function delete( string $url, array $args = array() );
}














