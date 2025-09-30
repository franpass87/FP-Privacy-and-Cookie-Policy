<?php
/**
 * Frontend banner.
 *
 * @package FP\Privacy\Frontend
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
\add_action( 'wp_footer', array( $this, 'render_banner' ) );
}

/**
 * Enqueue assets when necessary.
 *
 * @return void
 */
public function enqueue_assets() {
$lang      = \determine_locale();
$state     = $this->state->get_frontend_state( $lang );
$should    = $state['state']['should_display'];
$preview   = $state['state']['preview_mode'];
$shortcode = \apply_filters( 'fp_privacy_force_enqueue_banner', false );

if ( ! $should && ! $preview && ! $shortcode ) {
return;
}

$handle = 'fp-privacy-banner';
\wp_register_style( $handle, FP_PRIVACY_PLUGIN_URL . 'assets/css/banner.css', array(), FP_PRIVACY_PLUGIN_VERSION );
\wp_register_script( $handle, FP_PRIVACY_PLUGIN_URL . 'assets/js/banner.js', array(), FP_PRIVACY_PLUGIN_VERSION, true );
\wp_register_script( 'fp-privacy-consent-mode', FP_PRIVACY_PLUGIN_URL . 'assets/js/consent-mode.js', array(), FP_PRIVACY_PLUGIN_VERSION, true );

\wp_enqueue_style( $handle );
\wp_add_inline_style( $handle, $this->build_palette_css( $state['layout']['palette'] ) );

\wp_enqueue_script( 'fp-privacy-consent-mode' );
\wp_enqueue_script( $handle );

\wp_localize_script(
$handle,
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
}

/**
 * Render banner container.
 *
 * @return void
 */
public function render_banner() {
echo '<div id="fp-privacy-banner-root" aria-live="polite"></div>';
}

/**
 * Build palette CSS variables.
 *
 * @param array<string, string> $palette Palette.
 *
 * @return string
 */
private function build_palette_css( $palette ) {
$css = ':root {';
foreach ( $palette as $key => $value ) {
$css .= '--fp-privacy-' . \sanitize_key( $key ) . ':' . \sanitize_hex_color( $value ) . ';';
}
$css .= '}';

return $css;
}
}
