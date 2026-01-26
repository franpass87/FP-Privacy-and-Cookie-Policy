<?php
/**
 * Multilanguage compatibility.
 *
 * @package FP\Privacy
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy;

use FP\Privacy\Utils\Options;

/**
 * Handles compatibility with FP-Multilanguage plugin.
 */
class MultilanguageCompatibility {
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
	 * Setup compatibility hooks for WPML and FP-Multilanguage plugins.
	 *
	 * Ensures the plugins work together without conflicts:
	 * - Excludes privacy pages from automatic translation
	 * - Syncs current language between plugins
	 * - Translates policy URLs in banner links
	 *
	 * @return void
	 */
	public function setup() {
		// Check if WPML is active
		$wpml_active = function_exists( 'icl_get_languages' ) && function_exists( 'icl_get_current_language' );
		
		// Check if FP-Multilanguage is active
		$fpml_active = defined( 'FPML_VERSION' ) || class_exists( 'FP\MultiLanguage\Plugin' );

		if ( ! $wpml_active && ! $fpml_active ) {
			return; // No multilingual plugin active, skip compatibility setup
		}

		// 1. EXCLUDE PRIVACY PAGES FROM AUTOMATIC TRANSLATION
		// FP-Privacy already manages multilang internally for privacy/cookie pages
		if ( $fpml_active ) {
			\add_filter( 'fpml_skip_post', array( $this, 'exclude_privacy_pages_from_translation' ), 10, 2 );
		}

		// For WPML, exclude privacy pages from translation
		if ( $wpml_active ) {
			\add_filter( 'wpml_should_use_user_language', array( $this, 'wpml_exclude_privacy_pages' ), 10, 2 );
		}

		// 2. SYNC LOCALE WITH MULTILANGUAGE PLUGINS
		// Use current language from WPML or FP-Multilanguage for banner texts
		\add_filter( 'locale', array( $this, 'sync_locale_with_multilanguage' ), 5 );

		// 3. TRANSLATE POLICY URLs IN BANNER
		// Ensure links point to correct language version
		\add_filter( 'fp_privacy_policy_link_url', array( $this, 'translate_policy_url' ), 10, 3 );

		// 4. AUTO-CREATE PAGES FOR WPML LANGUAGES
		// Ensure privacy/cookie pages exist for all WPML languages
		if ( $wpml_active ) {
			\add_action( 'admin_init', array( $this, 'ensure_wpml_pages_exist' ), 20 );
			\add_action( 'wpml_loaded', array( $this, 'ensure_wpml_pages_exist' ), 20 );
		}
	}

	/**
	 * Exclude privacy/cookie pages from FP-Multilanguage automatic translation.
	 *
	 * @param bool $skip    Whether to skip translation.
	 * @param int  $post_id Post ID.
	 *
	 * @return bool
	 */
	public function exclude_privacy_pages_from_translation( $skip, $post_id ) {
		if ( $skip ) {
			return $skip; // Already skipped for other reasons
		}

		$options = $this->options ? $this->options->all() : array();
		$pages   = isset( $options['pages'] ) && \is_array( $options['pages'] ) ? $options['pages'] : array();

		// Extract all privacy/cookie page IDs
		foreach ( $pages as $type => $languages ) {
			if ( ! \is_array( $languages ) ) {
				continue;
			}

			foreach ( $languages as $lang => $page_id ) {
				if ( (int) $page_id === (int) $post_id ) {
					return true; // This is a privacy page, exclude it
				}
			}
		}

		return $skip;
	}

	/**
	 * Sync locale with WPML or FP-Multilanguage current language.
	 *
	 * @param string $locale Current locale.
	 *
	 * @return string
	 */
	public function sync_locale_with_multilanguage( $locale ) {
		// Priority 1: Get current language from WPML
		if ( \function_exists( 'icl_get_current_language' ) ) {
			$wpml_lang = \icl_get_current_language();
			if ( $wpml_lang && \is_string( $wpml_lang ) ) {
				// Convert WPML language code to locale
				$wpml_locale = $this->convert_wpml_lang_to_locale( $wpml_lang );
				if ( $wpml_locale ) {
					return $wpml_locale;
				}
			}
		}

		// Priority 2: Get current language from FP-Multilanguage
		if ( \function_exists( 'fpml_get_current_language' ) ) {
			$current_lang = \fpml_get_current_language();

			if ( $current_lang && \is_string( $current_lang ) ) {
				return $current_lang;
			}
		}

		return $locale;
	}

	/**
	 * Convert WPML language code to locale format.
	 *
	 * @param string $wpml_lang WPML language code (e.g., 'it', 'en').
	 * @return string|null Locale code or null if not supported.
	 */
	private function convert_wpml_lang_to_locale( $wpml_lang ) {
		$wpml_lang = strtolower( trim( $wpml_lang ) );
		
		// Common WPML language code mappings
		$mappings = array(
			'it' => 'it_IT',
			'en' => 'en_US',
			'es' => 'es_ES',
			'fr' => 'fr_FR',
			'de' => 'de_DE',
			'pt' => 'pt_PT',
		);

		if ( isset( $mappings[ $wpml_lang ] ) ) {
			return $mappings[ $wpml_lang ];
		}

		// If WPML returns a full locale, try to use it
		if ( strpos( $wpml_lang, '_' ) !== false ) {
			return $wpml_lang;
		}

		return null;
	}

	/**
	 * Exclude privacy pages from WPML translation.
	 *
	 * @param bool   $use_user_lang Whether to use user language.
	 * @param object $post          Post object.
	 *
	 * @return bool
	 */
	public function wpml_exclude_privacy_pages( $use_user_lang, $post ) {
		if ( ! $post || ! isset( $post->ID ) ) {
			return $use_user_lang;
		}

		return $this->exclude_privacy_pages_from_translation( false, $post->ID );
	}

	/**
	 * Translate policy URL to use FP-Multilanguage URL structure.
	 *
	 * @param string $url  Original URL.
	 * @param string $type Policy type (privacy|cookie).
	 * @param string $lang Language code.
	 *
	 * @return string
	 */
	public function translate_policy_url( $url, $type, $lang ) {
		if ( ! $this->options ) {
			return $url;
		}

		$options = $this->options->all();
		$pages   = isset( $options['pages'] ) && \is_array( $options['pages'] ) ? $options['pages'] : array();

		// Determine correct key
		$key = ( $type === 'privacy' ) ? 'privacy_policy_page_id' : 'cookie_policy_page_id';

		// Get page ID for requested language
		$page_id = isset( $pages[ $key ][ $lang ] ) ? (int) $pages[ $key ][ $lang ] : 0;

		if ( ! $page_id ) {
			return $url;
		}

		// Use WordPress permalink (FP-Multilanguage will add language prefix automatically)
		$permalink = \get_permalink( $page_id );

		return $permalink ?: $url;
	}

	/**
	 * Ensure privacy/cookie pages exist for all WPML languages.
	 * This is called automatically when WPML is active.
	 *
	 * @return void
	 */
	public function ensure_wpml_pages_exist() {
		if ( ! $this->options ) {
			return;
		}

		// Only run once per request to avoid performance issues
		static $ran = false;
		if ( $ran ) {
			return;
		}
		$ran = true;

		// Call ensure_pages_exist which now includes WPML languages
		if ( method_exists( $this->options, 'ensure_pages_exist' ) ) {
			$this->options->ensure_pages_exist();
		}
	}
}












