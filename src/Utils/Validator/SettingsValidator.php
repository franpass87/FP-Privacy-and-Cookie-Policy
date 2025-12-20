<?php
/**
 * Settings validation utilities.
 *
 * @package FP\Privacy\Utils\Validator
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils\Validator;

use FP\Privacy\Domain\ValueObjects\ConsentModeDefaults;
use function wp_parse_args;
use function strtolower;
use function in_array;
use function absint;

/**
 * Provides settings-related validation and sanitization methods.
 */
class SettingsValidator {
	/**
	 * Sanitize consent mode defaults.
	 *
	 * Uses ConsentModeDefaults value object internally for validation and sanitization.
	 *
	 * @param array<string, string> $values   Raw values.
	 * @param array<string, string> $defaults Default values.
	 *
	 * @return array<string, string>
	 */
	public static function sanitize_consent_mode( array $values, array $defaults ): array {
		// Merge with defaults first.
		$consent_mode_data = wp_parse_args( $values, $defaults );
		
		// Use ConsentModeDefaults value object for validation and sanitization.
		$consent_mode = ConsentModeDefaults::from_array( $consent_mode_data );
		
		// Return as array for backward compatibility.
		return $consent_mode->to_array();
	}

	/**
	 * Sanitize owner and DPO fields.
	 *
	 * @param array<string, mixed> $fields Raw fields.
	 *
	 * @return array<string, string>
	 */
	public static function sanitize_owner_fields( array $fields ): array {
		$fields = wp_parse_args(
			$fields,
			array(
				'org_name'      => '',
				'vat'           => '',
				'address'       => '',
				'dpo_name'      => '',
				'dpo_email'     => '',
				'privacy_email' => '',
			)
		);

		return array(
			'org_name'      => BasicValidator::text( $fields['org_name'] ),
			'vat'           => BasicValidator::text( $fields['vat'] ),
			'address'       => BasicValidator::textarea( $fields['address'] ),
			'dpo_name'      => BasicValidator::text( $fields['dpo_name'] ),
			'dpo_email'     => BasicValidator::email( $fields['dpo_email'] ),
			'privacy_email' => BasicValidator::email( $fields['privacy_email'] ),
		);
	}

	/**
	 * Sanitize pages mapping per language.
	 *
	 * @param array<string, mixed> $pages     Raw pages mapping.
	 * @param array<int, string>   $languages Active languages.
	 *
	 * @return array<string, array<string, int>>
	 */
	public static function sanitize_pages( array $pages, array $languages ): array {
		$defaults = array(
			'privacy_policy_page_id' => array(),
			'cookie_policy_page_id'  => array(),
		);

		$pages = wp_parse_args( $pages, $defaults );

		foreach ( $defaults as $key => $_default ) {
			$map = array();

			if ( isset( $pages[ $key ] ) && is_array( $pages[ $key ] ) ) {
				foreach ( $pages[ $key ] as $language => $page_id ) {
					$lang_key         = BasicValidator::locale( $language, $languages[0] ?? 'en_US' );
					$map[ $lang_key ] = absint( $page_id );
				}
			}

			foreach ( $languages as $language ) {
				$language = BasicValidator::locale( $language, 'en_US' );

				if ( ! isset( $map[ $language ] ) ) {
					$map[ $language ] = 0;
				}
			}

			$pages[ $key ] = $map;
		}

		return $pages;
	}
}
















