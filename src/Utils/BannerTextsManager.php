<?php
/**
 * Banner texts manager.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

/**
 * Manages banner texts and translations.
 */
class BannerTextsManager {
	/**
	 * Options handler reference.
	 *
	 * @var Options
	 */
	private $options;

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
	 * @param LanguageNormalizer $language_normalizer Language normalizer.
	 */
	public function __construct( Options $options, LanguageNormalizer $language_normalizer ) {
		$this->options            = $options;
		$this->language_normalizer = $language_normalizer;
	}

	/**
	 * Get banner text for a specific language.
	 *
	 * @param string $lang Language code.
	 *
	 * @return array<string, string>
	 */
	public function get_banner_text( $lang ) {
		// Auto-detect user language if not specified
		if ( empty( $lang ) ) {
			$lang = $this->detect_user_language();
		}

		// Always return translated texts in real-time to ensure proper localization
		$languages = $this->options->get_languages();
		$primary   = $languages[0] ?? 'en_US';
		
		// Normalize the requested language, but preserve English/Italian if detected
		// This ensures that even if English is not in active languages, we still show English texts
		$detected_lang = $lang; // Keep original detected language
		$requested = Validator::locale( $lang, $primary );
		
		// If the detected language is English but not in active languages, 
		// still use English texts (important for WPML compatibility)
		if ( ( $detected_lang === 'en_US' || strpos( strtolower( $detected_lang ), 'en' ) === 0 ) 
			&& ! in_array( 'en_US', $languages, true ) ) {
			$requested = 'en_US';
		}
		
		// Similarly for Italian
		if ( ( $detected_lang === 'it_IT' || strpos( strtolower( $detected_lang ), 'it' ) === 0 ) 
			&& ! in_array( 'it_IT', $languages, true ) ) {
			$requested = 'it_IT';
		}

		// If the requested language is Italian, use hardcoded Italian translations
		if ( $requested === 'it_IT' || $this->options->normalize_language( $requested ) === 'it_IT' ) {
			$italian_translations = $this->get_hardcoded_italian_translations();

			// Check if there are custom texts saved for this language
			$texts = $this->options->all()['banner_texts'] ?? array();
			if ( isset( $texts[ $requested ] ) && \is_array( $texts[ $requested ] ) ) {
				// Merge custom texts with Italian translations (custom texts take priority)
				return array_merge( $italian_translations, $texts[ $requested ] );
			}

			// Check normalized language
			$normalized = $this->options->normalize_language( $requested );
			if ( isset( $texts[ $normalized ] ) && \is_array( $texts[ $normalized ] ) && $normalized !== $requested ) {
				// Merge with Italian translations
				return array_merge( $italian_translations, $texts[ $normalized ] );
			}

			// Return hardcoded Italian translations
			return $italian_translations;
		}

		// If the requested language is English, use hardcoded English translations
		if ( $requested === 'en_US' || $this->options->normalize_language( $requested ) === 'en_US' ) {
			$english_translations = $this->get_hardcoded_english_translations();

			// Check if there are custom texts saved for this language
			$texts = $this->options->all()['banner_texts'] ?? array();
			if ( isset( $texts[ $requested ] ) && \is_array( $texts[ $requested ] ) ) {
				// Merge custom texts with English translations (custom texts take priority)
				return array_merge( $english_translations, $texts[ $requested ] );
			}

			// Check normalized language
			$normalized = $this->options->normalize_language( $requested );
			if ( isset( $texts[ $normalized ] ) && \is_array( $texts[ $normalized ] ) && $normalized !== $requested ) {
				// Merge with English translations
				return array_merge( $english_translations, $texts[ $normalized ] );
			}

			// Return hardcoded English translations
			return $english_translations;
		}

		// For other languages, use the normal translation system
		$translated_defaults = $this->get_translated_banner_defaults( $requested );

		// Check if there are custom texts saved for this language
		$texts = $this->options->all()['banner_texts'] ?? array();
		if ( isset( $texts[ $requested ] ) && \is_array( $texts[ $requested ] ) ) {
			// Merge custom texts with translated defaults (custom texts take priority)
			return array_merge( $translated_defaults, $texts[ $requested ] );
		}

		// Check normalized language
		$normalized = $this->options->normalize_language( $requested );
		if ( isset( $texts[ $normalized ] ) && \is_array( $texts[ $normalized ] ) && $normalized !== $requested ) {
			// Merge with translated defaults
			return array_merge( $translated_defaults, $texts[ $normalized ] );
		}

		// Return translated defaults as ultimate fallback
		return $translated_defaults;
	}

	/**
	 * Get translated banner defaults for a specific language.
	 *
	 * @param string $lang Language code.
	 *
	 * @return array<string, string>
	 */
	private function get_translated_banner_defaults( $lang ) {
		// Force load the correct textdomain for this language
		$original_locale = \get_locale();

		// Load the textdomain if not already loaded
		if ( ! \is_textdomain_loaded( 'fp-privacy' ) ) {
			$this->load_textdomain_for_locale( $original_locale );
		}

		// Temporarily switch locale to get translated strings
		if ( $lang !== $original_locale ) {
			\switch_to_locale( $lang );
			// Reload textdomain for the new locale
			\unload_textdomain( 'fp-privacy' );
			$this->load_textdomain_for_locale( $lang );
		}

		$defaults = array(
			'title'              => Options::maybe_translate( 'We value your privacy' ),
			'message'            => Options::maybe_translate( 'We use cookies to improve your experience. You can accept all cookies or manage your preferences.' ),
			'btn_accept'         => Options::maybe_translate( 'Accept all' ),
			'btn_reject'         => Options::maybe_translate( 'Reject all' ),
			'btn_prefs'          => Options::maybe_translate( 'Manage preferences' ),
			'modal_title'        => Options::maybe_translate( 'Cookie preferences' ),
			'modal_close'        => Options::maybe_translate( 'Close preferences' ),
			'modal_save'         => Options::maybe_translate( 'Save preferences' ),
			'revision_notice'    => Options::maybe_translate( 'We have updated our policy. Please review your preferences.' ),
			'toggle_locked'      => Options::maybe_translate( 'Required' ),
			'toggle_enabled'     => Options::maybe_translate( 'Enabled' ),
			'debug_label'        => Options::maybe_translate( 'Cookie debug:' ),
			'link_policy'        => '',
			'link_privacy_policy' => Options::maybe_translate( 'Privacy Policy' ),
			'link_cookie_policy'  => Options::maybe_translate( 'Cookie Policy' ),
		);

		// Restore original locale
		if ( $lang !== $original_locale ) {
			\restore_previous_locale();
			// Reload textdomain for the original locale
			\unload_textdomain( 'fp-privacy' );
			$this->load_textdomain_for_locale( $original_locale );
		}

		return $defaults;
	}

	/**
	 * Load textdomain for specific locale using absolute path (junction-safe).
	 *
	 * @param string $locale Locale code.
	 *
	 * @return bool
	 */
	private function load_textdomain_for_locale( $locale ) {
		$mofile = FP_PRIVACY_PLUGIN_PATH . 'languages/fp-privacy-' . $locale . '.mo';

		if ( file_exists( $mofile ) ) {
			return \load_textdomain( 'fp-privacy', $mofile );
		}

		return false;
	}

	/**
	 * Force update banner texts for all active languages with proper translations.
	 *
	 * @return void
	 */
	public function force_update_banner_texts_translations() {
		$languages   = $this->options->get_languages();
		$current_texts = $this->options->all()['banner_texts'] ?? array();
		$updated_texts = array();

		foreach ( $languages as $lang ) {
			$lang = Validator::locale( $lang, 'en_US' );

			// Get current texts for this language
			$current_lang_texts = $current_texts[ $lang ] ?? array();

			// Get translated defaults for this language
			$translated_defaults = $this->get_translated_banner_defaults( $lang );

			// Merge current texts with translated defaults (current texts take priority)
			$updated_texts[ $lang ] = array_merge( $translated_defaults, $current_lang_texts );
		}

		// Avoid database writes if nothing changed
		if ( $current_texts === $updated_texts ) {
			return;
		}

		// Update the options with the new translated texts
		$this->options->set( array( 'banner_texts' => $updated_texts ) );
	}

	/**
	 * Get hardcoded Italian translations as fallback.
	 * This ensures texts are always in Italian when the locale is Italian.
	 *
	 * @return array<string, string>
	 */
	private function get_hardcoded_italian_translations() {
		return array(
			'title'              => 'Rispettiamo la tua privacy',
			'message'            => 'Utilizziamo i cookie per migliorare la tua esperienza. Puoi accettare tutti i cookie o gestire le tue preferenze.',
			'btn_accept'         => 'Accetta tutti',
			'btn_reject'         => 'Rifiuta tutti',
			'btn_prefs'          => 'Gestisci preferenze',
			'modal_title'        => 'Preferenze cookie',
			'modal_close'        => 'Chiudi preferenze',
			'modal_save'         => 'Salva preferenze',
			'revision_notice'    => 'Abbiamo aggiornato la nostra policy. Rivedi le tue preferenze.',
			'toggle_locked'      => 'Obbligatorio',
			'toggle_enabled'     => 'Abilitato',
			'debug_label'        => 'Debug cookie:',
			'link_policy'        => '',
			'link_privacy_policy' => 'Informativa sulla Privacy',
			'link_cookie_policy'  => 'Cookie Policy',
		);
	}

	/**
	 * Get hardcoded English translations as fallback.
	 *
	 * @return array<string, string>
	 */
	private function get_hardcoded_english_translations() {
		return array(
			'title'               => 'We respect your privacy',
			'message'             => 'We use cookies to improve your experience. You can accept all cookies or manage your preferences.',
			'btn_accept'          => 'Accept all',
			'btn_reject'          => 'Reject all',
			'btn_prefs'           => 'Manage preferences',
			'modal_title'         => 'Cookie preferences',
			'modal_close'         => 'Close preferences',
			'modal_save'          => 'Save preferences',
			'revision_notice'     => 'We have updated our policy. Please review your preferences.',
			'toggle_locked'       => 'Required',
			'toggle_enabled'      => 'Enabled',
			'debug_label'         => 'Debug cookie:',
			'link_policy'         => '',
			'link_privacy_policy' => 'Privacy Policy',
			'link_cookie_policy'  => 'Cookie Policy',
		);
	}

	/**
	 * Detect user language from WPML, FP-Multilanguage, browser or WordPress locale.
	 *
	 * @return string
	 */
	public function detect_user_language() {
		// Priority 1: Check WPML (most common multilingual plugin)
		if ( function_exists( 'icl_get_languages' ) && function_exists( 'icl_get_current_language' ) ) {
			$wpml_lang = \icl_get_current_language();
			if ( $wpml_lang && is_string( $wpml_lang ) ) {
				// WPML returns language codes like 'it', 'en', etc.
				// Convert to locale format
				$wpml_locale = $this->convert_wpml_lang_to_locale( $wpml_lang );
				if ( $wpml_locale ) {
					return $wpml_locale;
				}
			}
		}

		// Priority 2: Check FP-Multilanguage plugin
		if ( function_exists( 'fpml_get_current_language' ) ) {
			$fpml_lang = \fpml_get_current_language();
			if ( $fpml_lang && is_string( $fpml_lang ) ) {
				return $fpml_lang;
			}
		}

		// Priority 3: Use WordPress locale (may be set by WPML or other plugins)
		$wp_locale = function_exists( '\get_locale' ) ? \get_locale() : 'en_US';
		
		// Normalize locale to standard format
		$normalized = $this->normalize_locale( $wp_locale );
		if ( $normalized ) {
			return $normalized;
		}

		// Priority 4: Try to detect from browser headers
		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			$browser_lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];

			// Check for Italian
			if ( strpos( $browser_lang, 'it' ) !== false ) {
				return 'it_IT';
			}

			// Check for English
			if ( strpos( $browser_lang, 'en' ) !== false ) {
				return 'en_US';
			}
		}

		// Default to English
		return 'en_US';
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

		// If WPML returns a full locale, try to normalize it
		if ( strpos( $wpml_lang, '_' ) !== false ) {
			return $this->normalize_locale( $wpml_lang );
		}

		return null;
	}

	/**
	 * Normalize locale code to standard format.
	 *
	 * @param string $locale Locale code.
	 * @return string|null Normalized locale or null if not recognized.
	 */
	private function normalize_locale( $locale ) {
		$locale = strtolower( trim( $locale ) );
		
		// Check for Italian variants
		if ( $locale === 'it_it' || $locale === 'it' || strpos( $locale, 'it' ) === 0 ) {
			return 'it_IT';
		}

		// Check for English variants
		if ( $locale === 'en_us' || $locale === 'en' || strpos( $locale, 'en' ) === 0 ) {
			return 'en_US';
		}

		// If it's already in the correct format (e.g., 'it_IT', 'en_US'), return as is
		if ( preg_match( '/^[a-z]{2}_[A-Z]{2}$/', $locale ) ) {
			return strtolower( substr( $locale, 0, 2 ) ) . '_' . strtoupper( substr( $locale, 3, 2 ) );
		}

		return null;
	}

	/**
	 * Get default banner texts for a language.
	 *
	 * @param string $lang Language code.
	 *
	 * @return array<string, string>
	 */
	public function get_default_banner_texts( $lang ) {
		if ( $lang === 'it_IT' ) {
			return $this->get_hardcoded_italian_translations();
		}

		if ( $lang === 'en_US' ) {
			return $this->get_hardcoded_english_translations();
		}

		return $this->get_translated_banner_defaults( $lang );
	}
}
















