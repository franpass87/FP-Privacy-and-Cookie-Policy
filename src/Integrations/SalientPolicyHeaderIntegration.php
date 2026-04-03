<?php
/**
 * Salient theme: page header meta for policy pages.
 *
 * @package FP\Privacy\Integrations
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

declare(strict_types=1);

namespace FP\Privacy\Integrations;

use FP\Privacy\Utils\Options;

/**
 * Imposta meta Salient (titolo header + allineamento centro) sulle pagine policy del plugin.
 */
final class SalientPolicyHeaderIntegration {

	/**
	 * True se il template attivo è Salient (anche child theme).
	 *
	 * @return bool
	 */
	public static function is_salient_active(): bool {
		if ( ! \function_exists( 'wp_get_theme' ) ) {
			return false;
		}

		$theme = \wp_get_theme();
		return 'salient' === \strtolower( (string) $theme->get_template() );
	}

	/**
	 * Sincronizza meta Page Header Salient per una pagina.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public static function sync_post( int $post_id ): void {
		if ( $post_id <= 0 || ! self::is_salient_active() ) {
			return;
		}

		$post = \get_post( $post_id );
		if ( ! ( $post instanceof \WP_Post ) || 'page' !== $post->post_type ) {
			return;
		}

		$title = \sanitize_text_field( (string) $post->post_title );
		if ( '' === $title ) {
			return;
		}

		\update_post_meta( $post_id, '_nectar_header_title', $title );
		\update_post_meta( $post_id, '_nectar_page_header_alignment', 'center' );
		\update_post_meta( $post_id, '_nectar_page_header_alignment_v', 'middle' );
	}

	/**
	 * Verifica se il post è una pagina privacy/cookie registrata nelle opzioni.
	 *
	 * @param int     $post_id Post ID.
	 * @param Options $options Opzioni plugin.
	 * @return bool
	 */
	public static function is_policy_page( int $post_id, Options $options ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}

		$languages = $options->get_languages();
		if ( empty( $languages ) ) {
			$languages = array( \function_exists( 'get_locale' ) ? (string) \get_locale() : 'en_US' );
		}

		foreach ( $languages as $lang ) {
			$lang = $options->normalize_language( (string) $lang );
			if ( $post_id === $options->get_page_id( 'privacy_policy', $lang ) ) {
				return true;
			}
			if ( $post_id === $options->get_page_id( 'cookie_policy', $lang ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Sincronizza tutte le pagine policy configurate (es. dopo switch tema).
	 *
	 * @param Options $options Opzioni plugin.
	 * @return void
	 */
	public static function sync_all_policy_pages( Options $options ): void {
		if ( ! self::is_salient_active() ) {
			return;
		}

		$languages = $options->get_languages();
		if ( empty( $languages ) ) {
			$languages = array( \function_exists( 'get_locale' ) ? (string) \get_locale() : 'en_US' );
		}

		$seen = array();

		foreach ( $languages as $lang ) {
			$lang = $options->normalize_language( (string) $lang );
			foreach ( array( 'privacy_policy', 'cookie_policy' ) as $type ) {
				$id = $options->get_page_id( $type, $lang );
				if ( $id <= 0 || isset( $seen[ $id ] ) ) {
					continue;
				}
				$seen[ $id ] = true;
				self::sync_post( $id );
			}
		}
	}

	/**
	 * Registra hook WordPress.
	 *
	 * @param Options $options Opzioni plugin.
	 * @return void
	 */
	public static function register( Options $options ): void {
		\add_action(
			'save_post_page',
			static function ( $post_id, $unused_post, $unused_update ) use ( $options ): void {
				unset( $unused_post, $unused_update );

				$pid = (int) $post_id;
				if ( $pid <= 0 || \wp_is_post_autosave( $pid ) || \wp_is_post_revision( $pid ) ) {
					return;
				}

				if ( ! self::is_policy_page( $pid, $options ) ) {
					return;
				}

				if ( ! self::is_salient_active() ) {
					return;
				}

				self::sync_post( $pid );
			},
			20,
			3
		);

		\add_action(
			'after_switch_theme',
			static function () use ( $options ): void {
				self::sync_all_policy_pages( $options );
			}
		);
	}
}
