<?php
/**
 * Script blocking rules manager.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

use FP\Privacy\Integrations\DetectorRegistry;

/**
 * Manages script blocking rules for consent categories.
 */
class ScriptRulesManager {
	/**
	 * Normalize a list of handles.
	 *
	 * @param mixed $handles Raw handles.
	 *
	 * @return array<int, string>
	 */
	public function sanitize_handle_list( $handles ) {
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
	public function sanitize_pattern_list( $patterns ) {
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
	public function normalize_entry( array $entry ) {
		return array(
			'script_handles' => $this->sanitize_handle_list( $entry['script_handles'] ?? array() ),
			'style_handles'  => $this->sanitize_handle_list( $entry['style_handles'] ?? array() ),
			'patterns'       => $this->sanitize_pattern_list( $entry['patterns'] ?? array() ),
			'iframes'        => $this->sanitize_pattern_list( $entry['iframes'] ?? array() ),
			'managed'        => isset( $entry['managed'] ) ? Validator::bool( $entry['managed'] ) : false,
		);
	}

	/**
	 * Merge preset suggestions into an existing rule set.
	 *
	 * @param array<string, mixed> $current Current entry.
	 * @param array<string, mixed> $preset  Preset entry.
	 *
	 * @return array<string, mixed>
	 */
	public function merge_with_defaults( array $current, array $preset ) {
		$current_normalized = $this->normalize_entry( $current );
		$preset_normalized  = $this->normalize_entry( $preset );

		$merged = array(
			'script_handles' => $this->merge_unique_list( $current_normalized['script_handles'], $preset_normalized['script_handles'] ),
			'style_handles'  => $this->merge_unique_list( $current_normalized['style_handles'], $preset_normalized['style_handles'] ),
			'patterns'       => $this->merge_unique_list( $current_normalized['patterns'], $preset_normalized['patterns'] ),
			'iframes'        => $this->merge_unique_list( $current_normalized['iframes'], $preset_normalized['iframes'] ),
			'managed'        => false,
		);

		$has_current = $this->has_values( $current_normalized );
		$has_preset  = $this->has_values( $preset_normalized );

		if ( $current_normalized['managed'] && $has_current ) {
			$merged['managed'] = true;
		} elseif ( ! $has_current && $has_preset ) {
			$merged['managed'] = true;
		}

		if ( ! $merged['managed'] && $has_current && ! $this->has_custom_rules( $current_normalized ) && $has_preset ) {
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
	private function merge_unique_list( array $base, array $additional ) {
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
	public function has_values( array $entry ) {
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
	public function has_custom_rules( array $entry ) {
		$managed = isset( $entry['managed'] ) ? Validator::bool( $entry['managed'] ) : false;

		return ! $managed && $this->has_values( $entry );
	}

	/**
	 * Compare script rule lists ignoring management metadata.
	 *
	 * @param array<string, mixed> $a First entry.
	 * @param array<string, mixed> $b Second entry.
	 *
	 * @return bool
	 */
	public function are_equal( array $a, array $b ) {
		return $a['script_handles'] === $b['script_handles']
			&& $a['style_handles'] === $b['style_handles']
			&& $a['patterns'] === $b['patterns']
			&& $a['iframes'] === $b['iframes'];
	}

	/**
	 * Sanitize script blocking rules for active languages and categories.
	 *
	 * @param array<string, mixed> $rules      Raw rules payload.
	 * @param array<int, string>   $languages  Active languages.
	 * @param array<string, mixed> $categories Sanitized categories.
	 * @param array<string, mixed> $existing   Existing rules.
	 * @param LanguageNormalizer   $normalizer Language normalizer instance.
	 *
	 * @return array<string, array<string, array<string, array<int, string>>>>
	 */
	public function sanitize_rules( array $rules, array $languages, array $categories, array $existing, LanguageNormalizer $normalizer ) {
		$sanitized = array();

		foreach ( $languages as $language ) {
			$language = $normalizer->normalize( $language );
			$raw      = isset( $rules[ $language ] ) && \is_array( $rules[ $language ] ) ? $rules[ $language ] : array();
			$sanitized[ $language ] = array();

			foreach ( $categories as $slug => $meta ) {
				$entry            = isset( $raw[ $slug ] ) && \is_array( $raw[ $slug ] ) ? $raw[ $slug ] : array();
				$previous_entry   = isset( $existing[ $language ][ $slug ] ) && \is_array( $existing[ $language ][ $slug ] ) ? $existing[ $language ][ $slug ] : array();
				$previous_managed = isset( $previous_entry['managed'] ) ? Validator::bool( $previous_entry['managed'] ) : false;
				$previous_rules   = $this->normalize_entry( $previous_entry );
				$normalized       = $this->normalize_entry( $entry );

				$managed = false;

				if ( isset( $entry['managed'] ) ) {
					$managed = Validator::bool( $entry['managed'] );
				} elseif ( $previous_managed && $this->are_equal( $previous_rules, $normalized ) ) {
					$managed = true;
				}

				$normalized['managed'] = $managed;

				$sanitized[ $language ][ $slug ] = $normalized;
			}
		}

		return $sanitized;
	}

	/**
	 * Build empty script rule set for a language based on categories metadata.
	 *
	 * @param array<string, mixed>           $categories Categories metadata.
	 * @param array<int, array<string, mixed>> $services   Snapshot services.
	 *
	 * @return array<string, array<string, array<int, string>>>
	 */
	public function build_language_defaults( $categories, $services ) {
		$defaults       = array();
		$snapshot_rules = $this->collect_presets_by_category( $services );

		foreach ( $categories as $slug => $meta ) {
			$defaults[ $slug ] = $this->normalize_entry( array() );

			if ( isset( $snapshot_rules[ $slug ] ) ) {
				$defaults[ $slug ] = $this->merge_with_defaults( $defaults[ $slug ], $snapshot_rules[ $slug ] );
			}
		}

		return $defaults;
	}

	/**
	 * Collect preset entries indexed by consent category.
	 *
	 * @param array<int, array<string, mixed>> $services Services list.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function collect_presets_by_category( array $services ) {
		$presets        = DetectorRegistry::get_blocking_presets();
		$category_rules = array();

		if ( empty( $presets ) ) {
			return $category_rules;
		}

		foreach ( $services as $service ) {
			if ( ! \is_array( $service ) ) {
				continue;
			}

			if ( isset( $service['detected'] ) && ! $service['detected'] ) {
				continue;
			}

			$slug = isset( $service['slug'] ) ? \sanitize_key( $service['slug'] ) : '';

			if ( '' === $slug || ! isset( $presets[ $slug ] ) ) {
				continue;
			}

			$category = isset( $service['category'] ) ? \sanitize_key( $service['category'] ) : '';

			if ( '' === $category ) {
				continue;
			}

			if ( ! isset( $category_rules[ $category ] ) ) {
				$category_rules[ $category ] = $this->normalize_entry( array() );
			}

			$category_rules[ $category ] = $this->merge_with_defaults( $category_rules[ $category ], $presets[ $slug ] );
		}

		return $category_rules;
	}

	/**
	 * Prime script rules when new services are detected.
	 *
	 * @param array<int, array<string, mixed>> $services   Services detected.
	 * @param array<int, string>               $languages  Active languages.
	 * @param array<string, mixed>             $scripts    Current scripts configuration.
	 * @param callable                         $get_categories_callback Callback to get categories for a language.
	 * @param LanguageNormalizer               $normalizer Language normalizer instance.
	 *
	 * @return array<string, mixed>|null Updated scripts or null if no changes.
	 */
	public function prime_from_services( array $services, array $languages, array $scripts, callable $get_categories_callback, LanguageNormalizer $normalizer ) {
		$presets_by_category = $this->collect_presets_by_category( $services );

		if ( empty( $presets_by_category ) ) {
			return null;
		}

		if ( empty( $languages ) ) {
			return null;
		}

		$updated = false;

		foreach ( $languages as $language ) {
			$language   = $normalizer->normalize( $language );
			$categories = call_user_func( $get_categories_callback, $language );

			if ( ! isset( $scripts[ $language ] ) || ! \is_array( $scripts[ $language ] ) ) {
				$scripts[ $language ] = array();
			}

			foreach ( $categories as $slug => $meta ) {
				$current_entry      = isset( $scripts[ $language ][ $slug ] ) && \is_array( $scripts[ $language ][ $slug ] ) ? $scripts[ $language ][ $slug ] : array();
				$normalized_current = $this->normalize_entry( $current_entry );

				if ( $this->has_custom_rules( $normalized_current ) ) {
					continue;
				}

				if ( ! isset( $presets_by_category[ $slug ] ) ) {
					continue;
				}

				$merged = $this->merge_with_defaults( $normalized_current, $presets_by_category[ $slug ] );

				if ( $this->are_equal( $normalized_current, $merged ) && $normalized_current['managed'] === $merged['managed'] ) {
					continue;
				}

				$scripts[ $language ][ $slug ] = $merged;
				$updated                        = true;
			}
		}

		return $updated ? $scripts : null;
	}

	/**
	 * Retrieve script rules for a given language.
	 *
	 * @param string                 $lang       Language code.
	 * @param array<string, mixed>   $categories Categories for the language.
	 * @param array<string, mixed>   $stored     Stored script rules.
	 * @param array<string, mixed>   $defaults   Default script rules.
	 * @param LanguageNormalizer     $normalizer Language normalizer instance.
	 *
	 * @return array<string, array<string, array<int, string>>>
	 */
	public function get_rules_for_language( $lang, array $categories, array $stored, array $defaults, LanguageNormalizer $normalizer ) {
		$lang             = $normalizer->normalize( $lang );
		$normalized_stored = array();

		if ( isset( $stored[ $lang ] ) && \is_array( $stored[ $lang ] ) ) {
			foreach ( $stored[ $lang ] as $slug => $entry ) {
				if ( ! \is_array( $entry ) ) {
					$entry = array();
				}

				$normalized_stored[ $slug ] = $this->normalize_entry( $entry );
			}
		}

		$rules = array();

		foreach ( $categories as $slug => $meta ) {
			$rules[ $slug ] = isset( $defaults[ $slug ] ) ? $defaults[ $slug ] : $this->normalize_entry( array() );

			if ( isset( $normalized_stored[ $slug ] ) ) {
				$entry = $normalized_stored[ $slug ];

				if ( $this->has_custom_rules( $entry ) ) {
					$rules[ $slug ] = $entry;
				} elseif ( $this->has_values( $entry ) ) {
					$rules[ $slug ] = $this->merge_with_defaults( $rules[ $slug ], $entry );
				}
			}
		}

		return $rules;
	}
}