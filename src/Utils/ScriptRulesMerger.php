<?php
/**
 * Script rules merger.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

/**
 * Handles merging and comparison of script blocking rules.
 */
class ScriptRulesMerger {
	/**
	 * Merge preset suggestions into an existing rule set.
	 *
	 * @param array<string, mixed> $current Current entry.
	 * @param array<string, mixed> $preset  Preset entry.
	 *
	 * @return array<string, mixed>
	 */
	public static function merge_with_defaults( array $current, array $preset ) {
		$current_normalized = ScriptRulesSanitizer::normalize_entry( $current );
		$preset_normalized  = ScriptRulesSanitizer::normalize_entry( $preset );

		$merged = array(
			'script_handles' => self::merge_unique_list( $current_normalized['script_handles'], $preset_normalized['script_handles'] ),
			'style_handles'  => self::merge_unique_list( $current_normalized['style_handles'], $preset_normalized['style_handles'] ),
			'patterns'       => self::merge_unique_list( $current_normalized['patterns'], $preset_normalized['patterns'] ),
			'iframes'        => self::merge_unique_list( $current_normalized['iframes'], $preset_normalized['iframes'] ),
			'managed'        => false,
		);

		$has_current = self::has_values( $current_normalized );
		$has_preset  = self::has_values( $preset_normalized );

		if ( $current_normalized['managed'] && $has_current ) {
			$merged['managed'] = true;
		} elseif ( ! $has_current && $has_preset ) {
			$merged['managed'] = true;
		}

		if ( ! $merged['managed'] && $has_current && ! self::has_custom_rules( $current_normalized ) && $has_preset ) {
			$merged['managed'] = true;
		}

		return $merged;
	}

	/**
	 * Merge unique values preserving order.
	 *
	 * @param array<int, string> $base       Base list.
	 * @param array<int, string> $additional Additional values.
	 *
	 * @return array<int, string>
	 */
	private static function merge_unique_list( array $base, array $additional ) {
		foreach ( $additional as $value ) {
			if ( '' === $value ) {
				continue;
			}

			if ( ! in_array( $value, $base, true ) ) {
				$base[] = $value;
			}
		}

		return $base;
	}

	/**
	 * Determine whether an entry contains meaningful values.
	 *
	 * @param array<string, mixed> $entry Entry to evaluate.
	 *
	 * @return bool
	 */
	public static function has_values( array $entry ) {
		return ! empty( $entry['script_handles'] )
			|| ! empty( $entry['style_handles'] )
			|| ! empty( $entry['patterns'] )
			|| ! empty( $entry['iframes'] );
	}

	/**
	 * Determine whether the entry was manually customized.
	 *
	 * @param array<string, mixed> $entry Entry to evaluate.
	 *
	 * @return bool
	 */
	public static function has_custom_rules( array $entry ) {
		$managed = isset( $entry['managed'] ) ? Validator::bool( $entry['managed'] ) : false;

		return ! $managed && self::has_values( $entry );
	}

	/**
	 * Compare script rule lists ignoring management metadata.
	 *
	 * @param array<string, mixed> $a First entry.
	 * @param array<string, mixed> $b Second entry.
	 *
	 * @return bool
	 */
	public static function are_equal( array $a, array $b ) {
		return $a['script_handles'] === $b['script_handles']
			&& $a['style_handles'] === $b['style_handles']
			&& $a['patterns'] === $b['patterns']
			&& $a['iframes'] === $b['iframes'];
	}
}















