<?php
/**
 * HTTP request factory.
 *
 * @package FP\Privacy\Infrastructure\Http
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Infrastructure\Http;

/**
 * Factory for creating HTTP client instances.
 */
class RequestFactory {
	/**
	 * Create HTTP client instance.
	 *
	 * @return HttpClientInterface HTTP client instance.
	 */
	public static function create(): HttpClientInterface {
		return new WpHttpClient();
	}
}










