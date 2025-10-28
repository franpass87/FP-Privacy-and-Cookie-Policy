<?php
/**
 * Auto translator interface.
 *
 * @package FP\Privacy\Interfaces
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Interfaces;

/**
 * Interface for automatic translation services.
 */
interface AutoTranslatorInterface {
	/**
	 * Get translation cache.
	 *
	 * @return array<string, mixed>
	 */
	public function get_cache();

	/**
	 * Translate banner texts to a requested language with caching.
	 *
	 * @param array<string, string> $source      Source banner texts.
	 * @param string                $source_lang Source language code.
	 * @param string                $target_lang Target language code.
	 *
	 * @return array<string, string> Translated texts or source if translation fails.
	 */
	public function translate_banner_texts( array $source, $source_lang, $target_lang );

	/**
	 * Translate categories metadata with caching.
	 *
	 * @param array<string, array<string, mixed>> $categories  Categories metadata.
	 * @param string                              $source_lang Source language.
	 * @param string                              $target_lang Target language.
	 *
	 * @return array<string, array<string, mixed>> Translated categories.
	 */
	public function translate_categories( array $categories, $source_lang, $target_lang );
}