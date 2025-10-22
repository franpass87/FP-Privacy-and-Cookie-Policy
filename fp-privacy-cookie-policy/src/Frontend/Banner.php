<?php
/**
 * Frontend banner.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Utils\Options;

/**
 * Handles banner rendering and assets.
 */
class Banner {
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
     * Tracks whether the banner markup has been rendered.
     *
     * @var bool
     */
    private $rendered = false;

/**
 * Constructor.
 *
 * @param Options      $options Options.
 * @param ConsentState $state   Consent state.
 */
public function __construct( Options $options, ConsentState $state ) {
$this->options = $options;
$this->state   = $state;
}

/**
 * Hooks.
 *
 * @return void
 */
    public function hooks() {
        \add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        \add_action( 'fp_privacy_enqueue_banner_assets', array( $this, 'enqueue_assets_forced' ), 10, 1 );
        \add_action( 'wp_body_open', array( $this, 'render_banner' ), 20 );
        \add_action( 'wp_footer', array( $this, 'render_banner' ), 5 );
    }

    /**
     * Enqueue assets when necessary.
     *
     * @return void
     */
    public function enqueue_assets() {
        $this->maybe_enqueue_assets();
    }

    /**
     * Enqueue assets when a shortcode renders after wp_enqueue_scripts.
     *
     * @param string $lang Language override.
     *
     * @return void
     */
    public function enqueue_assets_forced( $lang = '' ) {
        $this->maybe_enqueue_assets( $lang );
    }

    /**
     * Perform enqueue logic.
     *
     * @param string $lang Optional language override.
     *
     * @return void
     */
    private function maybe_enqueue_assets( $lang = '' ) {
        $lang      = '' !== $lang ? $this->options->normalize_language( $lang ) : \determine_locale();
        $state     = $this->state->get_frontend_state( $lang );
        $should    = ! empty( $state['state']['should_display'] );
        $preview   = ! empty( $state['state']['preview_mode'] );
        $shortcode = \apply_filters( 'fp_privacy_force_enqueue_banner', false );
        
        // Aggiungi classe dark mode al body se abilitato
        $dark_mode_enabled = ! empty( $state['layout']['enable_dark_mode'] );
        if ( $dark_mode_enabled ) {
            \add_filter( 'body_class', function( $classes ) {
                $classes[] = 'fp-privacy-dark-mode-enabled';
                return $classes;
            });
        }

        $consent_handle = 'fp-privacy-consent-mode';
        $banner_handle  = 'fp-privacy-banner';

        \wp_register_script( $consent_handle, FP_PRIVACY_PLUGIN_URL . 'assets/js/consent-mode.js', array(), FP_PRIVACY_PLUGIN_VERSION, false );

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
                    'duration' => (int) \apply_filters( 'fp_privacy_cookie_duration_days', 180 ),
                ),
                'rest'      => array(
                    'url'   => \esc_url_raw( \rest_url( 'fp-privacy/v1/consent' ) ),
                    'nonce' => \wp_create_nonce( 'wp_rest' ),
                ),
            )
        );

        \wp_enqueue_script( $consent_handle );
        
        // Add script loading optimization
        \add_filter( 'script_loader_tag', function( $tag, $handle, $src ) use ( $consent_handle, $banner_handle ) {
            if ( $handle === $consent_handle || $handle === $banner_handle ) {
                return str_replace( '<script ', '<script defer ', $tag );
            }
            return $tag;
        }, 10, 3 );

        if ( ! $should && ! $preview && ! $shortcode ) {
            $bootstrap = "(function(){try{var data=window.FP_PRIVACY_DATA;if(!data||!data.options){return;}var state=data.options.state||{};if(state.should_display||state.preview_mode){return;}var consent=window.fpPrivacyConsent;if(!consent||typeof consent.update!==\"function\"){return;}var mapper=typeof consent.mapBannerPayload===\"function\"?consent.mapBannerPayload:null;var categories=state.categories||{};var defaults=(data.options.mode)||{};if(mapper){var payload=mapper(categories,{defaults:defaults});if(payload){consent.update(payload);}}}catch(e){}})();";

            \wp_add_inline_script( $consent_handle, $bootstrap, 'after' );

            return;
        }

        \wp_register_style( $banner_handle, FP_PRIVACY_PLUGIN_URL . 'assets/css/banner.css', array(), FP_PRIVACY_PLUGIN_VERSION );
        \wp_register_script( $banner_handle, FP_PRIVACY_PLUGIN_URL . 'assets/js/banner.js', array( $consent_handle ), FP_PRIVACY_PLUGIN_VERSION, false );

        \wp_enqueue_style( $banner_handle );
        \wp_add_inline_style(
            $banner_handle,
            $this->build_palette_css(
                isset( $state['layout']['palette'] ) ? $state['layout']['palette'] : array(),
                ! empty( $state['layout']['sync_modal_and_button'] )
            )
        );

        \wp_enqueue_script( $banner_handle );
    }

/**
 * Render banner container.
 *
 * @return void
 */
public function render_banner() {
        if ( $this->rendered ) {
            return;
        }

        $this->rendered = true;

        echo '<div id="fp-privacy-banner-root" aria-live="polite"></div>';
    }

    /**
     * Build palette CSS variables.
     *
     * @param array<string, string> $palette    Palette.
     * @param bool                  $sync_modal Whether modal styling should mirror the banner.
     *
     * @return string
     */
    private function build_palette_css( $palette, $sync_modal = false ) {
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
