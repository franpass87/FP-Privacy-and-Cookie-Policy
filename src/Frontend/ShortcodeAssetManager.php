<?php
/**
 * Shortcode asset manager.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Utils\Options;

/**
 * Handles asset enqueuing for shortcodes.
 */
class ShortcodeAssetManager {
	/**
	 * Evita doppia `add_action` se più shortcode chiamano prima di `wp_enqueue_scripts`.
	 *
	 * @var bool
	 */
	private static $policy_enqueue_hook_registered = false;

	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Palette CSS builder.
	 *
	 * @var BannerPaletteBuilder
	 */
	private $palette_builder;

	/**
	 * Constructor.
	 *
	 * @param Options               $options          Options handler.
	 * @param BannerPaletteBuilder|null $palette_builder Builder (optional).
	 */
	public function __construct( Options $options, ?BannerPaletteBuilder $palette_builder = null ) {
		$this->options          = $options;
		$this->palette_builder = $palette_builder ?? new BannerPaletteBuilder();
	}

	/**
	 * Ensure banner assets load when the shortcode renders after wp_enqueue_scripts.
	 *
	 * @param string $lang Language override.
	 *
	 * @return void
	 */
	public function enqueue_banner_assets( $lang ) {
		$lang = '' !== $lang ? $this->options->normalize_language( $lang ) : '';

		if ( '' === $lang ) {
			$lang = \determine_locale();
		}

		\do_action( 'fp_privacy_enqueue_banner_assets', $lang );
	}

	/**
	 * Enqueue policy styles CSS.
	 *
	 * @return void
	 */
	public function enqueue_policy_styles() {
		if ( ! \wp_style_is( 'fp-privacy-policy-styles', 'enqueued' ) && ! \wp_style_is( 'fp-privacy-policy-styles', 'done' ) ) {
			if ( \did_action( 'wp_enqueue_scripts' ) ) {
				\wp_enqueue_style(
					'fp-privacy-policy-styles',
					FP_PRIVACY_PLUGIN_URL . 'assets/css/privacy-policy.css',
					array(),
					FP_PRIVACY_PLUGIN_VERSION
				);
				$this->add_policy_palette_inline();
			} elseif ( ! self::$policy_enqueue_hook_registered ) {
				self::$policy_enqueue_hook_registered = true;
				\add_action(
					'wp_enqueue_scripts',
					function () {
						\wp_enqueue_style(
							'fp-privacy-policy-styles',
							FP_PRIVACY_PLUGIN_URL . 'assets/css/privacy-policy.css',
							array(),
							FP_PRIVACY_PLUGIN_VERSION
						);
						$this->add_policy_palette_inline();
					},
					20
				);
			}
		}
	}

	/**
	 * Inietta variabili colore policy dalla palette banner (impostazioni).
	 *
	 * @return void
	 */
	private function add_policy_palette_inline(): void {
		$palette = $this->options->get_color_palette();
		\wp_add_inline_style(
			'fp-privacy-policy-styles',
			$this->palette_builder->build_policy_page_css( $palette )
		);
	}
}















