<?php
/**
 * Script rules manager interface.
 *
 * @package FP\Privacy\Interfaces
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Interfaces;

use FP\Privacy\Utils\LanguageNormalizer;

/**
 * Interface for script blocking rules management.
 */
interface ScriptRulesManagerInterface {
	/**
	 * Normalize a list of handles.
	 *
	 * @param mixed $handles Raw handles.
	 *
	 * @return array<int, string>
	 */
	public function sanitize_handle_list( $handles );

	/**
	 * Normalize substring patterns used to match sources.
	 *
	 * @param mixed $patterns Raw patterns list.
	 *
	 * @return array<int, string>
	 */
	public function sanitize_pattern_list( $patterns );

	/**
	 * Normalize script rule entry ensuring consistent structure.
	 *
	 * @param array<string, mixed> $entry Raw entry.
	 *
	 * @return array<string, mixed>
	 */
	public function normalize_entry( array $entry );

	/**
	 * Merge preset suggestions into an existing rule set.
	 *
	 * @param array<string, mixed> $current Current entry.
	 * @param array<string, mixed> $preset  Preset entry.
	 *
	 * @return array<string, mixed>
	 */
	public function merge_with_defaults( array $current, array $preset );

	/**
	 * Determine whether an entry contains meaningful values.
	 *
	 * @param array<string, mixed> $entry Entry to evaluate.
	 *
	 * @return bool
	 */
	public function has_values( array $entry );

	/**
	 * Determine whether the entry was manually customized.
	 *
	 * @param array<string, mixed> $entry Entry to evaluate.
	 *
	 * @return bool
	 */
	public function has_custom_rules( array $entry );

	/**
	 * Compare script rule lists ignoring management metadata.
	 *
	 * @param array<string, mixed> $a First entry.
	 * @param array<string, mixed> $b Second entry.
	 *
	 * @return bool
	 */
	public function are_equal( array $a, array $b );

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
	public function sanitize_rules( array $rules, array $languages, array $categories, array $existing, LanguageNormalizer $normalizer );

	/**
	 * Build empty script rule set for a language based on categories metadata.
	 *
	 * @param array<string, mixed>             $categories Categories metadata.
	 * @param array<int, array<string, mixed>> $services   Snapshot services.
	 *
	 * @return array<string, array<string, array<int, string>>>
	 */
	public function build_language_defaults( $categories, $services );

	/**
	 * Collect preset entries indexed by consent category.
	 *
	 * @param array<int, array<string, mixed>> $services Services list.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function collect_presets_by_category( array $services );

	/**
	 * Prime script rules when new services are detected.
	 *
	 * @param array<int, array<string, mixed>> $services              Services detected.
	 * @param array<int, string>               $languages             Active languages.
	 * @param array<string, mixed>             $scripts               Current scripts configuration.
	 * @param callable                         $get_categories_callback Callback to get categories for a language.
	 * @param LanguageNormalizer               $normalizer            Language normalizer instance.
	 *
	 * @return array<string, mixed>|null Updated scripts or null if no changes.
	 */
	public function prime_from_services( array $services, array $languages, array $scripts, callable $get_categories_callback, LanguageNormalizer $normalizer );

	/**
	 * Retrieve script rules for a given language.
	 *
	 * @param string               $lang       Language code.
	 * @param array<string, mixed> $categories Categories for the language.
	 * @param array<string, mixed> $stored     Stored script rules.
	 * @param array<string, mixed> $defaults   Default script rules.
	 * @param LanguageNormalizer   $normalizer Language normalizer instance.
	 *
	 * @return array<string, array<string, array<int, string>>>
	 */
	public function get_rules_for_language( $lang, array $categories, array $stored, array $defaults, LanguageNormalizer $normalizer );
}