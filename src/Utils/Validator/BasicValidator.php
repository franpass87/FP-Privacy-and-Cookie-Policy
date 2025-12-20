<?php
/**
 * Basic validation utilities.
 *
 * @package FP\Privacy\Utils\Validator
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils\Validator;

use const FILTER_VALIDATE_INT;
use function filter_var;
use function in_array;
use function preg_match;
use function rest_sanitize_boolean;
use function sanitize_email;
use function sanitize_hex_color;
use function sanitize_text_field;
use function strtolower;
use function wp_kses_post;
use function esc_url_raw;

/**
 * Provides basic validation and sanitization methods.
 */
class BasicValidator {
	/**
	 * Normalize a boolean value.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return bool
	 */
	public static function bool( $value ): bool {
		return (bool) rest_sanitize_boolean( $value );
	}

	/**
	 * Normalize an integer within optional boundaries.
	 *
	 * @param mixed    $value   Raw value.
	 * @param int      $default Default value.
	 * @param int|null $min     Minimum value.
	 * @param int|null $max     Maximum value.
	 *
	 * @return int
	 */
	public static function int( $value, int $default = 0, ?int $min = null, ?int $max = null ): int {
		if ( null !== $value && '' !== $value ) {
			$validated = filter_var( $value, FILTER_VALIDATE_INT );
			if ( false !== $validated && null !== $validated ) {
				$value = (int) $validated;
			}
		}

		if ( ! is_int( $value ) ) {
			$value = $default;
		}

		if ( null !== $min && $value < $min ) {
			$value = $min;
		}

		if ( null !== $max && $value > $max ) {
			$value = $max;
		}

		return (int) $value;
	}

	/**
	 * Sanitize plain text.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function text( $value ): string {
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitize multi-line text allowing basic markup.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function textarea( $value ): string {
		return wp_kses_post( (string) $value );
	}

	/**
	 * Sanitize URL value.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function url( $value ): string {
		return esc_url_raw( (string) $value );
	}

	/**
	 * Sanitize email value.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function email( $value ): string {
		return sanitize_email( (string) $value );
	}

	/**
	 * Sanitize hexadecimal color.
	 *
	 * @param mixed  $value   Raw value.
	 * @param string $default Default fallback.
	 *
	 * @return string
	 */
	public static function hex_color( $value, string $default = '' ): string {
		$color = sanitize_hex_color( (string) $value );

		return $color ? $color : $default;
	}

	/**
	 * Sanitize locale string.
	 *
	 * @param mixed  $value   Raw value.
	 * @param string $default Default fallback.
	 *
	 * @return string
	 */
	public static function locale( $value, string $default = 'en_US' ): string {
		$value = sanitize_text_field( (string) $value );

		if ( '' === $value ) {
			return $default;
		}

		if ( ! preg_match( '/^[A-Za-z]{2,}([_-][A-Za-z0-9]{2,})?$/', $value ) ) {
			return $default;
		}

		return $value;
	}

	/**
	 * Sanitize a list of locale codes ensuring uniqueness.
	 *
	 * @param mixed  $values   Raw values.
	 * @param string $fallback Fallback locale.
	 *
	 * @return array<int, string>
	 */
	public static function locale_list( $values, string $fallback ): array {
		$values  = is_array( $values ) ? $values : array();
		$locales = array();

		foreach ( $values as $value ) {
			$locale = self::locale( $value, '' );

			if ( '' === $locale || in_array( $locale, $locales, true ) ) {
				continue;
			}

			$locales[] = $locale;
		}

		if ( empty( $locales ) ) {
			$locales[] = self::locale( $fallback, 'en_US' );
		}

		// IMPORTANTE: Assicura che it_IT sia sempre la prima lingua (principale)
		// Questo garantisce che la Cookie Policy venga generata prima in italiano
		$italian_index = array_search( 'it_IT', $locales, true );
		if ( false !== $italian_index && $italian_index !== 0 ) {
			// it_IT è presente ma non è il primo: spostalo all'inizio
			unset( $locales[ $italian_index ] );
			// Re-index array dopo unset
			$locales = array_values( $locales );
			// Metti it_IT all'inizio
			array_unshift( $locales, 'it_IT' );
		}
		// Se it_IT non è presente, non lo aggiungiamo forzatamente
		// (l'utente potrebbe voler usare solo altre lingue)

		return $locales;
	}

	/**
	 * Validate choice against allowed values.
	 *
	 * @param mixed             $value   Raw value.
	 * @param array<int,string> $allowed Allowed values.
	 * @param string            $default Default fallback.
	 *
	 * @return string
	 */
	public static function choice( $value, array $allowed, string $default ): string {
		$value = sanitize_text_field( (string) $value );

		return in_array( $value, $allowed, true ) ? $value : $default;
	}
}
















