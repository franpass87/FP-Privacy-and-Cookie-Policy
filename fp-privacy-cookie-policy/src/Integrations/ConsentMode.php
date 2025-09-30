<?php
/**
 * Google Consent Mode integration.
 *
 * @package FP\Privacy\Integrations
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
\add_action( 'wp_footer', array( $this, 'print_defaults' ), 1 );
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
$object   = \\wp_json_encode( $defaults );

$script = sprintf(
    '(function(){var defaults=%1$s;window.fpPrivacyConsentDefaults=defaults;window.dataLayer=window.dataLayer||[];if(typeof window.gtag==="function"){window.gtag("consent","default",defaults);}else{window.dataLayer.push(["consent","default",defaults]);}window.dataLayer.push({event:"gtm.init_consent",consentDefaults:defaults});})();',
    $object
);

\wp_print_inline_script_tag( $script );
}
}
