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
	 * Options handler.
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
			} else {
				\add_action( 'wp_enqueue_scripts', function() {
					\wp_enqueue_style(
						'fp-privacy-policy-styles',
						FP_PRIVACY_PLUGIN_URL . 'assets/css/privacy-policy.css',
						array(),
						FP_PRIVACY_PLUGIN_VERSION
					);
				}, 20 );
			}
		}
	}
}















