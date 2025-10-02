<?php
/**
 * Shortcodes.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Admin\PolicyGenerator;
use FP\Privacy\Utils\Options;
use FP\Privacy\Utils\View;

/**
 * Registers frontend shortcodes.
 */
class Shortcodes {
/**
 * Options handler.
 *
 * @var Options
 */
private $options;

    /**
     * View renderer.
     *
     * @var View
     */
    private $view;

    /**
     * Policy generator.
     *
     * @var PolicyGenerator
     */
    private $generator;

/**
 * Consent state.
 *
 * @var ConsentState
 */
private $state;

/**
 * Whether to force enqueue banner assets.
 *
 * @var bool
 */
private $force_enqueue = false;

/**
 * Constructor.
 *
     * @param Options         $options   Options handler.
     * @param View            $view      View renderer.
     * @param PolicyGenerator $generator Policy generator.
     */
    public function __construct( Options $options, View $view, PolicyGenerator $generator ) {
        $this->options   = $options;
        $this->view      = $view;
        $this->generator = $generator;
    }

/**
 * Inject consent state dependency.
 *
 * @param ConsentState $state State.
 *
 * @return void
 */
public function set_state( ConsentState $state ) {
$this->state = $state;
}

/**
 * Hooks.
 *
 * @return void
 */
public function hooks() {
\add_action( 'init', array( $this, 'register_shortcodes' ) );
\add_filter( 'fp_privacy_force_enqueue_banner', array( $this, 'maybe_force_enqueue' ) );
}

/**
 * Register shortcodes.
 *
 * @return void
 */
public function register_shortcodes() {
\add_shortcode( 'fp_privacy_policy', array( $this, 'render_privacy_policy' ) );
\add_shortcode( 'fp_cookie_policy', array( $this, 'render_cookie_policy' ) );
\add_shortcode( 'fp_cookie_preferences', array( $this, 'render_preferences_button' ) );
\add_shortcode( 'fp_cookie_banner', array( $this, 'render_cookie_banner' ) );
}

/**
 * Force enqueue when needed.
 *
 * @param bool $value Current value.
 *
 * @return bool
 */
public function maybe_force_enqueue( $value ) {
return $value || $this->force_enqueue;
}

/**
 * Render privacy policy template.
 *
 * @param array<string, string> $atts Attributes.
 *
 * @return string
 */
public function render_privacy_policy( $atts ) {
$atts = \shortcode_atts(
array(
'lang' => \get_locale(),
),
$atts,
'fp_privacy_policy'
);

        $html = $this->generator->generate_privacy_policy( $atts['lang'] );

        return \apply_filters( 'fp_privacy_policy_content', $html, $atts['lang'] );
    }

/**
 * Render cookie policy template.
 *
 * @param array<string, string> $atts Attributes.
 *
 * @return string
 */
public function render_cookie_policy( $atts ) {
$atts = \shortcode_atts(
array(
'lang' => \get_locale(),
),
$atts,
'fp_cookie_policy'
);

        $html = $this->generator->generate_cookie_policy( $atts['lang'] );

        return \apply_filters( 'fp_cookie_policy_content', $html, $atts['lang'] );
    }

/**
 * Render preferences button.
 *
 * @param array<string, string> $atts Attributes.
 *
 * @return string
 */
public function render_preferences_button( $atts ) {
$atts = \shortcode_atts(
array(
'label'       => \__( 'Manage cookie preferences', 'fp-privacy' ),
'lang'        => \get_locale(),
'description' => \__( 'Opens the cookie preferences modal so you can review or update your consent settings.', 'fp-privacy' ),
),
$atts,
'fp_cookie_preferences'
);

$label = \sanitize_text_field( $atts['label'] );
if ( '' === $label ) {
    $label = \__( 'Manage cookie preferences', 'fp-privacy' );
}

$lang = isset( $atts['lang'] ) ? $atts['lang'] : '';
$lang = \preg_replace( '/[^A-Za-z0-9_\\-]/', '', $lang );
if ( '' === $lang ) {
    $lang = \get_locale();
}

$lang = $this->options->normalize_language( $lang );

$this->force_enqueue = true;

if ( \did_action( 'wp_enqueue_scripts' ) ) {
    $this->enqueue_banner_assets( $lang );
}

$description = \sanitize_text_field( $atts['description'] );
if ( '' === $description ) {
    $description = \__( 'Opens the cookie preferences modal so you can review or update your consent settings.', 'fp-privacy' );
}

$state = $this->state ? $this->state->get_frontend_state( $lang ) : array( 'state' => array() );
        $last = isset( $state['state']['last_event'] ) ? $state['state']['last_event'] : '';

        if ( $last ) {
            $timestamp = \mysql2date( 'U', $last, true );
            $format    = trim( (string) \get_option( 'date_format' ) . ' ' . (string) \get_option( 'time_format' ) );

            if ( '' === $format ) {
                $format = 'F j, Y';
            }

            $formatted = $timestamp ? \wp_date( $format, $timestamp ) : $last;

            $description .= ' ' . \sprintf( \__( 'Last consent: %s', 'fp-privacy' ), $formatted );
        }

$description = \trim( $description );
$description_id = \wp_unique_id( 'fp-privacy-consent-hint-' );

return \sprintf(
'<button type="button" class="fp-privacy-preferences" data-fp-privacy-open aria-describedby="%2$s">%1$s</button><span id="%2$s" class="screen-reader-text">%3$s</span>',
\esc_html( $label ),
\esc_attr( $description_id ),
\esc_html( $description )
);
}

/**
 * Render banner container via shortcode.
 *
 * @param array<string, string> $atts Attributes.
 *
 * @return string
 */
public function render_cookie_banner( $atts ) {
    $atts = \shortcode_atts(
        array(
            'lang'     => '',
            'type'     => '',
            'position' => '',
            'force'    => '',
        ),
        $atts,
        'fp_cookie_banner'
    );

    $lang            = '' !== \trim( (string) $atts['lang'] ) ? \preg_replace( '/[^A-Za-z0-9_\\-]/', '', $atts['lang'] ) : '';
    $normalized_lang = '' !== $lang ? $this->options->normalize_language( $lang ) : '';

    $type     = in_array( $atts['type'], array( 'floating', 'bar' ), true ) ? $atts['type'] : '';
    $position = in_array( $atts['position'], array( 'top', 'bottom' ), true ) ? $atts['position'] : '';
    $force    = in_array( strtolower( (string) $atts['force'] ), array( '1', 'true', 'yes' ), true );

    $this->force_enqueue = true;

    if ( \did_action( 'wp_enqueue_scripts' ) ) {
        $this->enqueue_banner_assets( '' !== $normalized_lang ? $normalized_lang : $lang );
    }

    $attributes = array(
        'class'                  => 'fp-privacy-banner-shortcode',
        'data-fp-privacy-banner' => '1',
    );

    if ( '' !== $normalized_lang ) {
        $attributes['data-lang'] = $normalized_lang;
    }

    if ( '' !== $type ) {
        $attributes['data-layout-type'] = $type;
    }

    if ( '' !== $position ) {
        $attributes['data-layout-position'] = $position;
    }

    if ( $force ) {
        $attributes['data-force-display'] = '1';
    }

    $html = '<div';

    foreach ( $attributes as $key => $value ) {
        $html .= \sprintf( ' %s="%s"', $key, \esc_attr( $value ) );
    }

    $html .= '></div>';

    return $html;
}

/**
 * Ensure banner assets load when the shortcode renders after wp_enqueue_scripts.
 *
 * @param string $lang Language override.
 *
 * @return void
 */
private function enqueue_banner_assets( $lang ) {
    $lang = '' !== $lang ? $this->options->normalize_language( $lang ) : '';

    if ( '' === $lang ) {
        $lang = \determine_locale();
    }

    \do_action( 'fp_privacy_enqueue_banner_assets', $lang );
}
}
