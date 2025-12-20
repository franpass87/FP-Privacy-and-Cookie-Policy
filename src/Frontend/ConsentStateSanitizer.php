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
	 *
	 * @param array<string, mixed> $states States array.
	 *
	 * @return array<string, bool>
	 */
	public function sanitize_states_payload( array $states ) {
		$sanitized = array();

		foreach ( $states as $key => $value ) {
			$clean_key = \sanitize_key( $key );

			if ( '' === $clean_key ) {
				continue;
			}

			$sanitized[ $clean_key ] = $this->normalize_boolean( $value );
		}

		return $sanitized;
	}

	/**
	 * Filter out unknown consent categories from the payload.
	 *
	 * @param array<string, bool> $states   Sanitized states.
	 * @param string              $language Active language.
	 *
	 * @return array<string, bool>
	 */
	public function filter_known_categories( array $states, $language ) {
		$categories = $this->options->get_categories_for_language( $language );

		if ( empty( $categories ) ) {
			return array();
		}

		$filtered = array();

		foreach ( $states as $slug => $value ) {
			if ( isset( $categories[ $slug ] ) ) {
				$filtered[ $slug ] = (bool) $value;
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
	 *
	 * @param array<string, bool> $states   Sanitized states.
	 * @param string              $language Active language.
	 *
	 * @return array<string, bool>
	 */
	public function enforce_locked_categories( array $states, $language ) {
		$categories = $this->options->get_categories_for_language( $language );

		foreach ( $categories as $slug => $category ) {
			if ( ! empty( $category['locked'] ) ) {
				$states[ $slug ] = true;
			}
		}

		return $states;
	}
}















