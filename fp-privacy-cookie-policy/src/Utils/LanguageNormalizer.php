<?php
/**
 * Language normalization utility.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

/**
 * Handles language code normalization and validation.
 */
class LanguageNormalizer {
	/**
	 * Active languages.
	 *
	 * @var array<int, string>
	 */
	private $languages;

	/**
	 * Constructor.
	 *
	 * @param array<int, string> $languages Active languages.
	 */
	public function __construct( array $languages = array() ) {
		$this->languages = $languages;
	}

	/**
	 * Set active languages.
	 *
	 * @param array<int, string> $languages Active languages.
	 *
	 * @return void
	 */
	public function set_languages( array $languages ) {
		$this->languages = $languages;
	}

	/**
	 * Get active languages.
	 *
	 * @return array<int, string>
	 */
	public function get_languages() {
		return $this->languages;
	}

	/**
	 * Normalize locale against active languages.
	 *
	 * @param string $locale Raw locale.
	 *
	 * @return string
	 */
	public function normalize( $locale ) {
		if ( empty( $this->languages ) ) {
			return Validator::locale( $locale, 'en_US' );
		}

		$primary = $this->languages[0];
		$locale  = Validator::locale( $locale, $primary );

		if ( in_array( $locale, $this->languages, true ) ) {
			return $locale;
		}

		$matched = $this->match_alias( $locale );

		if ( '' !== $matched ) {
			return $matched;
		}

		return $primary;
	}

	/**
	 * Attempt to match locale variations against configured languages.
	 *
	 * @param string $locale Requested locale.
	 *
	 * @return string
	 */
	private function match_alias( $locale ) {
		$normalized = $this->normalize_token( $locale );

		if ( '' === $normalized ) {
			return '';
		}

		foreach ( $this->languages as $language ) {
			if ( '' === $language ) {
				continue;
			}

			$candidate = $this->normalize_token( $language );

			if ( $candidate === $normalized ) {
				return $language;
			}

			if ( \str_replace( '_', '', $candidate ) === $normalized ) {
				return $language;
			}
		}

		$separator = \strpos( $normalized, '_' );
		$root      = false !== $separator ? \substr( $normalized, 0, $separator ) : $normalized;

		if ( '' === $root ) {
			return '';
		}

		foreach ( $this->languages as $language ) {
			if ( '' === $language ) {
				continue;
			}

			$candidate = $this->normalize_token( $language );

			if ( $candidate === $root || 0 === \strpos( $candidate, $root . '_' ) ) {
				return $language;
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
	private function normalize_token( $locale ) {
		$locale = \strtolower( \trim( (string) $locale ) );
		$locale = \str_replace( '-', '_', $locale );

		return \preg_replace( '/[^a-z0-9_]/', '', $locale ) ?? '';
	}
}