<?php
/**
 * Language normalizer interface.
 *
 * @package FP\Privacy\Interfaces
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Interfaces;

/**
 * Interface for language code normalization.
 */
interface LanguageNormalizerInterface {
	/**
	 * Set active languages.
	 *
	 * @param array<int, string> $languages Active languages.
	 *
	 * @return void
	 */
	public function set_languages( array $languages );

	/**
	 * Get active languages.
	 *
	 * @return array<int, string>
	 */
	public function get_languages();

	/**
	 * Normalize locale against active languages.
	 *
	 * @param string $locale Raw locale.
	 *
	 * @return string
	 */
	public function normalize( $locale );
}