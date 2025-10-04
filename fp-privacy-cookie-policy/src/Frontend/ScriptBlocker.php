<?php
/**
 * Blocks scripts and embeds until consent is granted.
 *
 * @package FP\Privacy\Frontend
 */

namespace FP\Privacy\Frontend;

use FP\Privacy\Utils\Options;

use function __;
use function array_slice;
use function base64_encode;
use function esc_attr;
use function esc_html;
use function esc_html__;
use function is_admin;
use function is_array;
use function preg_match;
use function preg_replace_callback;
use function sanitize_key;
use function stripos;
use function trim;

/**
 * Transforms matching assets into inert placeholders.
 */
class ScriptBlocker {
    /**
     * Options handler.
     *
     * @var Options
     */
    private $options;

    /**
     * Consent state handler.
     *
     * @var ConsentState
     */
    private $state;

    /**
     * Whether the component has been prepared.
     *
     * @var bool
     */
    private $prepared = false;

    /**
     * Current language.
     *
     * @var string
     */
    private $language = '';

    /**
     * Blocking rules.
     *
     * @var array<string, mixed>
     */
    private $rules = array();

    /**
     * Allowed categories map.
     *
     * @var array<string, bool>
     */
    private $allowed = array();

    /**
     * Category labels for placeholders.
     *
     * @var array<string, string>
     */
    private $category_labels = array();

    /**
     * Constructor.
     *
     * @param Options      $options Options handler.
     * @param ConsentState $state   Consent state.
     */
    public function __construct( Options $options, ConsentState $state ) {
        $this->options = $options;
        $this->state   = $state;
    }

    /**
     * Register hooks when not in the admin area.
     *
     * @return void
     */
    public function hooks() {
        if ( is_admin() ) {
            return;
        }

        \add_action( 'init', array( $this, 'prepare' ), 5 );
        \add_filter( 'script_loader_tag', array( $this, 'filter_script_tag' ), 999, 3 );
        \add_filter( 'style_loader_tag', array( $this, 'filter_style_tag' ), 999, 4 );
        \add_filter( 'the_content', array( $this, 'filter_content' ), 9 );
        \add_filter( 'widget_text_content', array( $this, 'filter_content' ), 9 );
        \add_filter( 'widget_block_content', array( $this, 'filter_content' ), 9 );
    }

    /**
     * Hydrate rules and state.
     *
     * @return void
     */
    public function prepare() {
        if ( $this->prepared ) {
            return;
        }

        $locale = '';

        if ( \function_exists( '\\determine_locale' ) ) {
            $locale = \determine_locale();
        }

        if ( '' === $locale && \function_exists( '\\get_locale' ) ) {
            $locale = \get_locale();
        }

        $locale        = $this->options->normalize_language( $locale ?: 'en_US' );
        $rules_by_cat  = $this->options->get_script_rules_for_language( $locale );
        $categories    = $this->options->get_categories_for_language( $locale );
        $frontend      = $this->state->get_frontend_state( $locale );
        $consent_state = array();
        $preview       = false;

        if ( isset( $frontend['state'] ) && is_array( $frontend['state'] ) ) {
            $preview = ! empty( $frontend['state']['preview_mode'] );

            if ( isset( $frontend['state']['categories'] ) && is_array( $frontend['state']['categories'] ) ) {
                $consent_state = $frontend['state']['categories'];
            }
        }

        $script_handles = array();
        $style_handles  = array();
        $script_patterns = array();
        $iframe_patterns = array();

        foreach ( $rules_by_cat as $category => $rule_set ) {
            if ( empty( $rule_set ) || ! is_array( $rule_set ) ) {
                continue;
            }

            if ( ! empty( $rule_set['script_handles'] ) && is_array( $rule_set['script_handles'] ) ) {
                foreach ( $rule_set['script_handles'] as $handle ) {
                    $script_handles[ $handle ] = $category;
                }
            }

            if ( ! empty( $rule_set['style_handles'] ) && is_array( $rule_set['style_handles'] ) ) {
                foreach ( $rule_set['style_handles'] as $handle ) {
                    $style_handles[ $handle ] = $category;
                }
            }

            if ( ! empty( $rule_set['patterns'] ) && is_array( $rule_set['patterns'] ) ) {
                foreach ( $rule_set['patterns'] as $pattern ) {
                    $pattern = trim( $pattern );

                    if ( '' === $pattern ) {
                        continue;
                    }

                    $script_patterns[] = array(
                        'pattern'  => $pattern,
                        'category' => $category,
                    );
                }
            }

            if ( ! empty( $rule_set['iframes'] ) && is_array( $rule_set['iframes'] ) ) {
                foreach ( $rule_set['iframes'] as $pattern ) {
                    $pattern = trim( $pattern );

                    if ( '' === $pattern ) {
                        continue;
                    }

                    $iframe_patterns[] = array(
                        'pattern'  => $pattern,
                        'category' => $category,
                    );
                }
            }
        }

        $this->language        = $locale;
        $this->rules           = array(
            'script_handles'  => $script_handles,
            'style_handles'   => $style_handles,
            'script_patterns' => $script_patterns,
            'iframe_patterns' => $iframe_patterns,
        );
        $this->category_labels = array();
        $this->allowed         = array();

        foreach ( $categories as $slug => $meta ) {
            $this->category_labels[ $slug ] = isset( $meta['label'] ) ? (string) $meta['label'] : $slug;

            if ( $preview ) {
                $this->allowed[ $slug ] = true;
                continue;
            }

            if ( ! empty( $meta['locked'] ) ) {
                $this->allowed[ $slug ] = true;
                continue;
            }

            $this->allowed[ $slug ] = isset( $consent_state[ $slug ] ) ? (bool) $consent_state[ $slug ] : false;
        }

        $this->prepared = true;
    }

    /**
     * Replace script tags when necessary.
     *
     * @param string $tag    Original tag HTML.
     * @param string $handle Script handle.
     * @param string $src    Source URL.
     *
     * @return string
     */
    public function filter_script_tag( $tag, $handle, $src ) {
        $this->prepare();

        if ( empty( $this->rules['script_handles'] ) && empty( $this->rules['script_patterns'] ) ) {
            return $tag;
        }

        $category = '';
        $normalized_handle = sanitize_key( $handle );

        if ( '' !== $normalized_handle && isset( $this->rules['script_handles'][ $normalized_handle ] ) ) {
            $category = $this->rules['script_handles'][ $normalized_handle ];
        }

        if ( '' === $category && '' !== $src ) {
            $category = $this->match_pattern_category( $src, $this->rules['script_patterns'] );
        }

        if ( '' === $category || ! $this->should_block_category( $category ) ) {
            return $tag;
        }

        return $this->build_placeholder( $tag, 'script', $category );
    }

    /**
     * Replace style tags when necessary.
     *
     * @param string $tag    Original tag HTML.
     * @param string $handle Style handle.
     * @param string $href   Stylesheet href.
     * @param string $media  Media attribute.
     *
     * @return string
     */
    public function filter_style_tag( $tag, $handle, $href, $media ) {
        $this->prepare();

        if ( empty( $this->rules['style_handles'] ) ) {
            return $tag;
        }

        $normalized_handle = sanitize_key( $handle );

        if ( '' === $normalized_handle || ! isset( $this->rules['style_handles'][ $normalized_handle ] ) ) {
            return $tag;
        }

        $category = $this->rules['style_handles'][ $normalized_handle ];

        if ( ! $this->should_block_category( $category ) ) {
            return $tag;
        }

        return $this->build_placeholder( $tag, 'style', $category );
    }

    /**
     * Filter post content to block inline scripts and iframes.
     *
     * @param string $content HTML content.
     *
     * @return string
     */
    public function filter_content( $content ) {
        $this->prepare();

        $result = $content;

        if ( ! empty( $this->rules['script_patterns'] ) ) {
            $result = preg_replace_callback(
                '/<script\b[^>]*>.*?<\/script>/is',
                function ( $matches ) {
                    $tag = $matches[0];
                    $category = '';

                    if ( preg_match( '/src\s*=\s*"([^"]+)"/i', $tag, $src_match ) ) {
                        $category = $this->match_pattern_category( $src_match[1], $this->rules['script_patterns'] );
                    }

                    if ( '' === $category || ! $this->should_block_category( $category ) ) {
                        return $tag;
                    }

                    return $this->build_placeholder( $tag, 'script', $category );
                },
                $result
            );
        }

        if ( ! empty( $this->rules['iframe_patterns'] ) ) {
            $result = preg_replace_callback(
                '/<iframe\b[^>]*>.*?<\/iframe>/is',
                function ( $matches ) {
                    $tag = $matches[0];
                    $category = '';

                    if ( preg_match( '/src\s*=\s*"([^"]+)"/i', $tag, $src_match ) ) {
                        $category = $this->match_pattern_category( $src_match[1], $this->rules['iframe_patterns'] );
                    }

                    if ( '' === $category || ! $this->should_block_category( $category ) ) {
                        return $tag;
                    }

                    return $this->build_placeholder( $tag, 'iframe', $category );
                },
                $result
            );
        }

        return $result;
    }

    /**
     * Determine whether a category should be blocked.
     *
     * @param string $category Category slug.
     *
     * @return bool
     */
    private function should_block_category( $category ) {
        if ( isset( $this->allowed[ $category ] ) ) {
            return ! $this->allowed[ $category ];
        }

        return true;
    }

    /**
     * Attempt to match a value against configured patterns.
     *
     * @param string $value    Candidate string.
     * @param array  $patterns Pattern definitions.
     *
     * @return string
     */
    private function match_pattern_category( $value, $patterns ) {
        if ( empty( $patterns ) || '' === $value ) {
            return '';
        }

        foreach ( $patterns as $entry ) {
            if ( empty( $entry['pattern'] ) || ! isset( $entry['category'] ) ) {
                continue;
            }

            if ( false !== stripos( $value, $entry['pattern'] ) ) {
                return (string) $entry['category'];
            }
        }

        return '';
    }

    /**
     * Build placeholder markup for the provided HTML chunk.
     *
     * @param string $original Original HTML.
     * @param string $type     Placeholder type (script|style|iframe).
     * @param string $category Consent category.
     *
     * @return string
     */
    private function build_placeholder( $original, $type, $category ) {
        $encoded = base64_encode( $original );

        if ( false === $encoded ) {
            return $original;
        }

        $label        = isset( $this->category_labels[ $category ] ) ? $this->category_labels[ $category ] : $category;
        $category_attr = esc_attr( $category );
        $encoded_attr  = esc_attr( $encoded );

        if ( 'script' === $type ) {
            return sprintf(
                '<script type="text/plain" data-fp-privacy-blocked="script" data-fp-privacy-category="%1$s" data-fp-privacy-replace="%2$s"></script>',
                $category_attr,
                $encoded_attr
            );
        }

        if ( 'style' === $type ) {
            return sprintf(
                '<span class="fp-privacy-style-placeholder" data-fp-privacy-blocked="style" data-fp-privacy-category="%1$s" data-fp-privacy-replace="%2$s"></span>',
                $category_attr,
                $encoded_attr
            );
        }

        $message = sprintf(
            __( 'Content blocked until %s consent is granted.', 'fp-privacy' ),
            $label
        );

        return sprintf(
            '<div class="fp-privacy-blocked" data-fp-privacy-blocked="iframe" data-fp-privacy-category="%1$s" data-fp-privacy-replace="%2$s"><p>%3$s</p><button type="button" class="button" data-fp-privacy-open="1">%4$s</button></div>',
            $category_attr,
            $encoded_attr,
            esc_html( $message ),
            esc_html__( 'Manage preferences', 'fp-privacy' )
        );
    }
}
