<?php
/**
 * Banner validation utilities.
 *
 * @package FP\Privacy\Utils\Validator
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils\Validator;

use FP\Privacy\Domain\ValueObjects\ColorPalette;
use function wp_parse_args;
use function get_locale;
use function switch_to_locale;
use function restore_previous_locale;
use function did_action;
use function __;

/**
 * Provides banner-related validation and sanitization methods.
 */
class BannerValidator {
	/**
	 * Sanitize the color palette configuration.
	 *
	 * Uses ColorPalette value object internally for validation and sanitization.
	 *
	 * @param array<string, mixed>  $palette  Palette values.
	 * @param array<string, string> $defaults Default palette.
	 *
	 * @return array<string, string>
	 */
	public static function sanitize_palette( array $palette, array $defaults ): array {
		// Merge with defaults first.
		$palette_data = wp_parse_args( $palette, $defaults );
		
		// Use ColorPalette value object for validation and sanitization.
		// This ensures all colors are properly validated and sanitized.
		$color_palette = ColorPalette::from_array( $palette_data );
		
		// Return as array for backward compatibility.
		return $color_palette->to_array();
	}

	/**
	 * Sanitize banner texts per language.
	 *
	 * @param array<string, array<string, mixed>> $texts     Banner texts.
	 * @param array<int, string>                  $languages Active languages.
	 * @param array<string, string>               $defaults  Default text map.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function sanitize_banner_texts( array $texts, array $languages, array $defaults ): array {
		$sanitized = array();

		foreach ( $languages as $language ) {
			$language = BasicValidator::locale( $language, 'en_US' );
			$source   = isset( $texts[ $language ] ) && is_array( $texts[ $language ] ) ? $texts[ $language ] : array();

			// Get translated defaults for this language
			$translated_defaults = self::get_translated_banner_defaults_for_language( $language );

			$sanitized[ $language ] = array(
				'title'           => BasicValidator::text( $source['title'] ?? $translated_defaults['title'] ),
				'message'         => BasicValidator::textarea( $source['message'] ?? $translated_defaults['message'] ),
				'btn_accept'      => BasicValidator::text( $source['btn_accept'] ?? $translated_defaults['btn_accept'] ),
				'btn_reject'      => BasicValidator::text( $source['btn_reject'] ?? $translated_defaults['btn_reject'] ),
				'btn_prefs'       => BasicValidator::text( $source['btn_prefs'] ?? $translated_defaults['btn_prefs'] ),
				'modal_title'     => BasicValidator::text( $source['modal_title'] ?? $translated_defaults['modal_title'] ),
				'modal_close'     => BasicValidator::text( $source['modal_close'] ?? $translated_defaults['modal_close'] ),
				'modal_save'      => BasicValidator::text( $source['modal_save'] ?? $translated_defaults['modal_save'] ),
				'revision_notice' => BasicValidator::text( $source['revision_notice'] ?? $translated_defaults['revision_notice'] ),
				'toggle_locked'   => BasicValidator::text( $source['toggle_locked'] ?? $translated_defaults['toggle_locked'] ),
				'toggle_enabled'  => BasicValidator::text( $source['toggle_enabled'] ?? $translated_defaults['toggle_enabled'] ),
				'debug_label'     => BasicValidator::text( $source['debug_label'] ?? $translated_defaults['debug_label'] ),
				'link_policy'     => BasicValidator::url( $source['link_policy'] ?? $translated_defaults['link_policy'] ),
				'link_privacy_policy' => BasicValidator::text( $source['link_privacy_policy'] ?? $translated_defaults['link_privacy_policy'] ),
				'link_cookie_policy'  => BasicValidator::text( $source['link_cookie_policy'] ?? $translated_defaults['link_cookie_policy'] ),
			);
		}

		return $sanitized;
	}

	/**
	 * Get translated banner defaults for a specific language.
	 *
	 * @param string $lang Language code.
	 *
	 * @return array<string, string>
	 */
	private static function get_translated_banner_defaults_for_language( $lang ) {
		// Default italiano hardcoded (lingua principale)
		if ( $lang === 'it_IT' || $lang === 'it' ) {
			return array(
				'title'               => 'Rispettiamo la tua privacy',
				'message'             => 'Utilizziamo i cookie per migliorare la tua esperienza. Puoi accettare tutti i cookie o gestire le tue preferenze.',
				'btn_accept'          => 'Accetta tutti',
				'btn_reject'          => 'Rifiuta tutti',
				'btn_prefs'           => 'Gestisci preferenze',
				'modal_title'         => 'Preferenze cookie',
				'modal_close'         => 'Chiudi preferenze',
				'modal_save'          => 'Salva preferenze',
				'revision_notice'     => 'Abbiamo aggiornato la nostra policy. Rivedi le tue preferenze.',
				'toggle_locked'       => 'Obbligatorio',
				'toggle_enabled'      => 'Abilitato',
				'debug_label'         => 'Debug cookie:',
				'link_policy'         => '',
				'link_privacy_policy' => 'Informativa sulla Privacy',
				'link_cookie_policy'  => 'Cookie Policy',
			);
		}

		// Per altre lingue, usa switch_to_locale
		$original_locale = \get_locale();
		if ( $lang !== $original_locale ) {
			\switch_to_locale( $lang );
		}

		$defaults = array(
			'title'              => self::translate( 'We value your privacy' ),
			'message'            => self::translate( 'We use cookies to improve your experience. You can accept all cookies or manage your preferences.' ),
			'btn_accept'         => self::translate( 'Accept all' ),
			'btn_reject'         => self::translate( 'Reject all' ),
			'btn_prefs'          => self::translate( 'Manage preferences' ),
			'modal_title'        => self::translate( 'Cookie preferences' ),
			'modal_close'        => self::translate( 'Close preferences' ),
			'modal_save'         => self::translate( 'Save preferences' ),
			'revision_notice'    => self::translate( 'We have updated our policy. Please review your preferences.' ),
			'toggle_locked'      => self::translate( 'Required' ),
			'toggle_enabled'     => self::translate( 'Enabled' ),
			'debug_label'        => self::translate( 'Cookie debug:' ),
			'link_policy'        => '',
			'link_privacy_policy' => self::translate( 'Privacy Policy' ),
			'link_cookie_policy'  => self::translate( 'Cookie Policy' ),
		);

		// Restore original locale
		if ( $lang !== $original_locale ) {
			\restore_previous_locale();
		}

		return $defaults;
	}

	/**
	 * Translate helper that avoids early loading issues.
	 *
	 * @param string $text Text to translate.
	 *
	 * @return string
	 */
	private static function translate( string $text ): string {
		if ( function_exists( 'did_action' ) && did_action( 'init' ) > 0 ) {
			return __( $text, 'fp-privacy' );
		}

		return $text;
	}
}
















