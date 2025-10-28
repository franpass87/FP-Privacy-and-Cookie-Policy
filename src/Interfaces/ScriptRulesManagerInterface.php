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
	public function sanitize_handle_list( $handles );
	public function sanitize_pattern_list( $patterns );
	public function normalize_entry( array $entry );
	public function merge_with_defaults( array $current, array $preset );
	public function has_values( array $entry );
	public function has_custom_rules( array $entry );
	public function are_equal( array $a, array $b );
	public function sanitize_rules( array $rules, array $languages, array $categories, array $existing, LanguageNormalizer $normalizer );
	public function build_language_defaults( $categories, $services );
	public function collect_presets_by_category( array $services );
	public function prime_from_services( array $services, array $languages, array $scripts, callable $get_categories_callback, LanguageNormalizer $normalizer );
	public function get_rules_for_language( $lang, array $categories, array $stored, array $defaults, LanguageNormalizer $normalizer );
}
