<?php
/**
 * Validation utilities.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

use FP\Privacy\Utils\Validator\BasicValidator;
use FP\Privacy\Utils\Validator\BannerValidator;
use FP\Privacy\Utils\Validator\SettingsValidator;
use FP\Privacy\Utils\Validator\ContentValidator;

/**
 * Provides reusable validation helpers.
 * 
 * This class acts as a facade, delegating to specialized validator classes
 * while maintaining backward compatibility with existing static method calls.
 */
class Validator {
	/**
	 * Normalize a boolean value.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return bool
	 */
	public static function bool( $value ): bool {
		return BasicValidator::bool( $value );
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
		return BasicValidator::int( $value, $default, $min, $max );
	}

	/**
	 * Sanitize plain text.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function text( $value ): string {
		return BasicValidator::text( $value );
	}

	/**
	 * Sanitize multi-line text allowing basic markup.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function textarea( $value ): string {
		return BasicValidator::textarea( $value );
	}

	/**
	 * Sanitize URL value.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function url( $value ): string {
		return BasicValidator::url( $value );
	}

	/**
	 * Sanitize email value.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function email( $value ): string {
		return BasicValidator::email( $value );
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
		return BasicValidator::hex_color( $value, $default );
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
		return BasicValidator::locale( $value, $default );
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
		return BasicValidator::locale_list( $values, $fallback );
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
		return BasicValidator::choice( $value, $allowed, $default );
	}

	/**
	 * Sanitize the color palette configuration.
	 *
	 * @param array<string, mixed>  $palette  Palette values.
	 * @param array<string, string> $defaults Default palette.
	 *
	 * @return array<string, string>
	 */
	public static function sanitize_palette( array $palette, array $defaults ): array {
		return BannerValidator::sanitize_palette( $palette, $defaults );
	}

	/**
	 * Sanitize banner texts per language.
	 *
	 * @param array<string, array<string, mixed>> $texts     Banner texts.
	 * @param array<int, string>                  $languages Active languages.
	 * @param array<string, string>               $defaults  Default text map.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function sanitize_banner_texts( array $texts, array $languages, array $defaults ): array {
		return BannerValidator::sanitize_banner_texts( $texts, $languages, $defaults );
	}

	/**
	 * Sanitize consent mode defaults.
	 *
	 * @param array<string, string> $values   Raw values.
	 * @param array<string, string> $defaults Default values.
	 *
	 * @return array<string, string>
	 */
	public static function sanitize_consent_mode( array $values, array $defaults ): array {
		return SettingsValidator::sanitize_consent_mode( $values, $defaults );
	}

	/**
	 * Sanitize owner and DPO fields.
	 *
	 * @param array<string, mixed> $fields Raw fields.
	 *
	 * @return array<string, string>
	 */
	public static function sanitize_owner_fields( array $fields ): array {
		return SettingsValidator::sanitize_owner_fields( $fields );
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
		return SettingsValidator::sanitize_pages( $pages, $languages );
	}

	/**
	 * Sanitize categories definition.
	 *
	 * @param array<string, mixed> $categories Raw categories.
	 * @param array<int, string>   $languages  Active languages.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function sanitize_categories( array $categories, array $languages ): array {
		return ContentValidator::sanitize_categories( $categories, $languages );
	}

	/**
	 * Sanitize services definition grouped by language.
	 *
	 * @param array<string, mixed> $services Raw services per language.
	 * @param array<int, string>   $languages Active languages.
	 *
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public static function sanitize_services( array $services, array $languages ): array {
		return ContentValidator::sanitize_services( $services, $languages );
	}

	/**
	 * Sanitize cached automatic translations.
	 *
	 * @param array<string, mixed>      $translations Cached translations.
	 * @param array<string, string>     $defaults      Default banner texts.
	 *
	 * @return array<string, mixed>
	 */
	public static function sanitize_auto_translations( array $translations, array $defaults ): array {
		return ContentValidator::sanitize_auto_translations( $translations, $defaults );
	}

}
