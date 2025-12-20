<?php
/**
 * Block registry.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Utils\Options;

/**
 * Handles registration of Gutenberg blocks.
 */
class BlockRegistry {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Tracks localized editor scripts.
	 *
	 * @var array<string, bool>
	 */
	private $localized = array();

	/**
	 * Constructor.
	 *
	 * @param Options $options Options.
	 */
	public function __construct( Options $options ) {
		$this->options = $options;
	}

	/**
	 * Register blocks.
	 *
	 * @param array<string, callable> $block_callbacks Map of block slugs to render callbacks.
	 *
	 * @return void
	 */
	public function register_blocks( array $block_callbacks ) {
		foreach ( $block_callbacks as $slug => $callback ) {
			$dir = FP_PRIVACY_PLUGIN_PATH . 'blocks/' . $slug;

			if ( ! file_exists( $dir . '/block.json' ) ) {
				continue;
			}

			$this->register_block_assets( $slug );

			$type = \register_block_type(
				$dir . '/block.json',
				array(
					'render_callback' => $callback,
				)
			);

			if ( $type instanceof \WP_Block_Type ) {
				$this->maybe_localize_languages( $slug, $type->editor_script );
			}
		}
	}

	/**
	 * Register block assets for a given slug.
	 *
	 * @param string $slug Block slug.
	 *
	 * @return void
	 */
	private function register_block_assets( $slug ) {
		$handles = array(
			'privacy-policy'     => array(
				'script' => 'fp-privacy-privacy-policy-block-editor',
				'style'  => 'fp-privacy-privacy-policy-block-editor-style',
			),
			'cookie-policy'      => array(
				'script' => 'fp-privacy-cookie-policy-block-editor',
				'style'  => 'fp-privacy-cookie-policy-block-editor-style',
			),
			'cookie-preferences' => array(
				'script' => 'fp-privacy-cookie-preferences-block-editor',
				'style'  => 'fp-privacy-cookie-preferences-block-editor-style',
			),
			'cookie-banner'      => array(
				'script' => 'fp-privacy-cookie-banner-block-editor',
				'style'  => 'fp-privacy-cookie-banner-block-editor-style',
			),
		);

		if ( ! isset( $handles[ $slug ] ) ) {
			return;
		}

		$dir_path = FP_PRIVACY_PLUGIN_PATH . 'blocks/' . $slug . '/';
		$dir_url  = FP_PRIVACY_PLUGIN_URL . 'blocks/' . $slug . '/';

		$script_handle = $handles[ $slug ]['script'];
		$style_handle  = $handles[ $slug ]['style'];

		if ( ! \wp_script_is( $script_handle, 'registered' ) && file_exists( $dir_path . 'edit.js' ) ) {
			\wp_register_script(
				$script_handle,
				$dir_url . 'edit.js',
				array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor' ),
				FP_PRIVACY_PLUGIN_VERSION,
				true
			);
		}

		if ( ! \wp_style_is( $style_handle, 'registered' ) && file_exists( $dir_path . 'style.css' ) ) {
			\wp_register_style(
				$style_handle,
				$dir_url . 'style.css',
				array(),
				FP_PRIVACY_PLUGIN_VERSION
			);
		}
	}

	/**
	 * Localize available languages into block editor script.
	 *
	 * @param string $slug   Block slug.
	 * @param string $handle Script handle.
	 *
	 * @return void
	 */
	private function maybe_localize_languages( $slug, $handle ) {
		$supported = array( 'privacy-policy', 'cookie-policy', 'cookie-preferences', 'cookie-banner' );

		if ( ! in_array( $slug, $supported, true ) ) {
			return;
		}

		if ( empty( $handle ) || isset( $this->localized[ $handle ] ) ) {
			return;
		}

		if ( ! \wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		$languages = array();
		$active    = $this->options->get_languages();

		foreach ( $active as $code ) {
			$normalized = $this->options->normalize_language( $code );
			if ( '' === $normalized ) {
				continue;
			}

			$languages[] = array(
				'code'  => $normalized,
				'label' => BlockPreviewData::format_language_label( $normalized ),
			);
		}

		if ( empty( $languages ) ) {
			$fallback = $this->options->normalize_language( \get_locale() );
			$languages[] = array(
				'code'  => $fallback,
				'label' => BlockPreviewData::format_language_label( $fallback ),
			);
		}

		$languages_json = \wp_json_encode( $languages );

		if ( false === $languages_json ) {
			return;
		}

		$script = 'window.fpPrivacyBlockData = window.fpPrivacyBlockData || {};' .
			'window.fpPrivacyBlockData.languages = ' . $languages_json . ';';

		if ( 'cookie-banner' === $slug ) {
			$preview = BlockPreviewData::get_banner_preview_data( $this->options, $languages );

			if ( ! empty( $preview ) ) {
				$preview_json = \wp_json_encode( $preview );

				if ( false !== $preview_json ) {
					$script .= 'window.fpPrivacyBlockData.bannerPreview = ' . $preview_json . ';';
				}
			}
		}

		\wp_add_inline_script( $handle, $script, 'before' );

		$this->localized[ $handle ] = true;
	}
}















