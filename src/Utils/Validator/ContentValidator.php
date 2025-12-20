<?php
/**
 * Content validation utilities.
 *
 * @package FP\Privacy\Utils\Validator
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils\Validator;

use function sanitize_key;
use function str_replace;
use function strtolower;
use function trim;
use function preg_replace;
use function strlen;
use function strpos;
use function in_array;
use function is_array;
use function sanitize_text_field;

/**
 * Provides content-related validation and sanitization methods (categories, services, translations).
 */
class ContentValidator {
	/**
	 * Sanitize categories definition.
	 *
	 * @param array<string, mixed> $categories Raw categories.
	 * @param array<int, string>   $languages  Active languages.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function sanitize_categories( array $categories, array $languages ): array {
		$sanitized = array();

		foreach ( $categories as $slug => $category ) {
			$key = sanitize_key( $slug );

			if ( '' === $key ) {
				continue;
			}

			$category = is_array( $category ) ? $category : array();

			$labels       = isset( $category['label'] ) && is_array( $category['label'] ) ? $category['label'] : array();
			$descriptions = isset( $category['description'] ) && is_array( $category['description'] ) ? $category['description'] : array();
			$services     = isset( $category['services'] ) && is_array( $category['services'] ) ? $category['services'] : array();

			$sanitized[ $key ] = array(
				'label'       => array(),
				'description' => array(),
				'locked'      => BasicValidator::bool( $category['locked'] ?? false ),
				'services'    => array(),
			);

			$default_label = null;
			if ( array_key_exists( 'default', $labels ) ) {
				$default_label                                 = BasicValidator::text( $labels['default'] );
				$sanitized[ $key ]['label']['default']        = $default_label;
			}

			$default_description = null;
			if ( array_key_exists( 'default', $descriptions ) ) {
				$default_description                            = BasicValidator::textarea( $descriptions['default'] );
				$sanitized[ $key ]['description']['default']   = $default_description;
			}

			$first_label = '';
			foreach ( $labels as $label_key => $label_value ) {
				if ( 'default' === $label_key ) {
					continue;
				}

				$first_label = BasicValidator::text( $label_value );
				break;
			}

			$first_description = '';
			foreach ( $descriptions as $description_key => $description_value ) {
				if ( 'default' === $description_key ) {
					continue;
				}

				$first_description = BasicValidator::textarea( $description_value );
				break;
			}

			foreach ( $languages as $language ) {
				$language = BasicValidator::locale( $language, 'en_US' );

				if ( array_key_exists( $language, $labels ) ) {
					$label = BasicValidator::text( $labels[ $language ] );
				} elseif ( null !== $default_label ) {
					$label = $default_label;
				} else {
					$label = $first_label;
				}

				if ( array_key_exists( $language, $descriptions ) ) {
					$description = BasicValidator::textarea( $descriptions[ $language ] );
				} elseif ( null !== $default_description ) {
					$description = $default_description;
				} else {
					$description = $first_description;
				}

				$sanitized[ $key ]['label'][ $language ]       = $label;
				$sanitized[ $key ]['description'][ $language ] = $description;
			}

			$sanitized[ $key ]['services'] = self::sanitize_services( $services, $languages );
		}

		return $sanitized;
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
		$sanitized = array();

		foreach ( $services as $language => $entries ) {
			$lang       = BasicValidator::locale( $language, $languages[0] ?? 'en_US' );
			$is_default = 'default' === $language || 'default' === $lang;

			if ( ! $is_default && ! in_array( $lang, $languages, true ) ) {
				$mapped = self::match_locale_to_active_languages( $lang, $languages );

				if ( '' === $mapped ) {
					continue;
				}

				$lang = $mapped;
			}

			if ( ! is_array( $entries ) ) {
				continue;
			}

			$key = $is_default ? 'default' : $lang;

			foreach ( $entries as $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}

				$sanitized[ $key ][] = self::sanitize_service_entry( $entry );
			}
		}

		foreach ( $languages as $language ) {
			$language = BasicValidator::locale( $language, 'en_US' );

			if ( ! isset( $sanitized[ $language ] ) ) {
				$sanitized[ $language ] = array();
			}
		}

		if ( isset( $sanitized['default'] ) ) {
			$sanitized['default'] = array_values( $sanitized['default'] );
		}

		return $sanitized;
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
		$sanitized = array();

		if ( isset( $translations['banner'] ) && is_array( $translations['banner'] ) ) {
			foreach ( $translations['banner'] as $locale => $payload ) {
				$locale = BasicValidator::locale( $locale, '' );

				if ( '' === $locale || ! is_array( $payload ) ) {
					continue;
				}

				$hash  = isset( $payload['hash'] ) ? BasicValidator::text( $payload['hash'] ) : '';
				$texts = isset( $payload['texts'] ) && is_array( $payload['texts'] ) ? $payload['texts'] : array();
				$map   = BannerValidator::sanitize_banner_texts( array( $locale => $texts ), array( $locale ), $defaults );

				$sanitized['banner'][ $locale ] = array(
					'hash'  => $hash,
					'texts' => $map[ $locale ],
				);
			}
		}

		if ( isset( $translations['categories'] ) && is_array( $translations['categories'] ) ) {
			foreach ( $translations['categories'] as $locale => $payload ) {
				$locale = BasicValidator::locale( $locale, '' );

				if ( '' === $locale || ! is_array( $payload ) ) {
					continue;
				}

				$hash  = isset( $payload['hash'] ) ? BasicValidator::text( $payload['hash'] ) : '';
				$items = array();

				if ( isset( $payload['items'] ) && is_array( $payload['items'] ) ) {
					foreach ( $payload['items'] as $slug => $entry ) {
						$key = sanitize_key( $slug );

						if ( '' === $key || ! is_array( $entry ) ) {
							continue;
						}

						$items[ $key ] = array(
							'label'       => BasicValidator::text( $entry['label'] ?? '' ),
							'description' => BasicValidator::textarea( $entry['description'] ?? '' ),
						);
					}
				}

				$sanitized['categories'][ $locale ] = array(
					'hash'  => $hash,
					'items' => $items,
				);
			}
		}

		return $sanitized;
	}

	/**
	 * Attempt to match an arbitrary locale string against active languages.
	 *
	 * @param string              $locale    Candidate locale.
	 * @param array<int, string>  $languages Active languages.
	 *
	 * @return string
	 */
	private static function match_locale_to_active_languages( string $locale, array $languages ): string {
		$normalized = self::normalize_locale_token( $locale );

		if ( '' === $normalized ) {
			return '';
		}

		foreach ( $languages as $language ) {
			if ( '' === $language ) {
				continue;
			}

			$candidate = self::normalize_locale_token( $language );

			if ( $candidate === $normalized ) {
				return $language;
			}

			if ( str_replace( '_', '', $candidate ) === $normalized ) {
				return $language;
			}
		}

		if ( strlen( $normalized ) === 2 ) {
			foreach ( $languages as $language ) {
				if ( '' === $language ) {
					continue;
				}

				$candidate = self::normalize_locale_token( $language );

				if ( 0 === strpos( $candidate, $normalized . '_' ) ) {
					return $language;
				}
			}
		}

		return '';
	}

	/**
	 * Normalize locale token for safe comparisons.
	 *
	 * @param string $locale Raw locale value.
	 *
	 * @return string
	 */
	private static function normalize_locale_token( string $locale ): string {
		$locale = str_replace( '-', '_', strtolower( trim( $locale ) ) );

		return preg_replace( '/[^a-z0-9_]/', '', $locale ) ?? '';
	}

	/**
	 * Sanitize single service entry.
	 *
	 * @param array<string, mixed> $service Service definition.
	 *
	 * @return array<string, mixed>
	 */
	private static function sanitize_service_entry( array $service ): array {
		$cookies = array();

		if ( isset( $service['cookies'] ) && is_array( $service['cookies'] ) ) {
			foreach ( $service['cookies'] as $cookie ) {
				if ( ! is_array( $cookie ) ) {
					continue;
				}

				$cookies[] = self::sanitize_cookie_entry( $cookie );
			}
		}

		$signals = array();

		if ( isset( $service['uses_consent_mode'] ) && is_array( $service['uses_consent_mode'] ) ) {
			$allowed = array( 'analytics_storage', 'ad_storage', 'ad_user_data', 'ad_personalization', 'functionality_storage', 'personalization_storage', 'security_storage' );

			foreach ( $service['uses_consent_mode'] as $key => $value ) {
				$candidate = '';

				if ( is_string( $key ) && '' !== $key && ! is_numeric( $key ) ) {
					$candidate = sanitize_text_field( $key );

					if ( is_array( $value ) ) {
						$enabled = false;

						foreach ( $value as $flag ) {
							if ( BasicValidator::bool( $flag ) ) {
								$enabled = true;
								break;
							}
						}
					} else {
						$enabled = BasicValidator::bool( $value );
					}

					if ( ! $enabled ) {
						continue;
					}
				} else {
					$candidate = sanitize_text_field( (string) $value );
				}

				if ( '' === $candidate ) {
					continue;
				}

				if ( in_array( $candidate, $allowed, true ) && ! in_array( $candidate, $signals, true ) ) {
					$signals[] = $candidate;
				}
			}
		}

		return array(
			'key'               => sanitize_key( $service['key'] ?? '' ),
			'name'              => BasicValidator::text( $service['name'] ?? '' ),
			'provider'          => BasicValidator::text( $service['provider'] ?? '' ),
			'purpose'           => BasicValidator::textarea( $service['purpose'] ?? '' ),
			'policy_url'        => BasicValidator::url( $service['policy_url'] ?? '' ),
			'retention'         => BasicValidator::text( $service['retention'] ?? '' ),
			'data_collected'    => BasicValidator::textarea( $service['data_collected'] ?? '' ),
			'legal_basis'       => BasicValidator::text( $service['legal_basis'] ?? '' ),
			'data_transfer'     => BasicValidator::textarea( $service['data_transfer'] ?? '' ),
			'cookies'           => $cookies,
			'uses_consent_mode' => $signals,
		);
	}

	/**
	 * Sanitize individual cookie entry for a service.
	 *
	 * @param array<string, mixed> $cookie Cookie definition.
	 *
	 * @return array<string, string>
	 */
	private static function sanitize_cookie_entry( array $cookie ): array {
		return array(
			'name'        => BasicValidator::text( $cookie['name'] ?? '' ),
			'domain'      => BasicValidator::text( $cookie['domain'] ?? '' ),
			'duration'    => BasicValidator::text( $cookie['duration'] ?? '' ),
			'description' => BasicValidator::textarea( $cookie['description'] ?? '' ),
		);
	}
}
















