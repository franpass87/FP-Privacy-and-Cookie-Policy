<?php
/**
 * Categories manager.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

/**
 * Manages cookie categories and their services.
 */
class CategoriesManager {
	/**
	 * Options handler reference.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Auto translator.
	 *
	 * @var AutoTranslator
	 */
	private $auto_translator;

	/**
	 * Language normalizer.
	 *
	 * @var LanguageNormalizer
	 */
	private $language_normalizer;

	/**
	 * Constructor.
	 *
	 * @param Options            $options            Options handler.
	 * @param AutoTranslator     $auto_translator    Auto translator.
	 * @param LanguageNormalizer $language_normalizer Language normalizer.
	 */
	public function __construct( Options $options, AutoTranslator $auto_translator, LanguageNormalizer $language_normalizer ) {
		$this->options            = $options;
		$this->auto_translator    = $auto_translator;
		$this->language_normalizer = $language_normalizer;
	}

	/**
	 * Get categories for the requested language.
	 *
	 * @param string $lang Locale.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_categories_for_language( $lang ) {
		$languages = $this->options->get_languages();
		$primary   = $languages[0] ?? 'en_US';
		$requested = Validator::locale( $lang, $primary );
		$lang      = $this->options->normalize_language( $requested );
		$fallback  = $primary;
		$result    = array();

		$all_options = $this->options->all();
		$categories  = $all_options['categories'] ?? array();

		foreach ( $categories as $key => $category ) {
			$label = '';
			if ( isset( $category['label'][ $lang ] ) && '' !== $category['label'][ $lang ] ) {
				$label = $category['label'][ $lang ];
			} elseif ( isset( $category['label']['default'] ) ) {
				$label = $category['label']['default'];
			} elseif ( isset( $category['label'][ $fallback ] ) ) {
				$label = $category['label'][ $fallback ];
			}

			$description = '';
			if ( isset( $category['description'][ $lang ] ) && '' !== $category['description'][ $lang ] ) {
				$description = $category['description'][ $lang ];
			} elseif ( isset( $category['description']['default'] ) ) {
				$description = $category['description']['default'];
			} elseif ( isset( $category['description'][ $fallback ] ) ) {
				$description = $category['description'][ $fallback ];
			}

			$services_map = isset( $category['services'] ) && \is_array( $category['services'] ) ? $category['services'] : array();
			$services     = $this->resolve_services_for_language( $services_map, $lang, $fallback );

			$result[ $key ] = array(
				'label'       => $label,
				'description' => $description,
				'locked'      => ! empty( $category['locked'] ),
				'services'    => $services,
			);
		}

		if ( $requested !== $lang ) {
			$translated = $this->auto_translator->translate_categories( $result, $lang, $requested );

			// Update cache if translation occurred
			$new_cache = $this->auto_translator->get_cache();
			$all_options = $this->options->all();
			if ( $new_cache !== ( $all_options['auto_translations'] ?? array() ) ) {
				$this->options->set( array( 'auto_translations' => $new_cache ) );
			}

			return $translated;
		}

		return $result;
	}

	/**
	 * Resolve services list for a given language with fallbacks.
	 *
	 * @param array<string|int, mixed> $services_map Raw services map.
	 * @param string                   $lang         Requested language code.
	 * @param string                   $fallback     Fallback language code.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function resolve_services_for_language( array $services_map, $lang, $fallback ) {
		if ( empty( $services_map ) ) {
			return array();
		}

		// Legacy data may store services as a plain list without language keys.
		if ( array_values( $services_map ) === $services_map ) {
			return $this->normalize_services_list( $services_map );
		}

		$candidates = array( $lang );

		if ( 'default' !== $lang ) {
			$candidates[] = 'default';
		}

		if ( $fallback && ! in_array( $fallback, $candidates, true ) ) {
			$candidates[] = $fallback;
		}

		foreach ( $candidates as $code ) {
			if ( isset( $services_map[ $code ] ) && \is_array( $services_map[ $code ] ) && ! empty( $services_map[ $code ] ) ) {
				return $this->normalize_services_list( $services_map[ $code ] );
			}
		}

		return array();
	}

	/**
	 * Normalize a list of service definitions.
	 *
	 * @param mixed $services Raw services list.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function normalize_services_list( $services ) {
		if ( ! \is_array( $services ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $services as $service ) {
			if ( \is_array( $service ) ) {
				$normalized[] = $service;
			}
		}

		return $normalized;
	}
}
















