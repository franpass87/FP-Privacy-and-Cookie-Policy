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
	 * Sanitize a list of script handles.
	 *
	 * @param mixed $handles Script handles (string, array, or comma-separated string).
	 * @return array<int, string> Sanitized array of handles.
	 */
	public function sanitize_handle_list( $handles );

	/**
	 * Sanitize a list of URL patterns.
	 *
	 * @param mixed $patterns URL patterns (string, array, or comma-separated string).
	 * @return array<int, string> Sanitized array of patterns.
	 */
	public function sanitize_pattern_list( $patterns );

	/**
	 * Normalize a script rules entry to a standard format.
	 *
	 * @param array<string, mixed> $entry Raw entry data.
	 * @return array<string, mixed> Normalized entry.
	 */
	public function normalize_entry( array $entry );

	/**
	 * Merge current rules with preset defaults.
	 *
	 * @param array<string, mixed> $current Current rules.
	 * @param array<string, mixed> $preset  Preset defaults.
	 * @return array<string, mixed> Merged rules.
	 */
	public function merge_with_defaults( array $current, array $preset );

	/**
	 * Check if an entry has any values set.
	 *
	 * @param array<string, mixed> $entry Entry to check.
	 * @return bool True if entry has values.
	 */
	public function has_values( array $entry );

	/**
	 * Check if an entry has custom rules (non-preset).
	 *
	 * @param array<string, mixed> $entry Entry to check.
	 * @return bool True if entry has custom rules.
	 */
	public function has_custom_rules( array $entry );

	/**
	 * Compare two entries for equality.
	 *
	 * @param array<string, mixed> $a First entry.
	 * @param array<string, mixed> $b Second entry.
	 * @return bool True if entries are equal.
	 */
	public function are_equal( array $a, array $b );

	/**
	 * Sanitize script rules for all languages.
	 *
	 * @param array<string, mixed>        $rules      Raw rules data.
	 * @param array<int, string>          $languages Active languages.
	 * @param array<string, array<mixed>> $categories Category definitions.
	 * @param array<string, mixed>        $existing   Existing rules.
	 * @param LanguageNormalizer           $normalizer Language normalizer instance.
	 * @return array<string, mixed> Sanitized rules.
	 */
	public function sanitize_rules( array $rules, array $languages, array $categories, array $existing, LanguageNormalizer $normalizer );

	/**
	 * Build default script rules for a language.
	 *
	 * @param array<string, array<mixed>> $categories Category definitions.
	 * @param array<int, array<mixed>>    $services   Detected services.
	 * @return array<string, mixed> Default rules.
	 */
	public function build_language_defaults( $categories, $services );

	/**
	 * Collect blocking presets grouped by category.
	 *
	 * @param array<int, array<string, mixed>> $services Detected services.
	 * @return array<string, array<string, mixed>> Presets by category.
	 */
	public function collect_presets_by_category( array $services );

	/**
	 * Prime script rules from detected services.
	 *
	 * @param array<int, array<string, mixed>> $services              Detected services.
	 * @param array<int, string>                $languages             Active languages.
	 * @param array<string, mixed>               $scripts                Current script rules.
	 * @param callable                           $get_categories_callback Callback to get categories.
	 * @param LanguageNormalizer                 $normalizer             Language normalizer instance.
	 * @return array<string, mixed> Updated script rules.
	 */
	public function prime_from_services( array $services, array $languages, array $scripts, callable $get_categories_callback, LanguageNormalizer $normalizer );

	/**
	 * Get script rules for a specific language.
	 *
	 * @param string                $lang       Language code.
	 * @param array<string, mixed>  $categories Category definitions.
	 * @param array<string, mixed>  $stored     Stored rules.
	 * @param array<string, mixed>  $defaults   Default rules.
	 * @param LanguageNormalizer    $normalizer Language normalizer instance.
	 * @return array<string, mixed> Rules for the language.
	 */
	public function get_rules_for_language( $lang, array $categories, array $stored, array $defaults, LanguageNormalizer $normalizer );
}
