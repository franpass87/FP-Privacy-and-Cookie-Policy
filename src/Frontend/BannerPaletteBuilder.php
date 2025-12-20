<?php
/**
 * Banner palette builder.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Domain\ValueObjects\ColorPalette;

/**
 * Handles building CSS for banner palette.
 */
class BannerPaletteBuilder {
	/**
	 * Build palette CSS variables.
	 *
	 * @param array<string, string>|ColorPalette $palette    Palette (array or value object).
	 * @param bool                                $sync_modal Whether modal styling should mirror the banner.
	 *
	 * @return string
	 */
	public function build_palette_css( $palette, $sync_modal = false ) {
		// Convert ColorPalette value object to array if needed.
		if ( $palette instanceof ColorPalette ) {
			$palette = $palette->to_array();
		}

		$defaults = array(
			'surface_bg'          => '#F9FAFB',
			'surface_text'        => '#1F2937',
			'button_primary_bg'   => '#2563EB',
			'button_primary_tx'   => '#FFFFFF',
			'button_secondary_bg' => '#FFFFFF',
			'button_secondary_tx' => '#1F2937',
			'link'                => '#1D4ED8',
			'border'              => '#D1D5DB',
			'focus'               => '#2563EB',
		);

		$palette = is_array( $palette ) ? array_merge( $defaults, $palette ) : $defaults;

		$surface_bg          = $this->sanitize_palette_value( $palette, 'surface_bg', $defaults['surface_bg'] );
		$surface_text        = $this->sanitize_palette_value( $palette, 'surface_text', $defaults['surface_text'] );
		$button_primary_bg   = $this->sanitize_palette_value( $palette, 'button_primary_bg', $defaults['button_primary_bg'] );
		$button_primary_tx   = $this->sanitize_palette_value( $palette, 'button_primary_tx', $defaults['button_primary_tx'] );
		$button_secondary_bg = $this->sanitize_palette_value( $palette, 'button_secondary_bg', $defaults['button_secondary_bg'] );
		$button_secondary_tx = $this->sanitize_palette_value( $palette, 'button_secondary_tx', $defaults['button_secondary_tx'] );
		$link                = $this->sanitize_palette_value( $palette, 'link', $defaults['link'] );
		$border              = $this->sanitize_palette_value( $palette, 'border', $defaults['border'] );
		$focus               = $this->sanitize_palette_value( $palette, 'focus', $defaults['focus'] );

		$css  = '#fp-privacy-banner-root, [data-fp-privacy-banner] {';
		$css .= '--fp-privacy-surface_bg:' . $surface_bg . ';';
		$css .= '--fp-privacy-surface_text:' . $surface_text . ';';
		$css .= '--fp-privacy-button_primary_bg:' . $button_primary_bg . ';';
		$css .= '--fp-privacy-button_primary_tx:' . $button_primary_tx . ';';
		$css .= '--fp-privacy-button_secondary_bg:' . $button_secondary_bg . ';';
		$css .= '--fp-privacy-button_secondary_tx:' . $button_secondary_tx . ';';
		$css .= '--fp-privacy-link:' . $link . ';';
		$css .= '--fp-privacy-border:' . $border . ';';
		$css .= '--fp-privacy-focus:' . $focus . ';';
		$css .= '}' . PHP_EOL;

		if ( $sync_modal ) {
			$css .= '.fp-privacy-modal{background:' . $surface_bg . ';color:' . $surface_text . ';border:1px solid ' . $border . ';}' . PHP_EOL;
			$css .= '.fp-privacy-modal button.close{color:' . $surface_text . ';}' . PHP_EOL;
			$css .= '.fp-privacy-modal .fp-privacy-button-primary{background:' . $button_primary_bg . ';color:' . $button_primary_tx . ';}' . PHP_EOL;
			$css .= '.fp-privacy-modal .fp-privacy-button-secondary{background:' . $button_secondary_bg . ';color:' . $button_secondary_tx . ';border-color:' . $border . ';}' . PHP_EOL;
			$css .= '.fp-privacy-modal .fp-privacy-switch input[type="checkbox"]:checked{background:' . $button_primary_bg . ';}' . PHP_EOL;
		}

		return $css;
	}

	/**
	 * Sanitize a palette value with fallback.
	 *
	 * @param array<string, string> $palette Palette array.
	 * @param string                $key     Palette key.
	 * @param string                $default Default value.
	 *
	 * @return string
	 */
	private function sanitize_palette_value( array $palette, $key, $default ) {
		if ( ! isset( $palette[ $key ] ) ) {
			return $default;
		}

		$value = \sanitize_hex_color( $palette[ $key ] );

		if ( false === $value ) {
			return $default;
		}

		if ( 4 === strlen( $value ) ) {
			$value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
		}

		return $value;
	}
}















