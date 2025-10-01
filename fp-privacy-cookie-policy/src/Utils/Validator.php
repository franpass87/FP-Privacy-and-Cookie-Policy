<?php
/**
 * Validation utilities.
 *
 * @package FP\Privacy\Utils
 */

namespace FP\Privacy\Utils;

use const FILTER_VALIDATE_INT;
use function absint;
use function esc_url_raw;
use function filter_var;
use function is_array;
use function preg_match;
use function preg_replace;
use function rest_sanitize_boolean;
use function sanitize_email;
use function sanitize_hex_color;
use function sanitize_key;
use function sanitize_text_field;
use function str_replace;
use function strlen;
use function strpos;
use function strtolower;
use function trim;
use function wp_kses_post;
use function wp_parse_args;

/**
 * Provides reusable validation helpers.
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
		return (bool) rest_sanitize_boolean( $value );
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
		if ( null !== $value && '' !== $value ) {
			$validated = filter_var( $value, FILTER_VALIDATE_INT );
			if ( false !== $validated && null !== $validated ) {
				$value = (int) $validated;
			}
		}

		if ( ! is_int( $value ) ) {
			$value = $default;
		}

		if ( null !== $min && $value < $min ) {
			$value = $min;
		}

		if ( null !== $max && $value > $max ) {
			$value = $max;
		}

		return (int) $value;
	}

	/**
	 * Sanitize plain text.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function text( $value ): string {
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitize multi-line text allowing basic markup.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function textarea( $value ): string {
		return wp_kses_post( (string) $value );
	}

	/**
	 * Sanitize URL value.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function url( $value ): string {
		return esc_url_raw( (string) $value );
	}

	/**
	 * Sanitize email value.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function email( $value ): string {
		return sanitize_email( (string) $value );
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
		$color = sanitize_hex_color( (string) $value );

		return $color ? $color : $default;
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
		$value = sanitize_text_field( (string) $value );

		if ( '' === $value ) {
			return $default;
		}

		if ( ! preg_match( '/^[A-Za-z]{2,}([_-][A-Za-z0-9]{2,})?$/', $value ) ) {
			return $default;
		}

		return $value;
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
		$values  = is_array( $values ) ? $values : array();
		$locales = array();

		foreach ( $values as $value ) {
			$locale = self::locale( $value, '' );

			if ( '' === $locale || in_array( $locale, $locales, true ) ) {
				continue;
			}

			$locales[] = $locale;
		}

		if ( empty( $locales ) ) {
			$locales[] = self::locale( $fallback, 'en_US' );
		}

		return $locales;
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
		$value = sanitize_text_field( (string) $value );

		return in_array( $value, $allowed, true ) ? $value : $default;
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
		$palette   = wp_parse_args( $palette, $defaults );
		$sanitized = array();

		foreach ( $defaults as $key => $color ) {
			$sanitized[ $key ] = self::hex_color( $palette[ $key ] ?? $color, $color );
		}

		return $sanitized;
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
		$sanitized = array();

		foreach ( $languages as $language ) {
			$language = self::locale( $language, 'en_US' );
			$source   = isset( $texts[ $language ] ) && is_array( $texts[ $language ] ) ? $texts[ $language ] : array();

                        $sanitized[ $language ] = array(
                                'title'           => self::text( $source['title'] ?? ( $defaults['title'] ?? '' ) ),
                                'message'         => self::textarea( $source['message'] ?? ( $defaults['message'] ?? '' ) ),
                                'btn_accept'      => self::text( $source['btn_accept'] ?? ( $defaults['btn_accept'] ?? '' ) ),
                                'btn_reject'      => self::text( $source['btn_reject'] ?? ( $defaults['btn_reject'] ?? '' ) ),
                                'btn_prefs'       => self::text( $source['btn_prefs'] ?? ( $defaults['btn_prefs'] ?? '' ) ),
                                'modal_title'     => self::text( $source['modal_title'] ?? ( $defaults['modal_title'] ?? '' ) ),
                                'modal_close'     => self::text( $source['modal_close'] ?? ( $defaults['modal_close'] ?? '' ) ),
                                'modal_save'      => self::text( $source['modal_save'] ?? ( $defaults['modal_save'] ?? '' ) ),
                                'revision_notice' => self::text( $source['revision_notice'] ?? ( $defaults['revision_notice'] ?? '' ) ),
                                'toggle_locked'   => self::text( $source['toggle_locked'] ?? ( $defaults['toggle_locked'] ?? '' ) ),
                                'toggle_enabled'  => self::text( $source['toggle_enabled'] ?? ( $defaults['toggle_enabled'] ?? '' ) ),
                                'debug_label'     => self::text( $source['debug_label'] ?? ( $defaults['debug_label'] ?? '' ) ),
                                'link_policy'     => self::url( $source['link_policy'] ?? ( $defaults['link_policy'] ?? '' ) ),
                        );
		}

		return $sanitized;
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
		$allowed   = array( 'granted', 'denied' );
		$sanitized = array();

		foreach ( $defaults as $key => $default ) {
			$raw = isset( $values[ $key ] ) ? strtolower( (string) $values[ $key ] ) : $default;

			if ( ! in_array( $raw, $allowed, true ) ) {
				$raw = $default;
			}

			$sanitized[ $key ] = $raw;
		}

		return $sanitized;
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
			'org_name'      => self::text( $fields['org_name'] ),
			'vat'           => self::text( $fields['vat'] ),
			'address'       => self::textarea( $fields['address'] ),
			'dpo_name'      => self::text( $fields['dpo_name'] ),
			'dpo_email'     => self::email( $fields['dpo_email'] ),
			'privacy_email' => self::email( $fields['privacy_email'] ),
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
					$lang_key         = self::locale( $language, $languages[0] ?? 'en_US' );
					$map[ $lang_key ] = absint( $page_id );
				}
			}

			foreach ( $languages as $language ) {
				$language = self::locale( $language, 'en_US' );

				if ( ! isset( $map[ $language ] ) ) {
					$map[ $language ] = 0;
				}
			}

			$pages[ $key ] = $map;
		}

		return $pages;
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
                                'locked'      => self::bool( $category['locked'] ?? false ),
                                'services'    => array(),
                        );

                        $default_label = null;
                        if ( array_key_exists( 'default', $labels ) ) {
                                $default_label                                 = self::text( $labels['default'] );
                                $sanitized[ $key ]['label']['default']        = $default_label;
                        }

                        $default_description = null;
                        if ( array_key_exists( 'default', $descriptions ) ) {
                                $default_description                            = self::textarea( $descriptions['default'] );
                                $sanitized[ $key ]['description']['default']   = $default_description;
                        }

                        $first_label = '';
                        foreach ( $labels as $label_key => $label_value ) {
                                if ( 'default' === $label_key ) {
                                        continue;
                                }

                                $first_label = self::text( $label_value );
                                break;
                        }

                        $first_description = '';
                        foreach ( $descriptions as $description_key => $description_value ) {
                                if ( 'default' === $description_key ) {
                                        continue;
                                }

                                $first_description = self::textarea( $description_value );
                                break;
                        }

                        foreach ( $languages as $language ) {
                                $language = self::locale( $language, 'en_US' );

                                if ( array_key_exists( $language, $labels ) ) {
                                        $label = self::text( $labels[ $language ] );
                                } elseif ( null !== $default_label ) {
                                        $label = $default_label;
                                } else {
                                        $label = $first_label;
                                }

                                if ( array_key_exists( $language, $descriptions ) ) {
                                        $description = self::textarea( $descriptions[ $language ] );
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
                        $lang       = self::locale( $language, $languages[0] ?? 'en_US' );
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
			$language = self::locale( $language, 'en_US' );

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

                        if ( self::normalize_locale_token( $language ) === $normalized ) {
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
							if ( self::bool( $flag ) ) {
								$enabled = true;
								break;
							}
						}
					} else {
						$enabled = self::bool( $value );
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
			'name'              => self::text( $service['name'] ?? '' ),
			'provider'          => self::text( $service['provider'] ?? '' ),
			'purpose'           => self::textarea( $service['purpose'] ?? '' ),
			'policy_url'        => self::url( $service['policy_url'] ?? '' ),
			'retention'         => self::text( $service['retention'] ?? '' ),
			'data_collected'    => self::textarea( $service['data_collected'] ?? '' ),
			'legal_basis'       => self::text( $service['legal_basis'] ?? '' ),
			'data_transfer'     => self::textarea( $service['data_transfer'] ?? '' ),
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
			'name'        => self::text( $cookie['name'] ?? '' ),
			'domain'      => self::text( $cookie['domain'] ?? '' ),
			'duration'    => self::text( $cookie['duration'] ?? '' ),
			'description' => self::textarea( $cookie['description'] ?? '' ),
		);
	}
}
