<?php
/**
 * Google Consent Mode integration.
 *
 * @package FP\Privacy\Integrations
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Integrations;

use FP\Privacy\Utils\Options;

/**
 * Outputs consent mode defaults and helper script.
 */
class ConsentMode {
/**
 * Options handler.
 *
 * @var Options
 */
private $options;

/**
 * Constructor.
 *
 * @param Options $options Options.
 */
public function __construct( Options $options ) {
$this->options = $options;
}

/**
 * Hooks.
 *
 * @return void
 */
public function hooks() {
    \add_action( 'wp_enqueue_scripts', array( $this, 'ensure_script' ), 5 );
    \add_action( 'wp_head', array( $this, 'print_defaults' ), 1 );
}

/**
 * Register script if missing.
 *
 * @return void
 */
public function ensure_script() {
if ( ! \wp_script_is( 'fp-privacy-consent-mode', 'registered' ) ) {
\wp_register_script( 'fp-privacy-consent-mode', FP_PRIVACY_PLUGIN_URL . 'assets/js/consent-mode.js', array(), FP_PRIVACY_PLUGIN_VERSION, true );
}
}

/**
 * Print default consent mode configuration.
 *
 * @return void
 */
public function print_defaults() {
    if ( ! \wp_script_is( 'fp-privacy-consent-mode', 'enqueued' ) ) {
        return;
    }

        $defaults = $this->options->get( 'consent_mode_defaults' );

        if ( ! \is_array( $defaults ) || empty( $defaults ) ) {
            return;
        }

        // Optional Global Privacy Control (GPC) handling: if enabled and GPC is detected,
        // force all non-necessary storages to 'denied' at default stage.
        $gpc_enabled = (bool) \apply_filters( 'fp_privacy_enable_gpc', false, $this->options );
        if ( $gpc_enabled && isset( $_SERVER['HTTP_SEC_GPC'] ) && '1' === (string) $_SERVER['HTTP_SEC_GPC'] ) {
            if ( \is_array( $defaults ) ) {
                $keys = array( 'analytics_storage', 'ad_storage', 'ad_user_data', 'ad_personalization', 'personalization_storage' );

                foreach ( $keys as $k ) {
                    if ( isset( $defaults[ $k ] ) ) {
                        $defaults[ $k ] = 'denied';
                    }
                }
            }
        }
    $object   = \wp_json_encode( $defaults );

    if ( false === $object ) {
        return;
    }

    $script = sprintf(
        '(function(){var defaults=%1$s;window.fpPrivacyConsentDefaults=defaults;window.dataLayer=window.dataLayer||[];if(typeof window.gtag==="function"){window.gtag("consent","default",defaults);}else{window.dataLayer.push(["consent","default",defaults]);}window.dataLayer.push({event:"gtm.init_consent",consentDefaults:defaults});})();',
        $object
);

\wp_print_inline_script_tag( $script );
}
}
