<?php
/**
 * Script rules sanitizer.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

/**
 * Handles sanitization of script blocking rules.
 */
class ScriptRulesSanitizer {
	/**
	 * Normalize a list of handles.
	 *
	 * @param mixed $handles Raw handles.
	 *
	 * @return array<int, string>
	 */
	public static function sanitize_handle_list( $handles ) {
		if ( \is_string( $handles ) ) {
			$handles = \preg_split( '/[\r\n,]+/', $handles ) ?: array();
		}

		if ( ! \is_array( $handles ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $handles as $handle ) {
			$clean = \sanitize_key( (string) $handle );

			if ( '' === $clean ) {
				continue;
			}

			if ( ! in_array( $clean, $normalized, true ) ) {
				$normalized[] = $clean;
			}
		}

		return $normalized;
	}

	/**
	 * Normalize substring patterns used to match sources.
	 *
	 * @param mixed $patterns Raw patterns list.
	 *
	 * @return array<int, string>
	 */
	public static function sanitize_pattern_list( $patterns ) {
		if ( \is_string( $patterns ) ) {
			$patterns = \preg_split( '/[\r\n]+/', $patterns ) ?: array();
		}

		if ( ! \is_array( $patterns ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $patterns as $pattern ) {
			$clean = Validator::text( $pattern );

			if ( '' === $clean ) {
				continue;
			}

			if ( ! in_array( $clean, $normalized, true ) ) {
				$normalized[] = $clean;
			}
		}

		return $normalized;
	}

	/**
	 * Normalize script rule entry ensuring consistent structure.
	 *
	 * @param array<string, mixed> $entry Raw entry.
	 *
	 * @return array<string, mixed>
	 */
	public static function normalize_entry( array $entry ) {
		return array(
			'script_handles' => self::sanitize_handle_list( $entry['script_handles'] ?? array() ),
			'style_handles'  => self::sanitize_handle_list( $entry['style_handles'] ?? array() ),
			'patterns'       => self::sanitize_pattern_list( $entry['patterns'] ?? array() ),
			'iframes'        => self::sanitize_pattern_list( $entry['iframes'] ?? array() ),
			'managed'        => isset( $entry['managed'] ) ? Validator::bool( $entry['managed'] ) : false,
		);
	}
}















