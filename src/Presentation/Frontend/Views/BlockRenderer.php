<?php
/**
 * Block renderer.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Presentation\Frontend\Views;

/**
 * Handles rendering of Gutenberg blocks.
 */
class BlockRenderer {
	/**
	 * Render privacy policy block.
	 *
	 * @param array<string, mixed> $attributes Attributes.
	 *
	 * @return string
	 */
	public static function render_privacy_policy_block( $attributes ) {
		$lang = isset( $attributes['lang'] ) ? \sanitize_text_field( $attributes['lang'] ) : \get_locale();

		return \do_shortcode( '[fp_privacy_policy lang="' . \esc_attr( $lang ) . '"]' );
	}

	/**
	 * Render cookie policy block.
	 *
	 * @param array<string, mixed> $attributes Attributes.
	 *
	 * @return string
	 */
	public static function render_cookie_policy_block( $attributes ) {
		$lang = isset( $attributes['lang'] ) ? \sanitize_text_field( $attributes['lang'] ) : \get_locale();

		return \do_shortcode( '[fp_cookie_policy lang="' . \esc_attr( $lang ) . '"]' );
	}

	/**
	 * Render preferences block.
	 *
	 * @param array<string, mixed> $attributes Attributes.
	 *
	 * @return string
	 */
	public static function render_preferences_block( $attributes ) {
		$label = isset( $attributes['label'] ) && '' !== \trim( (string) $attributes['label'] )
			? \sanitize_text_field( $attributes['label'] )
			: \__( 'Manage cookie preferences', 'fp-privacy' );

		$description = isset( $attributes['description'] ) && '' !== \trim( (string) $attributes['description'] )
			? \sanitize_text_field( $attributes['description'] )
			: '';

		$lang = isset( $attributes['lang'] ) && '' !== \trim( (string) $attributes['lang'] )
			? \preg_replace( '/[^A-Za-z0-9_\\-]/', '', $attributes['lang'] )
			: '';

		$atts = array(
			'label' => \esc_attr( $label ),
		);

		if ( '' !== $description ) {
			$atts['description'] = \esc_attr( $description );
		}

		if ( '' !== $lang ) {
			$atts['lang'] = \esc_attr( $lang );
		}

		$attr_string = '';

		foreach ( $atts as $key => $value ) {
			$attr_string .= \sprintf( ' %s="%s"', $key, $value );
		}

		return \do_shortcode( '[fp_cookie_preferences' . $attr_string . ']' );
	}

	/**
	 * Render banner block.
	 *
	 * @param array<string, mixed> $attributes Attributes.
	 *
	 * @return string
	 */
	public static function render_banner_block( $attributes ) {
		$attrs = array();

		$layout   = isset( $attributes['layoutType'] ) ? $attributes['layoutType'] : '';
		$position = isset( $attributes['position'] ) ? $attributes['position'] : '';
		$lang     = isset( $attributes['lang'] ) ? $attributes['lang'] : '';

		if ( 'bar' === $layout ) {
			$attrs['type'] = 'bar';
		} elseif ( 'floating' === $layout ) {
			$attrs['type'] = 'floating';
		}

		if ( 'top' === $position ) {
			$attrs['position'] = 'top';
		} elseif ( 'bottom' === $position ) {
			$attrs['position'] = 'bottom';
		}

		if ( \is_string( $lang ) && '' !== \trim( $lang ) ) {
			$attrs['lang'] = \preg_replace( '/[^A-Za-z0-9_\\-]/', '', $lang );
		}

		if ( ! empty( $attributes['forceDisplay'] ) ) {
			$attrs['force'] = '1';
		}

		$attr_string = '';

		foreach ( $attrs as $key => $value ) {
			$attr_string .= \sprintf( ' %s="%s"', $key, \esc_attr( $value ) );
		}

		return \do_shortcode( '[fp_cookie_banner' . $attr_string . ']' );
	}
}















