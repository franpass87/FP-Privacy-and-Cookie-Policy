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
	 * Setup compatibility hooks for FP-Multilanguage plugin.
	 *
	 * Ensures the two plugins work together without conflicts:
	 * - Excludes privacy pages from automatic translation
	 * - Syncs current language between plugins
	 * - Translates policy URLs in banner links
	 *
	 * @return void
	 */
	public function setup() {
		// Check if FP-Multilanguage is active
		$fpml_active = defined( 'FPML_VERSION' ) || class_exists( 'FP\MultiLanguage\Plugin' );

		if ( ! $fpml_active ) {
			return; // FP-Multilanguage not active, skip compatibility setup
		}

		// 1. EXCLUDE PRIVACY PAGES FROM AUTOMATIC TRANSLATION
		// FP-Privacy already manages multilang internally for privacy/cookie pages
		\add_filter( 'fpml_skip_post', array( $this, 'exclude_privacy_pages_from_translation' ), 10, 2 );

		// 2. SYNC LOCALE WITH FP-MULTILANGUAGE
		// Use FP-Multilanguage's current language for banner texts
		\add_filter( 'locale', array( $this, 'sync_locale_with_multilanguage' ), 5 );

		// 3. TRANSLATE POLICY URLs IN BANNER
		// Ensure links point to correct language version
		\add_filter( 'fp_privacy_policy_link_url', array( $this, 'translate_policy_url' ), 10, 3 );
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
	 * Sync locale with FP-Multilanguage current language.
	 *
	 * @param string $locale Current locale.
	 *
	 * @return string
	 */
	public function sync_locale_with_multilanguage( $locale ) {
		// Get current language from FP-Multilanguage
		if ( \function_exists( 'fpml_get_current_language' ) ) {
			$current_lang = \fpml_get_current_language();

			if ( $current_lang && \is_string( $current_lang ) ) {
				return $current_lang;
			}
		}

		return $locale;
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
}












