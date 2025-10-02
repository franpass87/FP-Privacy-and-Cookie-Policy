<?php
/**
 * Block registration.
 *
 * @package FP\Privacy\Frontend
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Utils\Options;

/**
 * Registers Gutenberg blocks.
 */
class Blocks {
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
     * Hooks.
     *
     * @return void
     */
    public function hooks() {
        \add_action( 'init', array( $this, 'register_blocks' ) );
    }

    /**
     * Register blocks.
     *
     * @return void
     */
    public function register_blocks() {
        $blocks = array(
            'privacy-policy'     => array( $this, 'render_privacy_policy_block' ),
            'cookie-policy'      => array( $this, 'render_cookie_policy_block' ),
            'cookie-preferences' => array( $this, 'render_preferences_block' ),
            'cookie-banner'      => array( $this, 'render_banner_block' ),
        );

        foreach ( $blocks as $slug => $callback ) {
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
     * Render privacy policy block.
     *
     * @param array<string, mixed> $attributes Attributes.
     *
     * @return string
     */
    public function render_privacy_policy_block( $attributes ) {
        $lang = isset( $attributes['lang'] ) ? \sanitize_text_field( $attributes['lang'] ) : \get_locale();

        return \do_shortcode( '[fp_privacy_policy lang="' . \esc_attr( $lang ) . '"]' );
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
                'label' => $this->format_language_label( $normalized ),
            );
        }

        if ( empty( $languages ) ) {
            $fallback = $this->options->normalize_language( \get_locale() );
            $languages[] = array(
                'code'  => $fallback,
                'label' => $this->format_language_label( $fallback ),
            );
        }

        $languages_json = \wp_json_encode( $languages );

        if ( false === $languages_json ) {
            return;
        }

        $script = 'window.fpPrivacyBlockData = window.fpPrivacyBlockData || {};' .
            'window.fpPrivacyBlockData.languages = ' . $languages_json . ';';

        if ( 'cookie-banner' === $slug ) {
            $preview = $this->get_banner_preview_data( $languages );

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

    /**
     * Build preview data for banner texts.
     *
     * @param array<int, array<string, string>> $languages Registered languages list.
     *
     * @return array<string, array<string, string>>
     */
    private function get_banner_preview_data( array $languages ) {
        $preview = array();

        if ( empty( $languages ) ) {
            $languages[] = array(
                'code' => $this->options->normalize_language( \get_locale() ),
            );
        }

        foreach ( $languages as $language ) {
            if ( empty( $language['code'] ) ) {
                continue;
            }

            $code  = $this->options->normalize_language( $language['code'] );
            $texts = $this->options->get_banner_text( $code );

            $preview[ $code ] = array(
                'title'   => $this->prepare_preview_text( $texts['title'] ?? '' ),
                'message' => $this->prepare_preview_text( $texts['message'] ?? '' ),
                'accept'  => $this->prepare_preview_text( $texts['btn_accept'] ?? '' ),
                'reject'  => $this->prepare_preview_text( $texts['btn_reject'] ?? '' ),
                'prefs'   => $this->prepare_preview_text( $texts['btn_prefs'] ?? '' ),
            );
        }

        return $preview;
    }

    /**
     * Clean preview text for editor usage.
     *
     * @param string $text Raw text.
     *
     * @return string
     */
    private function prepare_preview_text( $text ) {
        $clean = \wp_strip_all_tags( (string) $text );

        $clean = \trim( \html_entity_decode( $clean, ENT_QUOTES, \get_bloginfo( 'charset' ) ?: 'UTF-8' ) );

        if ( '' === $clean ) {
            return '';
        }

        if ( \function_exists( 'mb_strlen' ) && \function_exists( 'mb_substr' ) ) {
            if ( \mb_strlen( $clean, 'UTF-8' ) > 320 ) {
                $clean = \rtrim( \mb_substr( $clean, 0, 317, 'UTF-8' ) ) . '…';
            }
        } elseif ( \strlen( $clean ) > 320 ) {
            $clean = \rtrim( \substr( $clean, 0, 317 ) ) . '…';
        }

        return $clean;
    }

    /**
     * Build a human readable label for the locale.
     *
     * @param string $code Locale code.
     *
     * @return string
     */
    private function format_language_label( $code ) {
        $label = $code;
        $locale = str_replace( '_', '-', $code );

        if ( class_exists( '\\Locale' ) ) {
            try {
                $display = \Locale::getDisplayName( $locale, $locale );
                if ( $display ) {
                    $label = \ucwords( $display );
                }
            } catch ( \Throwable $e ) {
                // Fallback to the code when intl is not available.
            }
        }

        return $label;
    }

    /**
     * Render cookie policy block.
     *
     * @param array<string, mixed> $attributes Attributes.
     *
     * @return string
     */
    public function render_cookie_policy_block( $attributes ) {
        $lang = isset( $attributes['lang'] ) ? \sanitize_text_field( $attributes['lang'] ) : \get_locale();

        return \do_shortcode( '[fp_cookie_policy lang="' . \esc_attr( $lang ) . '"]' );
    }

    /**
     * Render preferences block.
     *
     * @param array<string, mixed> $attributes Attributes.
     *
     * @return string
     */
    public function render_preferences_block( $attributes ) {
        $label = isset( $attributes['label'] ) && '' !== \trim( (string) $attributes['label'] )
            ? \sanitize_text_field( $attributes['label'] )
            : \__( 'Manage cookie preferences', 'fp-privacy' );

        $description = isset( $attributes['description'] ) && '' !== \trim( (string) $attributes['description'] )
            ? \sanitize_text_field( $attributes['description'] )
            : '';

        $lang = isset( $attributes['lang'] ) && '' !== \trim( (string) $attributes['lang'] )
            ? \preg_replace( '/[^A-Za-z0-9_\\-]/', '', $attributes['lang'] )
            : '';

        $atts = array(
            'label' => \esc_attr( $label ),
        );

        if ( '' !== $description ) {
            $atts['description'] = \esc_attr( $description );
        }

        if ( '' !== $lang ) {
            $atts['lang'] = \esc_attr( $lang );
        }

        $attr_string = '';

        foreach ( $atts as $key => $value ) {
            $attr_string .= \sprintf( ' %s="%s"', $key, $value );
        }

        return \do_shortcode( '[fp_cookie_preferences' . $attr_string . ']' );
    }

    /**
     * Render banner block.
     *
     * @param array<string, mixed> $attributes Attributes.
     *
     * @return string
     */
    public function render_banner_block( $attributes ) {
        $attrs = array();

        $layout = isset( $attributes['layoutType'] ) ? $attributes['layoutType'] : '';
        $position = isset( $attributes['position'] ) ? $attributes['position'] : '';
        $lang = isset( $attributes['lang'] ) ? $attributes['lang'] : '';

        if ( 'bar' === $layout ) {
            $attrs['type'] = 'bar';
        } elseif ( 'floating' === $layout ) {
            $attrs['type'] = 'floating';
        }

        if ( 'top' === $position ) {
            $attrs['position'] = 'top';
        } elseif ( 'bottom' === $position ) {
            $attrs['position'] = 'bottom';
        }

        if ( \is_string( $lang ) && '' !== \trim( $lang ) ) {
            $attrs['lang'] = \preg_replace( '/[^A-Za-z0-9_\\-]/', '', $lang );
        }

        if ( ! empty( $attributes['forceDisplay'] ) ) {
            $attrs['force'] = '1';
        }

        $attr_string = '';

        foreach ( $attrs as $key => $value ) {
            $attr_string .= \sprintf( ' %s="%s"', $key, \esc_attr( $value ) );
        }

        return \do_shortcode( '[fp_cookie_banner' . $attr_string . ']' );
    }
}
