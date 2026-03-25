<?php
/**
 * Banner texts manager.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

use FP\Privacy\Shared\Constants;

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
	 * Constructor.
	 *
	 * @param Options $options Options handler.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
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
				$result = array_merge( $italian_translations, $texts[ $requested ] );
				return $this->migrate_about_content_to_standard( $result, 'it_IT' );
			}

			// Check normalized language
			$normalized = $this->options->normalize_language( $requested );
			if ( isset( $texts[ $normalized ] ) && \is_array( $texts[ $normalized ] ) && $normalized !== $requested ) {
				$result = array_merge( $italian_translations, $texts[ $normalized ] );
				return $this->migrate_about_content_to_standard( $result, 'it_IT' );
			}

			return $italian_translations;
		}

		// If the requested language is English, use hardcoded English translations
		if ( $requested === 'en_US' || $this->options->normalize_language( $requested ) === 'en_US' ) {
			$english_translations = $this->get_hardcoded_english_translations();

			// Check if there are custom texts saved for this language
			$texts = $this->options->all()['banner_texts'] ?? array();
			if ( isset( $texts[ $requested ] ) && \is_array( $texts[ $requested ] ) ) {
				$result = array_merge( $english_translations, $texts[ $requested ] );
				return $this->migrate_about_content_to_standard( $result, 'en_US' );
			}

			// Check normalized language
			$normalized = $this->options->normalize_language( $requested );
			if ( isset( $texts[ $normalized ] ) && \is_array( $texts[ $normalized ] ) && $normalized !== $requested ) {
				$result = array_merge( $english_translations, $texts[ $normalized ] );
				return $this->migrate_about_content_to_standard( $result, 'en_US' );
			}

			return $english_translations;
		}

		// For other languages, use the normal translation system
		$translated_defaults = $this->get_translated_banner_defaults( $requested );

		// Check if there are custom texts saved for this language
		$texts = $this->options->all()['banner_texts'] ?? array();
		if ( isset( $texts[ $requested ] ) && \is_array( $texts[ $requested ] ) ) {
			$result = array_merge( $translated_defaults, $texts[ $requested ] );
			return $this->migrate_about_content_to_standard( $result, $requested );
		}

		// Check normalized language
		$normalized = $this->options->normalize_language( $requested );
		if ( isset( $texts[ $normalized ] ) && \is_array( $texts[ $normalized ] ) && $normalized !== $requested ) {
			$result = array_merge( $translated_defaults, $texts[ $normalized ] );
			return $this->migrate_about_content_to_standard( $result, $requested );
		}

		return $translated_defaults;
	}

	/**
	 * Migrate deprecated about_content (old short/company text) to the new standard text.
	 * If the locale is Italian but the saved body still matches the canonical English paragraph, replace it.
	 * Persists the fix so it applies on next load.
	 *
	 * @param array<string, string> $result Merged banner texts.
	 * @param string                $lang  Language code.
	 *
	 * @return array<string, string>
	 */
	private function migrate_about_content_to_standard( array $result, string $lang ): array {
		$current = isset( $result['about_content'] ) ? trim( (string) $result['about_content'] ) : '';
		if ( '' === $current ) {
			return $result;
		}

		$deprecated = array(
			'it_IT' => array(
				'Scopri di più sulla nostra azienda, i nostri valori e la nostra storia. Puoi trovare maggiori informazioni nella sezione Chi siamo del nostro sito.',
				'Utilizziamo i cookie per personalizzare contenuti e annunci, fornire funzionalità per i social media e analizzare il nostro traffico. Condividiamo inoltre informazioni sull\'utilizzo del nostro sito con i nostri partner per social media, pubblicità e analisi.',
			),
			'en_US' => array(
				'Learn more about our company, our values and our story. Find more information in the About us section of our website.',
				'We use cookies to personalise content and ads, to provide social media features and to analyse our traffic. We also share information about your use of our site with our social media, advertising and analytics partners.',
			),
		);

		$lang_key   = ( strpos( $lang, 'it' ) === 0 ) ? 'it_IT' : 'en_US';
		$to_replace = $deprecated[ $lang_key ] ?? array();
		$new_text   = $lang_key === 'it_IT'
			? Constants::BANNER_INFO_ABOUT_IT
			: Constants::BANNER_INFO_ABOUT_EN_UK;

		$should_replace = false;

		foreach ( $to_replace as $old ) {
			if ( $current === $old ) {
				$should_replace = true;
				break;
			}
		}

		// Sostituisci anche testi brevi (< 250 caratteri) che sembrano vecchi deprecati.
		if ( ! $should_replace && \strlen( $current ) < 250 ) {
			$short_deprecated_phrases = array(
				'it_IT' => array( 'personalizzare contenuti', 'azienda', 'Chi siamo' ),
				'en_US' => array( 'personalise content', 'our company', 'About us' ),
			);
			$phrases = $short_deprecated_phrases[ $lang_key ] ?? array();
			foreach ( $phrases as $phrase ) {
				if ( false !== \strpos( \strtolower( $current ), \strtolower( $phrase ) ) ) {
					$should_replace = true;
					break;
				}
			}
		}

		// Lingua italiana ma testo "Info" ancora il paragrafo standard inglese (es. merge opzioni errato).
		if ( ! $should_replace && 'it_IT' === $lang_key ) {
			if ( $current === Constants::BANNER_INFO_ABOUT_EN_UK || $current === Constants::BANNER_INFO_ABOUT_EN_US ) {
				$should_replace = true;
			}
		}

		if ( $should_replace ) {
			$result['about_content'] = $new_text;
			$this->persist_about_content_migration( $lang, $new_text );
		}

		return $result;
	}

	/**
	 * Persist migrated about_content to options so the fix is permanent.
	 *
	 * @param string $lang Language code.
	 * @param string $text New about_content text.
	 *
	 * @return void
	 */
	private function persist_about_content_migration( string $lang, string $text ): void {
		$all     = $this->options->all();
		$texts   = isset( $all['banner_texts'] ) && \is_array( $all['banner_texts'] ) ? $all['banner_texts'] : array();
		$norm    = $this->options->normalize_language( $lang );
		$lang_key = ( strpos( $norm, 'it' ) === 0 ) ? 'it_IT' : ( ( strpos( $norm, 'en' ) === 0 ) ? 'en_US' : $norm );

		if ( ! isset( $texts[ $lang_key ] ) || ! \is_array( $texts[ $lang_key ] ) ) {
			$texts[ $lang_key ] = array();
		}
		$texts[ $lang_key ]['about_content'] = $text;
		$this->options->set( array( 'banner_texts' => $texts ) );
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
			'tab_consent'        => Options::maybe_translate( 'Consent' ),
			'tab_details'        => Options::maybe_translate( 'Details' ),
			'tab_about'          => Options::maybe_translate( 'About' ),
			'tab_details_title'  => Options::maybe_translate( 'Categories and services' ),
			'about_content'      => Options::maybe_translate( 'We use cookies to ensure the proper functioning of the site and to improve your browsing experience. Cookies allow us to store your preferences, analyze traffic and personalise content. For more details on which cookies we use and how to manage them, please refer to our Cookie Policy and Privacy Policy.' ),
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
			'reject_all_confirm'  => 'Rifiutando non verranno attivati cookie di statistica e marketing (restano solo quelli strettamente necessari). Vuoi continuare?',
			'tab_consent'        => 'Consenso',
			'tab_details'        => 'Dettagli',
			'tab_about'          => 'Info',
			'tab_details_title'  => 'Categorie e servizi',
			'about_content'      => Constants::BANNER_INFO_ABOUT_IT,
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
			'reject_all_confirm'  => 'If you reject, analytics and marketing cookies will not be activated (only strictly necessary cookies remain). Continue?',
			'tab_consent'        => 'Consent',
			'tab_details'        => 'Details',
			'tab_about'          => 'About',
			'tab_details_title'  => 'Categories and services',
			'about_content'      => Constants::BANNER_INFO_ABOUT_EN_UK,
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
















