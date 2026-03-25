<?php
/**
 * Registry of banner color palette presets (admin selector).
 *
 * @package FP\Privacy\Support
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

declare(strict_types=1);

namespace FP\Privacy\Support;

/**
 * Provides built-in palette themes and helpers for save/view.
 */
final class PalettePresetRegistry {
	public const ID_DEFAULT = 'default';

	public const ID_SOFT = 'soft';

	public const ID_CONTRAST = 'contrast';

	public const ID_DARK = 'dark';

	public const ID_CUSTOM = 'custom';

	/**
	 * Ordered preset ids for select options (excludes custom).
	 *
	 * @return list<string>
	 */
	public static function builtin_preset_ids(): array {
		return array(
			self::ID_DEFAULT,
			self::ID_SOFT,
			self::ID_CONTRAST,
			self::ID_DARK,
		);
	}

	/**
	 * Human-readable label for a preset id.
	 *
	 * @param string $id Preset slug.
	 *
	 * @return string
	 */
	public static function get_label( string $id ): string {
		switch ( $id ) {
			case self::ID_DEFAULT:
				return \__( 'Predefinito (chiaro FP)', 'fp-privacy' );
			case self::ID_SOFT:
				return \__( 'Neutro morbido', 'fp-privacy' );
			case self::ID_CONTRAST:
				return \__( 'Alto contrasto', 'fp-privacy' );
			case self::ID_DARK:
				return \__( 'Scuro', 'fp-privacy' );
			case self::ID_CUSTOM:
				return \__( 'Personalizzato', 'fp-privacy' );
			default:
				return $id;
		}
	}

	/**
	 * Full color map for a built-in preset.
	 *
	 * @param string $id Preset slug (not "custom").
	 *
	 * @return array<string, string>
	 */
	public static function get_preset_colors( string $id ): array {
		$all = self::all_builtin_colors();
		$id  = \sanitize_key( $id );

		return $all[ $id ] ?? $all[ self::ID_DEFAULT ];
	}

	/**
	 * Whether the id is a built-in theme (not custom).
	 *
	 * @param string $id Preset slug from POST.
	 */
	public static function is_builtin_preset( string $id ): bool {
		return \in_array( \sanitize_key( $id ), self::builtin_preset_ids(), true );
	}

	/**
	 * Resolve palette array from posted banner_layout fragment.
	 *
	 * @param array<string, mixed> $layout_raw     Raw banner_layout from request.
	 * @param array<string, string> $fallback_palette Palette già salvata o default plugin se la richiesta non include i colori.
	 *
	 * @return array<string, string>
	 */
	public static function resolve_palette_from_request( array $layout_raw, array $fallback_palette ): array {
		$preset = isset( $layout_raw['palette_preset'] ) ? \sanitize_key( (string) $layout_raw['palette_preset'] ) : '';

		if ( self::is_builtin_preset( $preset ) ) {
			return self::get_preset_colors( $preset );
		}

		if ( self::ID_CUSTOM === $preset && isset( $layout_raw['palette'] ) && \is_array( $layout_raw['palette'] ) ) {
			return self::normalize_palette_keys( $layout_raw['palette'], $fallback_palette );
		}

		if ( isset( $layout_raw['palette'] ) && \is_array( $layout_raw['palette'] ) ) {
			return self::normalize_palette_keys( $layout_raw['palette'], $fallback_palette );
		}

		return $fallback_palette;
	}

	/**
	 * Detect which built-in preset matches stored colors, or "custom".
	 *
	 * @param array<string, string> $palette Stored palette (any key order).
	 *
	 * @return string
	 */
	public static function detect_preset( array $palette ): string {
		$normalized = self::normalize_for_compare( $palette );

		foreach ( self::builtin_preset_ids() as $id ) {
			if ( self::palettes_equal( $normalized, self::normalize_for_compare( self::get_preset_colors( $id ) ) ) ) {
				return $id;
			}
		}

		return self::ID_CUSTOM;
	}

	/**
	 * Preset colors for wp_localize_script (JSON-safe).
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function get_js_preset_map(): array {
		$out = array();
		foreach ( self::builtin_preset_ids() as $id ) {
			$out[ $id ] = self::get_preset_colors( $id );
		}

		return $out;
	}

	/**
	 * @return array<string, array<string, string>>
	 */
	private static function all_builtin_colors(): array {
		return array(
			self::ID_DEFAULT => array(
				'surface_bg'          => '#F9FAFB',
				'surface_text'        => '#1F2937',
				'button_primary_bg'   => '#2563EB',
				'button_primary_tx'   => '#FFFFFF',
				'button_secondary_bg' => '#FFFFFF',
				'button_secondary_tx' => '#1F2937',
				'link'                => '#1D4ED8',
				'border'              => '#D1D5DB',
				'focus'               => '#2563EB',
			),
			self::ID_SOFT => array(
				'surface_bg'          => '#FAFAF9',
				'surface_text'        => '#292524',
				'button_primary_bg'   => '#2563EB',
				'button_primary_tx'   => '#FFFFFF',
				'button_secondary_bg' => '#FFFFFF',
				'button_secondary_tx' => '#44403C',
				'link'                => '#1D4ED8',
				'border'              => '#D6D3D1',
				'focus'               => '#2563EB',
			),
			self::ID_CONTRAST => array(
				'surface_bg'          => '#FFFFFF',
				'surface_text'        => '#000000',
				'button_primary_bg'   => '#1D4ED8',
				'button_primary_tx'   => '#FFFFFF',
				'button_secondary_bg' => '#E5E7EB',
				'button_secondary_tx' => '#000000',
				'link'                => '#1E3A8A',
				'border'              => '#6B7280',
				'focus'               => '#2563EB',
			),
			self::ID_DARK => array(
				'surface_bg'          => '#0F172A',
				'surface_text'        => '#F8FAFC',
				'button_primary_bg'   => '#3B82F6',
				'button_primary_tx'   => '#FFFFFF',
				'button_secondary_bg' => '#1E293B',
				'button_secondary_tx' => '#E2E8F0',
				'link'                => '#60A5FA',
				'border'              => '#334155',
				'focus'               => '#38BDF8',
			),
		);
	}

	/**
	 * @param array<string, mixed>  $palette
	 * @param array<string, string> $fallback
	 *
	 * @return array<string, string>
	 */
	private static function normalize_palette_keys( array $palette, array $fallback ): array {
		$out = array();
		foreach ( \array_keys( $fallback ) as $key ) {
			$val = isset( $palette[ $key ] ) && \is_string( $palette[ $key ] ) ? $palette[ $key ] : ( $fallback[ $key ] ?? '' );
			$out[ $key ] = $val;
		}

		return $out;
	}

	/**
	 * @param array<string, string> $palette
	 *
	 * @return array<string, string>
	 */
	private static function normalize_for_compare( array $palette ): array {
		$keys = array(
			'surface_bg',
			'surface_text',
			'button_primary_bg',
			'button_primary_tx',
			'button_secondary_bg',
			'button_secondary_tx',
			'link',
			'border',
			'focus',
		);
		$out  = array();
		foreach ( $keys as $key ) {
			$out[ $key ] = self::expand_hex( isset( $palette[ $key ] ) ? (string) $palette[ $key ] : '' );
		}

		return $out;
	}

	/**
	 * @param array<string, string> $a
	 * @param array<string, string> $b
	 */
	private static function palettes_equal( array $a, array $b ): bool {
		foreach ( $a as $k => $v ) {
			if ( ( $b[ $k ] ?? '' ) !== $v ) {
				return false;
			}
		}

		return true;
	}

	private static function expand_hex( string $color ): string {
		$c = \strtoupper( \trim( $color ) );
		if ( '' === $c ) {
			return '';
		}
		if ( '#' !== $c[0] ) {
			$c = '#' . $c;
		}
		if ( \preg_match( '/^#([0-9A-F]{3})$/', $c, $m ) ) {
			$h = $m[1];

			return '#' . $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
		}

		return $c;
	}
}
