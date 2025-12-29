<?php
/**
 * Consent state sanitizer.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Utils\Options;

/**
 * Handles sanitization and validation of consent states.
 */
class ConsentStateSanitizer {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 * @param Options $options Options handler.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Sanitize states payload.
	 * Supports both simple boolean values and EDPB 2025 sub-categories structure.
	 *
	 * @param array<string, mixed> $states States array.
	 *
	 * @return array<string, bool|array<string, mixed>>
	 */
	public function sanitize_states_payload( array $states ) {
		$sanitized = array();
		$enable_sub_categories = $this->options->get( 'enable_sub_categories', false );

		foreach ( $states as $key => $value ) {
			$clean_key = \sanitize_key( $key );

			if ( '' === $clean_key ) {
				continue;
			}

			// EDPB 2025: Support sub-categories structure if enabled and value is array.
			if ( $enable_sub_categories && is_array( $value ) && isset( $value['enabled'] ) && isset( $value['services'] ) ) {
				$sanitized[ $clean_key ] = array(
					'enabled' => $this->normalize_boolean( $value['enabled'] ),
					'services' => $this->sanitize_services_payload( $value['services'] ?? array() ),
				);
			} else {
				// Legacy format: simple boolean value.
				$sanitized[ $clean_key ] = $this->normalize_boolean( $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize services payload for sub-categories.
	 *
	 * @param array<string, mixed> $services Services array.
	 *
	 * @return array<string, bool>
	 */
	private function sanitize_services_payload( array $services ): array {
		$sanitized = array();

		foreach ( $services as $service_key => $service_value ) {
			$clean_key = \sanitize_key( $service_key );

			if ( '' === $clean_key ) {
				continue;
			}

			$sanitized[ $clean_key ] = $this->normalize_boolean( $service_value );
		}

		return $sanitized;
	}

	/**
	 * Filter out unknown consent categories from the payload.
	 * Supports both simple boolean values and EDPB 2025 sub-categories structure.
	 *
	 * @param array<string, bool|array<string, mixed>> $states   Sanitized states.
	 * @param string                                    $language Active language.
	 *
	 * @return array<string, bool|array<string, mixed>>
	 */
	public function filter_known_categories( array $states, $language ) {
		$categories = $this->options->get_categories_for_language( $language );

		if ( empty( $categories ) ) {
			return array();
		}

		$filtered = array();

		foreach ( $states as $slug => $value ) {
			if ( isset( $categories[ $slug ] ) ) {
				// EDPB 2025: If value is array with sub-categories structure, preserve it.
				if ( is_array( $value ) && isset( $value['enabled'] ) && isset( $value['services'] ) ) {
					$filtered[ $slug ] = $value;
				} else {
					$filtered[ $slug ] = (bool) $value;
				}
			}
		}

		return $filtered;
	}

	/**
	 * Convert mixed value into a strict boolean following common string conventions.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return bool
	 */
	public function normalize_boolean( $value ) {
		if ( \is_bool( $value ) ) {
			return $value;
		}

		if ( \is_numeric( $value ) ) {
			return (bool) (int) $value;
		}

		if ( \is_string( $value ) ) {
			$value = strtolower( trim( $value ) );

			// Support legacy payloads that stored consent states as strings such as
			// "granted"/"denied" in addition to generic truthy tokens.
			$truthy = array( 'true', '1', 'yes', 'on', 'granted', 'allow', 'allowed', 'enabled', 'accept', 'accepted' );
			if ( in_array( $value, $truthy, true ) ) {
				return true;
			}

			$falsy = array( 'false', '0', 'no', 'off', 'denied', 'deny', 'disabled', 'disallow', 'disallowed', 'rejected', 'reject', 'revoked' );
			if ( in_array( $value, $falsy, true ) ) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Ensure locked consent categories remain granted.
	 * Supports both simple boolean values and EDPB 2025 sub-categories structure.
	 *
	 * @param array<string, bool|array<string, mixed>> $states   Sanitized states.
	 * @param string                                    $language Active language.
	 *
	 * @return array<string, bool|array<string, mixed>>
	 */
	public function enforce_locked_categories( array $states, $language ) {
		$categories = $this->options->get_categories_for_language( $language );
		$enable_sub_categories = $this->options->get( 'enable_sub_categories', false );

		foreach ( $categories as $slug => $category ) {
			if ( ! empty( $category['locked'] ) ) {
				// EDPB 2025: If sub-categories enabled, preserve structure but set enabled=true and all services=true.
				if ( $enable_sub_categories && isset( $states[ $slug ] ) && is_array( $states[ $slug ] ) && isset( $states[ $slug ]['services'] ) ) {
					$states[ $slug ]['enabled'] = true;
					foreach ( $states[ $slug ]['services'] as $service_key => $service_value ) {
						$states[ $slug ]['services'][ $service_key ] = true;
					}
				} else {
					$states[ $slug ] = true;
				}
			}
		}

		return $states;
	}
}















