<?php
/**
 * Sanitizer interface.
 *
 * @package FP\Privacy\Services\Sanitization
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Sanitization;

/**
 * Sanitizer interface for sanitizing data.
 */
interface SanitizerInterface {
	/**
	 * Sanitize a value.
	 *
	 * @param mixed  $value Value to sanitize.
	 * @param string $type Sanitization type.
	 * @return mixed Sanitized value.
	 */
	public function sanitize( $value, string $type );

	/**
	 * Sanitize an array of data.
	 *
	 * @param array<string, mixed> $data Data to sanitize.
	 * @param array<string, string> $rules Sanitization rules (field => type).
	 * @return array<string, mixed> Sanitized data.
	 */
	public function sanitizeArray( array $data, array $rules ): array;
}










