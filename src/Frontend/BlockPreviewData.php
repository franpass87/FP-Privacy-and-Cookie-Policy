<?php
/**
 * Block preview data.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Utils\Options;

/**
 * Handles preparation of preview data for blocks.
 */
class BlockPreviewData {
	/**
	 * Build preview data for banner texts.
	 *
	 * @param Options                        $options   Options handler.
	 * @param array<int, array<string, string>> $languages Registered languages list.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_banner_preview_data( Options $options, array $languages ) {
		$preview = array();

		if ( empty( $languages ) ) {
			$languages[] = array(
				'code' => $options->normalize_language( \get_locale() ),
			);
		}

		foreach ( $languages as $language ) {
			if ( empty( $language['code'] ) ) {
				continue;
			}

			$code  = $options->normalize_language( $language['code'] );
			$texts = $options->get_banner_text( $code );

			$preview[ $code ] = array(
				'title'   => self::prepare_preview_text( $texts['title'] ?? '' ),
				'message' => self::prepare_preview_text( $texts['message'] ?? '' ),
				'accept'  => self::prepare_preview_text( $texts['btn_accept'] ?? '' ),
				'reject'  => self::prepare_preview_text( $texts['btn_reject'] ?? '' ),
				'prefs'   => self::prepare_preview_text( $texts['btn_prefs'] ?? '' ),
			);
		}

		return $preview;
	}

	/**
	 * Clean preview text for editor usage.
	 *
	 * @param string $text Raw text.
	 *
	 * @return string
	 */
	public static function prepare_preview_text( $text ) {
		$clean = \wp_strip_all_tags( (string) $text );

		$clean = \trim( \html_entity_decode( $clean, ENT_QUOTES, \get_bloginfo( 'charset' ) ?: 'UTF-8' ) );

		if ( '' === $clean ) {
			return '';
		}

		if ( \function_exists( 'mb_strlen' ) && \function_exists( 'mb_substr' ) ) {
			if ( \mb_strlen( $clean, 'UTF-8' ) > 320 ) {
				$clean = \rtrim( \mb_substr( $clean, 0, 317, 'UTF-8' ) ) . '…';
			}
		} elseif ( \strlen( $clean ) > 320 ) {
			$clean = \rtrim( \substr( $clean, 0, 317 ) ) . '…';
		}

		return $clean;
	}

	/**
	 * Build a human readable label for the locale.
	 *
	 * @param string $code Locale code.
	 *
	 * @return string
	 */
	public static function format_language_label( $code ) {
		$label  = $code;
		$locale = str_replace( '_', '-', $code );

		if ( class_exists( '\\Locale' ) ) {
			try {
				$display = \Locale::getDisplayName( $locale, $locale );
				if ( $display ) {
					$label = \ucwords( $display );
				}
			} catch ( \Throwable $e ) {
				// Fallback to the code when intl is not available.
			}
		}

		return $label;
	}
}















