<?php
/**
 * Banner asset manager.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Frontend\ConsentState;
use FP\Privacy\Utils\Options;

/**
 * Handles asset enqueuing for banner.
 */
class BannerAssetManager {
	/**
	 * Options handler.
	 *
	 * @var Options
	 */
	private $options;

	/**
	 * Consent state.
	 *
	 * @var ConsentState
	 */
	private $state;

	/**
	 * Palette builder.
	 *
	 * @var BannerPaletteBuilder
	 */
	private $palette_builder;

	/**
	 * Constructor.
	 *
	 * @param Options              $options        Options handler.
	 * @param ConsentState         $state          Consent state.
	 * @param BannerPaletteBuilder $palette_builder Palette builder.
	 */
	public function __construct( Options $options, ConsentState $state, BannerPaletteBuilder $palette_builder ) {
		$this->options        = $options;
		$this->state          = $state;
		$this->palette_builder = $palette_builder;
	}

	/**
	 * Perform enqueue logic.
	 *
	 * @param string $lang Optional language override.
	 *
	 * @return void
	 */
	public function maybe_enqueue_assets( $lang = '' ) {
		$lang      = '' !== $lang ? $this->options->normalize_language( $lang ) : \determine_locale();
		$state     = $this->state->get_frontend_state( $lang );
		$should    = ! empty( $state['state']['should_display'] );
		$preview   = ! empty( $state['state']['preview_mode'] );
		$shortcode = \apply_filters( 'fp_privacy_force_enqueue_banner', false );

		$consent_handle = 'fp-privacy-consent-mode';
		$banner_handle  = 'fp-privacy-banner';

		\wp_register_script( $consent_handle, FP_PRIVACY_PLUGIN_URL . 'assets/js/consent-mode.js', array(), FP_PRIVACY_PLUGIN_VERSION, true );

		// Propagate GPC enablement to the front-end so consent-mode.js can honor it client-side
		$gpc_enabled = (bool) $this->options->get( 'gpc_enabled', false );
		\wp_add_inline_script( $consent_handle, 'window.fpPrivacyEnableGPC=' . ( $gpc_enabled ? 'true' : 'false' ) . ';', 'before' );

		\wp_localize_script(
			$consent_handle,
			'FP_PRIVACY_DATA',
			array(
				'ajaxUrl'   => \admin_url( 'admin-ajax.php' ),
				'nonce'     => \wp_create_nonce( 'fp-privacy-consent' ),
				'options'   => $state,
				'cookie'    => array(
					'name'     => ConsentState::COOKIE_NAME,
					'duration' => (int) \apply_filters( 'fp_privacy_cookie_duration_days', \FP\Privacy\Shared\Constants::COOKIE_DURATION_DAYS_DEFAULT ),
				),
				'rest'      => array(
					'url'   => \esc_url_raw( \rest_url( 'fp-privacy/v1/consent' ) ),
					'nonce' => \wp_create_nonce( 'wp_rest' ),
				),
			)
		);

		\wp_enqueue_script( $consent_handle );

		if ( ! $should && ! $preview && ! $shortcode ) {
			$bootstrap = "(function(){try{var data=window.FP_PRIVACY_DATA;if(!data||!data.options){return;}var state=data.options.state||{};if(state.should_display||state.preview_mode){return;}var consent=window.fpPrivacyConsent;if(!consent||typeof consent.update!==\"function\"){return;}var mapper=typeof consent.mapBannerPayload===\"function\"?consent.mapBannerPayload:null;var categories=state.categories||{};var defaults=(data.options.mode)||{};if(mapper){var payload=mapper(categories,{defaults:defaults});if(payload){consent.update(payload);}}}catch(e){}})();";

			\wp_add_inline_script( $consent_handle, $bootstrap, 'after' );

			return;
		}

		\wp_register_style( $banner_handle, FP_PRIVACY_PLUGIN_URL . 'assets/css/banner.css', array(), FP_PRIVACY_PLUGIN_VERSION );
		\wp_register_script( $banner_handle, FP_PRIVACY_PLUGIN_URL . 'assets/js/banner.js', array( $consent_handle ), FP_PRIVACY_PLUGIN_VERSION, true );

		\wp_enqueue_style( $banner_handle );
		\wp_add_inline_style(
			$banner_handle,
			$this->palette_builder->build_palette_css(
				isset( $state['layout']['palette'] ) ? $state['layout']['palette'] : array(),
				! empty( $state['layout']['sync_modal_and_button'] )
			)
		);

		\wp_enqueue_script( $banner_handle );
	}
}















