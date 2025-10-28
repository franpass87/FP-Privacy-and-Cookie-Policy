<?php
/**
 * Automatic translation service.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

use FP\Privacy\Interfaces\AutoTranslatorInterface;

/**
 * Handles automatic translation and caching for banner texts and categories.
 */
class AutoTranslator implements AutoTranslatorInterface {
	/**
	 * Translation cache.
	 *
	 * @var array<string, mixed>
	 */
	private $cache;

	/**
	 * Translator instance.
	 *
	 * @var Translator
	 */
	private $translator;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $cache      Translation cache.
	 * @param Translator|null      $translator Translator instance.
	 */
	public function __construct( array $cache = array(), ?Translator $translator = null ) {
		$this->cache      = $cache;
		$this->translator = $translator ?? new Translator();
	}

	/**
	 * Get translation cache.
	 *
	 * @return array<string, mixed>
	 */
	public function get_cache() {
		return $this->cache;
	}

	/**
	 * Translate banner texts to a requested language with caching.
	 *
	 * @param array<string, string> $source      Source banner texts.
	 * @param string                $source_lang Source language code.
	 * @param string                $target_lang Target language code.
	 *
	 * @return array<string, string> Translated texts or source if translation fails.
	 */
	public function translate_banner_texts( array $source, $source_lang, $target_lang ) {
		$source_lang = Validator::locale( $source_lang, 'en_US' );
		$target_lang = Validator::locale( $target_lang, $source_lang );

		if ( $source_lang === $target_lang ) {
			return $source;
		}

	$banner_cache = isset( $this->cache['banner'] ) && \is_array( $this->cache['banner'] ) ? $this->cache['banner'] : array();
	$encoded      = \wp_json_encode( $source );
	$hash         = \md5( false !== $encoded ? $encoded : serialize( $source ) );

		if ( isset( $banner_cache[ $target_lang ] ) && \is_array( $banner_cache[ $target_lang ] ) ) {
			$cached = $banner_cache[ $target_lang ];

			if ( isset( $cached['hash'], $cached['texts'] ) && $cached['hash'] === $hash && \is_array( $cached['texts'] ) ) {
				return $cached['texts'];
			}
		}

		if ( ! $this->translator ) {
			return $source;
		}

		$translated = $this->translator->translate_banner_texts( $source, $source_lang, $target_lang );

		if ( empty( $translated ) ) {
			return $source;
		}

		$sanitized = Validator::sanitize_banner_texts( array( $target_lang => $translated ), array( $target_lang ), $source );
		$result    = $sanitized[ $target_lang ] ?? array();

		if ( empty( $result ) ) {
			return $source;
		}

		$banner_cache[ $target_lang ] = array(
			'hash'  => $hash,
			'texts' => $result,
		);

		$this->cache['banner'] = $banner_cache;

		return $result;
	}

	/**
	 * Translate categories metadata with caching.
	 *
	 * @param array<string, array<string, mixed>> $categories  Categories metadata.
	 * @param string                              $source_lang Source language.
	 * @param string                              $target_lang Target language.
	 *
	 * @return array<string, array<string, mixed>> Translated categories.
	 */
	public function translate_categories( array $categories, $source_lang, $target_lang ) {
		$source_lang = Validator::locale( $source_lang, 'en_US' );
		$target_lang = Validator::locale( $target_lang, $source_lang );

		if ( $source_lang === $target_lang || empty( $categories ) ) {
			return $categories;
		}

		$categories_cache = isset( $this->cache['categories'] ) && \is_array( $this->cache['categories'] ) ? $this->cache['categories'] : array();
		$hash_payload     = array();

		foreach ( $categories as $slug => $meta ) {
			$key = \sanitize_key( $slug );

			if ( '' === $key ) {
				continue;
			}

			$hash_payload[ $key ] = array(
				'label'       => isset( $meta['label'] ) ? (string) $meta['label'] : '',
				'description' => isset( $meta['description'] ) ? (string) $meta['description'] : '',
			);
		}

	if ( empty( $hash_payload ) ) {
		return $categories;
	}

	$encoded = \wp_json_encode( $hash_payload );
	$hash    = \md5( false !== $encoded ? $encoded : serialize( $hash_payload ) );

		if ( isset( $categories_cache[ $target_lang ] ) && \is_array( $categories_cache[ $target_lang ] ) ) {
			$cached = $categories_cache[ $target_lang ];

			if ( isset( $cached['hash'], $cached['items'] ) && $cached['hash'] === $hash && \is_array( $cached['items'] ) ) {
				foreach ( $categories as $slug => $meta ) {
					$key = \sanitize_key( $slug );

					if ( '' === $key || ! isset( $cached['items'][ $key ] ) ) {
						continue;
					}

					$entry = $cached['items'][ $key ];

					if ( isset( $entry['label'] ) ) {
						$categories[ $slug ]['label'] = $entry['label'];
					}

					if ( isset( $entry['description'] ) ) {
						$categories[ $slug ]['description'] = $entry['description'];
					}
				}

				return $categories;
			}
		}

		if ( ! $this->translator ) {
			return $categories;
		}

		$cache_items = array();

		foreach ( $categories as $slug => $meta ) {
			$label       = isset( $meta['label'] ) ? (string) $meta['label'] : '';
			$description = isset( $meta['description'] ) ? (string) $meta['description'] : '';

			if ( '' !== \trim( $label ) ) {
				$label = $this->translator->translate_string( $label, $source_lang, $target_lang );
			}

			if ( '' !== \trim( $description ) ) {
				$description = $this->translator->translate_string( $description, $source_lang, $target_lang );
			}

			$label       = Validator::text( $label );
			$description = Validator::textarea( $description );

			$categories[ $slug ]['label']       = $label;
			$categories[ $slug ]['description'] = $description;

			$key = \sanitize_key( $slug );

			if ( '' === $key ) {
				continue;
			}

			$cache_items[ $key ] = array(
				'label'       => $label,
				'description' => $description,
			);
		}

		$categories_cache[ $target_lang ] = array(
			'hash'  => $hash,
			'items' => $cache_items,
		);

		$this->cache['categories'] = $categories_cache;

		return $categories;
	}
}