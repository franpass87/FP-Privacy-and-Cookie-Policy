<?php
/**
 * Sanitizer implementation.
 *
 * @package FP\Privacy\Services\Sanitization
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Services\Sanitization;

/**
 * Sanitizer implementation using WordPress sanitization functions.
 */
class Sanitizer implements SanitizerInterface {
	/**
	 * Sanitize a value.
	 *
	 * @param mixed  $value Value to sanitize.
	 * @param string $type Sanitization type.
	 * @return mixed Sanitized value.
	 */
	public function sanitize( $value, string $type ) {
		switch ( $type ) {
			case 'text':
			case 'string':
				return sanitize_text_field( $value );

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'email':
				return sanitize_email( $value );

			case 'url':
				return esc_url_raw( $value );

			case 'int':
			case 'integer':
				return absint( $value );

			case 'float':
				return filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );

			case 'bool':
			case 'boolean':
				return (bool) $value;

			case 'array':
				if ( ! is_array( $value ) ) {
					return array();
				}
				return array_map( function ( $item ) {
					return is_array( $item )
						? array_map( 'sanitize_text_field', $item )
						: \sanitize_text_field( (string) $item );
				}, $value );

			case 'key':
				return sanitize_key( $value );

			case 'title':
				return sanitize_title( $value );

			case 'html':
				return wp_kses_post( $value );

			case 'color':
				return sanitize_hex_color( $value );

			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Sanitize an array of data.
	 *
	 * @param array<string, mixed> $data Data to sanitize.
	 * @param array<string, string> $rules Sanitization rules (field => type).
	 * @return array<string, mixed> Sanitized data.
	 */
	public function sanitizeArray( array $data, array $rules ): array {
		$sanitized = array();

		foreach ( $rules as $field => $type ) {
			if ( isset( $data[ $field ] ) ) {
				$sanitized[ $field ] = $this->sanitize( $data[ $field ], $type );
			}
		}

		return $sanitized;
	}
}










