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
use FP\Privacy\Interfaces\ScriptRulesManagerInterface;

/**
 * Manages script blocking rules for consent categories.
 */
class ScriptRulesManager implements ScriptRulesManagerInterface {
	/**
	 * Normalize a list of handles.
	 *
	 * @param mixed $handles Raw handles.
	 *
	 * @return array<int, string>
	 */
	public function sanitize_handle_list( $handles ) {
		return ScriptRulesSanitizer::sanitize_handle_list( $handles );
	}

	/**
	 * Normalize substring patterns used to match sources.
	 *
	 * @param mixed $patterns Raw patterns list.
	 *
	 * @return array<int, string>
	 */
	public function sanitize_pattern_list( $patterns ) {
		return ScriptRulesSanitizer::sanitize_pattern_list( $patterns );
	}

	/**
	 * Normalize script rule entry ensuring consistent structure.
	 *
	 * @param array<string, mixed> $entry Raw entry.
	 *
	 * @return array<string, mixed>
	 */
	public function normalize_entry( array $entry ) {
		return ScriptRulesSanitizer::normalize_entry( $entry );
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
		return ScriptRulesMerger::merge_with_defaults( $current, $preset );
	}

	/**
	 * Determine whether an entry contains meaningful values.
	 *
	 * @param array<string, mixed> $entry Entry to evaluate.
	 *
	 * @return bool
	 */
	public function has_values( array $entry ) {
		return ScriptRulesMerger::has_values( $entry );
	}

	/**
	 * Determine whether the entry was manually customized.
	 *
	 * @param array<string, mixed> $entry Entry to evaluate.
	 *
	 * @return bool
	 */
	public function has_custom_rules( array $entry ) {
		return ScriptRulesMerger::has_custom_rules( $entry );
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
		return ScriptRulesMerger::are_equal( $a, $b );
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
				$previous_rules   = ScriptRulesSanitizer::normalize_entry( $previous_entry );
				$normalized       = ScriptRulesSanitizer::normalize_entry( $entry );

				$managed = false;

				if ( isset( $entry['managed'] ) ) {
					$managed = Validator::bool( $entry['managed'] );
				} elseif ( $previous_managed && ScriptRulesMerger::are_equal( $previous_rules, $normalized ) ) {
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
		return ScriptRulesBuilder::build_language_defaults( $categories, $services );
	}

	/**
	 * Collect preset entries indexed by consent category.
	 *
	 * @param array<int, array<string, mixed>> $services Services list.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function collect_presets_by_category( array $services ) {
		return ScriptRulesBuilder::collect_presets_by_category( $services );
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
		$presets_by_category = ScriptRulesBuilder::collect_presets_by_category( $services );

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
				$normalized_current = ScriptRulesSanitizer::normalize_entry( $current_entry );

				if ( ScriptRulesMerger::has_custom_rules( $normalized_current ) ) {
					continue;
				}

				if ( ! isset( $presets_by_category[ $slug ] ) ) {
					continue;
				}

				$merged = ScriptRulesMerger::merge_with_defaults( $normalized_current, $presets_by_category[ $slug ] );

				if ( ScriptRulesMerger::are_equal( $normalized_current, $merged ) && $normalized_current['managed'] === $merged['managed'] ) {
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

				$normalized_stored[ $slug ] = ScriptRulesSanitizer::normalize_entry( $entry );
			}
		}

		$rules = array();

		foreach ( $categories as $slug => $meta ) {
			$rules[ $slug ] = isset( $defaults[ $slug ] ) ? $defaults[ $slug ] : ScriptRulesSanitizer::normalize_entry( array() );

			if ( isset( $normalized_stored[ $slug ] ) ) {
				$entry = $normalized_stored[ $slug ];

				if ( ScriptRulesMerger::has_custom_rules( $entry ) ) {
					$rules[ $slug ] = $entry;
				} elseif ( ScriptRulesMerger::has_values( $entry ) ) {
					$rules[ $slug ] = ScriptRulesMerger::merge_with_defaults( $rules[ $slug ], $entry );
				}
			}
		}

		return $rules;
	}
}