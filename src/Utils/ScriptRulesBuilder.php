<?php
/**
 * Script rules builder.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

use FP\Privacy\Integrations\DetectorRegistry;

/**
 * Handles building of script blocking rules defaults and presets.
 */
class ScriptRulesBuilder {
	/**
	 * Build empty script rule set for a language based on categories metadata.
	 *
	 * @param array<string, mixed>           $categories Categories metadata.
	 * @param array<int, array<string, mixed>> $services   Snapshot services.
	 *
	 * @return array<string, array<string, array<int, string>>>
	 */
	public static function build_language_defaults( $categories, $services ) {
		$defaults       = array();
		$snapshot_rules = self::collect_presets_by_category( $services );

		foreach ( $categories as $slug => $meta ) {
			$defaults[ $slug ] = ScriptRulesSanitizer::normalize_entry( array() );

			if ( isset( $snapshot_rules[ $slug ] ) ) {
				$defaults[ $slug ] = ScriptRulesMerger::merge_with_defaults( $defaults[ $slug ], $snapshot_rules[ $slug ] );
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
	public static function collect_presets_by_category( array $services ) {
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
				$category_rules[ $category ] = ScriptRulesSanitizer::normalize_entry( array() );
			}

			$category_rules[ $category ] = ScriptRulesMerger::merge_with_defaults( $category_rules[ $category ], $presets[ $slug ] );
		}

		return $category_rules;
	}
}















