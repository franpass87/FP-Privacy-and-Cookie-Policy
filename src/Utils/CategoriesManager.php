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
	 * Canonical localized copy for core categories.
	 *
	 * @var array<string, array<string, array<string, string>>>
	 */
	private const CORE_CATEGORY_COPY = array(
		'necessary' => array(
			'it' => array(
				'label'       => 'Strettamente necessari',
				'description' => 'Cookie essenziali necessari al funzionamento del sito e non disattivabili.',
			),
			'en' => array(
				'label'       => 'Strictly necessary',
				'description' => 'Essential cookies required for the website to function and cannot be disabled.',
			),
		),
		'preferences' => array(
			'it' => array(
				'label'       => 'Preferenze',
				'description' => 'Memorizzano preferenze utente come lingua o localizzazione.',
			),
			'en' => array(
				'label'       => 'Preferences',
				'description' => 'Store user preferences such as language or location.',
			),
		),
		'statistics' => array(
			'it' => array(
				'label'       => 'Statistiche',
				'description' => 'Raccolgono statistiche anonime per migliorare i servizi.',
			),
			'en' => array(
				'label'       => 'Statistics',
				'description' => 'Collect anonymous statistics to improve our services.',
			),
		),
		'marketing' => array(
			'it' => array(
				'label'       => 'Marketing',
				'description' => 'Abilitano pubblicita personalizzata e tracciamento.',
			),
			'en' => array(
				'label'       => 'Marketing',
				'description' => 'Enable personalized advertising and tracking.',
			),
		),
	);
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
	 * Constructor.
	 *
	 * @param Options        $options         Options handler.
	 * @param AutoTranslator $auto_translator Auto translator.
	 */
	public function __construct( Options $options, AutoTranslator $auto_translator ) {
		$this->options         = $options;
		$this->auto_translator = $auto_translator;
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

			$result[ $key ] = $this->normalize_core_category_copy( $key, $result[ $key ], $lang );
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
	 * Normalize core category copy to avoid mixed-language labels in frontend/policies.
	 *
	 * @param string               $key      Category key.
	 * @param array<string, mixed> $category Category payload.
	 * @param string               $lang     Requested language.
	 *
	 * @return array<string, mixed>
	 */
	private function normalize_core_category_copy( string $key, array $category, string $lang ): array {
		if ( ! isset( self::CORE_CATEGORY_COPY[ $key ] ) ) {
			return $category;
		}

		$is_italian = 0 === strpos( strtolower( $lang ), 'it' );
		$bucket     = $is_italian ? 'it' : 'en';
		$canonical  = self::CORE_CATEGORY_COPY[ $key ][ $bucket ];

		$label = isset( $category['label'] ) ? Validator::text( (string) $category['label'] ) : '';
		$desc  = isset( $category['description'] ) ? Validator::textarea( (string) $category['description'] ) : '';

		if ( '' === $label || $this->is_core_category_copy( $key, $label, 'label' ) ) {
			$category['label'] = $canonical['label'];
		}

		if ( '' === $desc || $this->is_core_category_copy( $key, $desc, 'description' ) ) {
			$category['description'] = $canonical['description'];
		}

		return $category;
	}

	/**
	 * Check whether a value matches a known core category copy variant.
	 *
	 * @param string $key   Category key.
	 * @param string $value Value to test.
	 * @param string $field label|description.
	 *
	 * @return bool
	 */
	private function is_core_category_copy( string $key, string $value, string $field ): bool {
		$needle = strtolower( trim( $value ) );

		foreach ( self::CORE_CATEGORY_COPY[ $key ] as $copy ) {
			if ( ! isset( $copy[ $field ] ) ) {
				continue;
			}

			if ( strtolower( trim( (string) $copy[ $field ] ) ) === $needle ) {
				return true;
			}
		}

		return false;
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
















