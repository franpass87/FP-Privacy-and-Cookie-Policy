<?php
/**
 * Options handler.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

use WP_Post;

/**
 * Options utility class.
 */
class Options {
    const OPTION_KEY = 'fp_privacy_options';

    const PAGE_MANAGED_META_KEY = '_fp_privacy_managed_signature';

/**
 * Cached options.
 *
 * @var array<string, mixed>
 */
private $options = array();

/**
 * Blog identifier the options were loaded for.
 *
 * @var int
 */
private $blog_id = 0;

/**
 * Instance.
 *
 * @var Options
 */
private static $instance;

/**
 * Get singleton instance.
 *
 * @return Options
 */
public static function instance() {
$current_blog_id = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 0;

if ( ! self::$instance || self::$instance->blog_id !== $current_blog_id ) {
self::$instance = new self();
}

return self::$instance;
}

/**
 * Constructor.
 */
private function __construct() {
$this->blog_id = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 0;
$this->options = $this->load();
}

/**
 * Get raw options.
 *
 * @return array<string, mixed>
 */
public function all() {
return $this->options;
}

/**
 * Get a specific option value.
 *
 * @param string $key Option key.
 * @param mixed  $default Default value.
 *
 * @return mixed
 */
public function get( $key, $default = null ) {
if ( isset( $this->options[ $key ] ) ) {
return $this->options[ $key ];
}

return $default;
}

/**
 * Set options.
 *
 * @param array<string, mixed> $new_options New options.
 *
 * @return void
 */
public function set( array $new_options ) {
$defaults  = $this->get_default_options();
$merged    = \wp_parse_args( $new_options, $this->options );
$sanitized = $this->sanitize( $merged, $defaults );
$this->options = $sanitized;

\update_option( self::OPTION_KEY, $sanitized, false );

$this->ensure_pages_exist();
}

/**
 * Load options from the database.
 *
 * @return array<string, mixed>
 */
private function load() {
$stored   = \get_option( self::OPTION_KEY );
$defaults = $this->get_default_options();

if ( ! is_array( $stored ) ) {
return $defaults;
}

return $this->sanitize( \wp_parse_args( $stored, $defaults ), $defaults );
}

/**
 * Get defaults.
 *
 * @return array<string, mixed>
 */
public function get_default_options() {
$default_locale = \get_locale();
$default_palette = array(
    'surface_bg'          => '#0B1220',
    'surface_text'        => '#FFFFFF',
    'button_primary_bg'   => '#4C7CF6',
    'button_primary_tx'   => '#FFFFFF',
    'button_secondary_bg' => '#E5E7EB',
    'button_secondary_tx' => '#111827',
    'link'                => '#2563EB',
    'border'              => '#D1D5DB',
    'focus'               => '#3B82F6',
);

$banner_default = array(
    'title'          => \__( 'We value your privacy', 'fp-privacy' ),
    'message'        => \__( 'We use cookies to improve your experience. You can accept all cookies or manage your preferences.', 'fp-privacy' ),
    'btn_accept'     => \__( 'Accept all', 'fp-privacy' ),
    'btn_reject'     => \__( 'Reject all', 'fp-privacy' ),
    'btn_prefs'      => \__( 'Manage preferences', 'fp-privacy' ),
    'modal_title'    => \__( 'Privacy preferences', 'fp-privacy' ),
    'modal_close'    => \__( 'Close preferences', 'fp-privacy' ),
    'modal_save'     => \__( 'Save preferences', 'fp-privacy' ),
    'revision_notice'=> \__( 'We have updated our policy. Please review your preferences.', 'fp-privacy' ),
    'toggle_locked'  => \__( 'Always active', 'fp-privacy' ),
    'toggle_enabled' => \__( 'Enabled', 'fp-privacy' ),
    'debug_label'    => \__( 'Cookie debug:', 'fp-privacy' ),
    'link_policy'    => '',
);

$category_defaults = array(
    'necessary'   => array(
        'label'       => array( 'default' => \__('Strictly necessary', 'fp-privacy' ) ),
        'description' => array( 'default' => \__('Essential cookies required for the website to function and cannot be disabled.', 'fp-privacy' ) ),
        'locked'      => true,
        'services'    => array(),
    ),
    'preferences' => array(
        'label'       => array( 'default' => \__('Preferences', 'fp-privacy' ) ),
        'description' => array( 'default' => \__('Store user preferences such as language or location.', 'fp-privacy' ) ),
        'locked'      => false,
        'services'    => array(),
    ),
    'statistics'  => array(
        'label'       => array( 'default' => \__('Statistics', 'fp-privacy' ) ),
        'description' => array( 'default' => \__('Collect anonymous statistics to improve our services.', 'fp-privacy' ) ),
        'locked'      => false,
        'services'    => array(),
    ),
    'marketing'   => array(
        'label'       => array( 'default' => \__('Marketing', 'fp-privacy' ) ),
        'description' => array( 'default' => \__('Enable personalized advertising and tracking.', 'fp-privacy' ) ),
        'locked'      => false,
        'services'    => array(),
    ),
);

return array(
    'languages_active'      => array( $default_locale ),
    'banner_texts'          => array(
        $default_locale => $banner_default,
    ),
    'banner_layout'         => array(
        'type'                  => 'floating',
        'position'              => 'bottom',
        'palette'               => $default_palette,
        'sync_modal_and_button' => true,
    ),
    'categories'            => $category_defaults,
    'consent_mode_defaults' => array(
        'analytics_storage'      => 'denied',
        'ad_storage'             => 'denied',
        'ad_user_data'           => 'denied',
        'ad_personalization'     => 'denied',
        'functionality_storage'  => 'granted',
        'personalization_storage' => 'denied',
        'security_storage'       => 'granted',
    ),
    'retention_days'        => 180,
    'consent_revision'      => 1,
    'preview_mode'          => false,
    'pages'                 => array(
        'privacy_policy_page_id' => array( $default_locale => 0 ),
        'cookie_policy_page_id'  => array( $default_locale => 0 ),
    ),
    'org_name'              => '',
    'vat'                   => '',
    'address'               => '',
    'dpo_name'              => '',
    'dpo_email'             => '',
    'privacy_email'         => '',
    'snapshots'             => array(
        'services' => array(
            'detected'     => array(),
            'generated_at' => 0,
        ),
        'policies' => array(
            'privacy' => array(),
            'cookie'  => array(),
        ),
    ),
);
}

/**
 * Sanitize options array.
 *
 * @param array<string, mixed> $value    Value to sanitize.
 * @param array<string, mixed> $defaults Defaults.
 *
 * @return array<string, mixed>
 */
private function sanitize( array $value, array $defaults ) {
$default_locale = $defaults['languages_active'][0];
$languages      = Validator::locale_list( $value['languages_active'] ?? $defaults['languages_active'], $default_locale );

$banner_defaults = $defaults['banner_texts'][ $default_locale ] ?? reset( $defaults['banner_texts'] );
$layout_raw      = isset( $value['banner_layout'] ) && \is_array( $value['banner_layout'] ) ? $value['banner_layout'] : array();
        $categories_raw  = isset( $value['categories'] ) && \is_array( $value['categories'] ) ? $value['categories'] : $defaults['categories'];
        $pages_raw       = isset( $value['pages'] ) && \is_array( $value['pages'] ) ? $value['pages'] : array();

$owner_fields = Validator::sanitize_owner_fields(
    array(
        'org_name'      => $value['org_name'] ?? $defaults['org_name'],
        'vat'           => $value['vat'] ?? $defaults['vat'],
        'address'       => $value['address'] ?? $defaults['address'],
        'dpo_name'      => $value['dpo_name'] ?? $defaults['dpo_name'],
        'dpo_email'     => $value['dpo_email'] ?? $defaults['dpo_email'],
        'privacy_email' => $value['privacy_email'] ?? $defaults['privacy_email'],
    )
);

$layout = array(
    'type'                  => Validator::choice( $layout_raw['type'] ?? '', array( 'floating', 'bar' ), $defaults['banner_layout']['type'] ),
    'position'              => Validator::choice( $layout_raw['position'] ?? '', array( 'top', 'bottom' ), $defaults['banner_layout']['position'] ),
    'palette'               => Validator::sanitize_palette( isset( $layout_raw['palette'] ) && \is_array( $layout_raw['palette'] ) ? $layout_raw['palette'] : array(), $defaults['banner_layout']['palette'] ),
    'sync_modal_and_button' => Validator::bool( $layout_raw['sync_modal_and_button'] ?? $defaults['banner_layout']['sync_modal_and_button'] ),
);

        $default_categories = Validator::sanitize_categories( $defaults['categories'], $languages );
        $categories         = Validator::sanitize_categories( $categories_raw, $languages );

        $raw_categories_by_slug = array();

        foreach ( $categories_raw as $raw_slug => $raw_category ) {
            $normalized_slug = \sanitize_key( $raw_slug );

            if ( '' === $normalized_slug ) {
                continue;
            }

            $raw_categories_by_slug[ $normalized_slug ] = \is_array( $raw_category ) ? $raw_category : array();
        }

        if ( ! empty( $default_categories ) ) {
            $normalized = array();

            foreach ( $default_categories as $slug => $default_category ) {
                if ( isset( $categories[ $slug ] ) ) {
                    $merged = \array_replace_recursive( $default_category, $categories[ $slug ] );

                    $raw = $raw_categories_by_slug[ $slug ] ?? array();
                    if ( ! \array_key_exists( 'locked', $raw ) ) {
                        $merged['locked'] = $default_category['locked'];
                    }

                    $normalized[ $slug ] = $merged;
                } else {
                    $normalized[ $slug ] = $default_category;
                }
            }

            foreach ( $categories as $slug => $category ) {
                if ( ! isset( $normalized[ $slug ] ) ) {
                    $normalized[ $slug ] = $category;
                }
            }

            $categories = $normalized;
        }

        return array(
    'languages_active'      => $languages,
    'banner_texts'          => Validator::sanitize_banner_texts( isset( $value['banner_texts'] ) && \is_array( $value['banner_texts'] ) ? $value['banner_texts'] : array(), $languages, $banner_defaults ),
    'banner_layout'         => $layout,
            'categories'            => $categories,
    'consent_mode_defaults' => Validator::sanitize_consent_mode( isset( $value['consent_mode_defaults'] ) && \is_array( $value['consent_mode_defaults'] ) ? $value['consent_mode_defaults'] : array(), $defaults['consent_mode_defaults'] ),
    'retention_days'        => Validator::int( $value['retention_days'] ?? $defaults['retention_days'], $defaults['retention_days'], 1 ),
    'consent_revision'      => Validator::int( $value['consent_revision'] ?? $defaults['consent_revision'], $defaults['consent_revision'], 1 ),
    'preview_mode'          => Validator::bool( $value['preview_mode'] ?? $defaults['preview_mode'] ),
    'pages'                 => Validator::sanitize_pages( $pages_raw, $languages ),
    'org_name'              => $owner_fields['org_name'],
    'vat'                   => $owner_fields['vat'],
    'address'               => $owner_fields['address'],
    'dpo_name'              => $owner_fields['dpo_name'],
    'dpo_email'             => $owner_fields['dpo_email'],
    'privacy_email'         => $owner_fields['privacy_email'],
    'snapshots'             => $this->sanitize_snapshots( isset( $value['snapshots'] ) && \is_array( $value['snapshots'] ) ? $value['snapshots'] : array(), $languages ),
);
}

/**
 * Sanitize stored snapshots.
 *
 * @param array<string, mixed> $snapshots Snapshots payload.
 * @param array<int, string>   $languages Active languages.
 *
 * @return array<string, mixed>
 */
private function sanitize_snapshots( array $snapshots, array $languages ) {
$services = array(
    'detected'     => array(),
    'generated_at' => 0,
);

if ( isset( $snapshots['services'] ) && \is_array( $snapshots['services'] ) ) {
    $services['detected']     = isset( $snapshots['services']['detected'] ) && \is_array( $snapshots['services']['detected'] ) ? array_values( $snapshots['services']['detected'] ) : array();
    $services['generated_at'] = (int) ( $snapshots['services']['generated_at'] ?? 0 );
}

$policies = array(
    'privacy' => array(),
    'cookie'  => array(),
);

foreach ( array( 'privacy', 'cookie' ) as $type ) {
    $entries = array();
    if ( isset( $snapshots['policies'][ $type ] ) && \is_array( $snapshots['policies'][ $type ] ) ) {
        $entries = $snapshots['policies'][ $type ];
    }

    foreach ( $languages as $language ) {
        $language = Validator::locale( $language, $languages[0] );
        $content  = isset( $entries[ $language ]['content'] ) ? \wp_kses_post( $entries[ $language ]['content'] ) : '';
        $generated = isset( $entries[ $language ]['generated_at'] ) ? (int) $entries[ $language ]['generated_at'] : 0;

        $policies[ $type ][ $language ] = array(
            'content'      => $content,
            'generated_at' => $generated,
        );
    }
}

return array(
    'services' => $services,
    'policies' => $policies,
);
}

/**
 * Get active languages.
 *
 * @return array<int, string>
 */
    public function get_languages() {
        $configured = array();

        if ( isset( $this->options['languages_active'] ) ) {
            $configured = is_array( $this->options['languages_active'] )
                ? $this->options['languages_active']
                : array( $this->options['languages_active'] );
        }

        $fallback = $configured[0] ?? ( function_exists( '\\get_locale' ) ? (string) \get_locale() : 'en_US' );

        return Validator::locale_list( $configured, $fallback );
    }

/**
 * Normalize locale against active languages.
 *
 * @param string $locale Raw locale.
 *
 * @return string
 */
    public function normalize_language( $locale ) {
        $languages = $this->get_languages();

        if ( empty( $languages ) ) {
            return Validator::locale( $locale, 'en_US' );
        }

        $primary = $languages[0];
        $locale  = Validator::locale( $locale, $primary );

        if ( in_array( $locale, $languages, true ) ) {
            return $locale;
        }

        $matched = $this->match_language_alias( $locale, $languages );

        if ( '' !== $matched ) {
            return $matched;
        }

        return $primary;
    }

    /**
     * Attempt to match locale variations against configured languages.
     *
     * @param string              $locale    Requested locale.
     * @param array<int, string>  $languages Active languages.
     *
     * @return string
     */
    private function match_language_alias( $locale, array $languages ) {
        $normalized = $this->normalize_locale_token( $locale );

        if ( '' === $normalized ) {
            return '';
        }

        foreach ( $languages as $language ) {
            if ( '' === $language ) {
                continue;
            }

            $candidate = $this->normalize_locale_token( $language );

            if ( $candidate === $normalized ) {
                return $language;
            }

            if ( \str_replace( '_', '', $candidate ) === $normalized ) {
                return $language;
            }
        }

        $separator = \strpos( $normalized, '_' );
        $root      = false !== $separator ? \substr( $normalized, 0, $separator ) : $normalized;

        if ( '' === $root ) {
            return '';
        }

        foreach ( $languages as $language ) {
            if ( '' === $language ) {
                continue;
            }

            $candidate = $this->normalize_locale_token( $language );

            if ( $candidate === $root || 0 === \strpos( $candidate, $root . '_' ) ) {
                return $language;
            }
        }

        return '';
    }

    /**
     * Normalize locale token for safe comparisons.
     *
     * @param string $locale Raw locale value.
     *
     * @return string
     */
    private function normalize_locale_token( $locale ) {
        $locale = \strtolower( \trim( (string) $locale ) );
        $locale = \str_replace( '-', '_', $locale );

        return \preg_replace( '/[^a-z0-9_]/', '', $locale ) ?? '';
    }
/**
 * Get banner text for a language.
 *
 * @param string $lang Locale.
 *
 * @return array<string, string>
 */
public function get_banner_text( $lang ) {
$lang   = $this->normalize_language( $lang );
$texts  = $this->options['banner_texts'];
$result = $texts[ $lang ] ?? reset( $texts );

return \is_array( $result ) ? $result : array();
}

/**
 * Get categories for the requested language.
 *
 * @param string $lang Locale.
 *
 * @return array<string, array<string, mixed>>
 */
public function get_categories_for_language( $lang ) {
$lang     = $this->normalize_language( $lang );
$fallback = $this->get_languages()[0] ?? 'en_US';
$result   = array();

foreach ( $this->options['categories'] as $key => $category ) {
    $label = '';
    if ( isset( $category['label'][ $lang ] ) && '' !== $category['label'][ $lang ] ) {
        $label = $category['label'][ $lang ];
    } elseif ( isset( $category['label']['default'] ) ) {
        $label = $category['label']['default'];
    } elseif ( isset( $category['label'][ $fallback ] ) ) {
        $label = $category['label'][ $fallback ];
    }

    $description = '';
    if ( isset( $category['description'][ $lang ] ) && '' !== $category['description'][ $lang ] ) {
        $description = $category['description'][ $lang ];
    } elseif ( isset( $category['description']['default'] ) ) {
        $description = $category['description']['default'];
    } elseif ( isset( $category['description'][ $fallback ] ) ) {
        $description = $category['description'][ $fallback ];
    }

    $services_map = isset( $category['services'] ) && \is_array( $category['services'] ) ? $category['services'] : array();
    $services     = $this->resolve_services_for_language( $services_map, $lang, $fallback );

    $result[ $key ] = array(
        'label'       => $label,
        'description' => $description,
        'locked'      => ! empty( $category['locked'] ),
        'services'    => $services,
    );
}

return $result;
}

/**
 * Resolve services list for a given language with fallbacks.
 *
 * @param array<string|int, mixed> $services_map Raw services map.
 * @param string                   $lang         Requested language code.
 * @param string                   $fallback     Fallback language code.
 *
 * @return array<int, array<string, mixed>>
 */
private function resolve_services_for_language( array $services_map, $lang, $fallback ) {
    if ( empty( $services_map ) ) {
        return array();
    }

    // Legacy data may store services as a plain list without language keys.
    if ( array_values( $services_map ) === $services_map ) {
        return $this->normalize_services_list( $services_map );
    }

    $candidates = array( $lang );

    if ( 'default' !== $lang ) {
        $candidates[] = 'default';
    }

    if ( $fallback && ! in_array( $fallback, $candidates, true ) ) {
        $candidates[] = $fallback;
    }

    foreach ( $candidates as $code ) {
        if ( isset( $services_map[ $code ] ) && \is_array( $services_map[ $code ] ) && ! empty( $services_map[ $code ] ) ) {
            return $this->normalize_services_list( $services_map[ $code ] );
        }
    }

    return array();
}

/**
 * Normalize a list of service definitions.
 *
 * @param mixed $services Raw services list.
 *
 * @return array<int, array<string, mixed>>
 */
private function normalize_services_list( $services ) {
    if ( ! \is_array( $services ) ) {
        return array();
    }

    $normalized = array();

    foreach ( $services as $service ) {
        if ( \is_array( $service ) ) {
            $normalized[] = $service;
        }
    }

    return $normalized;
}

/**
 * Retrieve a policy page id for type and language.
 *
 * @param string $type  privacy_policy|cookie_policy.
 * @param string $lang  Locale.
 *
 * @return int
 */
public function get_page_id( $type, $lang ) {
$lang = $this->normalize_language( $lang );
$key  = 'privacy_policy' === $type ? 'privacy_policy_page_id' : 'cookie_policy_page_id';
$map  = isset( $this->options['pages'][ $key ] ) && \is_array( $this->options['pages'][ $key ] ) ? $this->options['pages'][ $key ] : array();

if ( ! empty( $map[ $lang ] ) ) {
    return (int) $map[ $lang ];
}

foreach ( $map as $page_id ) {
    if ( $page_id ) {
        return (int) $page_id;
    }
}

return 0;
}
/**
 * Increment consent revision.
 *
 * @return void
 */
    public function bump_revision() {
        $this->options['consent_revision'] = isset( $this->options['consent_revision'] ) ? (int) $this->options['consent_revision'] + 1 : 1;
        \update_option( self::OPTION_KEY, $this->options, false );
    }

/**
 * Ensure required pages exist.
 *
 * @return void
 */
public function ensure_pages_exist() {
$languages = $this->get_languages();
$pages     = isset( $this->options['pages'] ) && \is_array( $this->options['pages'] ) ? $this->options['pages'] : array();
$pages     = \wp_parse_args(
    $pages,
    array(
        'privacy_policy_page_id' => array(),
        'cookie_policy_page_id'  => array(),
    )
);

$updated = false;

$map = array(
    'privacy_policy_page_id' => array(
        'title'     => \__('Privacy Policy', 'fp-privacy' ),
        'shortcode' => 'fp_privacy_policy',
    ),
    'cookie_policy_page_id'  => array(
        'title'     => \__('Cookie Policy', 'fp-privacy' ),
        'shortcode' => 'fp_cookie_policy',
    ),
);

        foreach ( $map as $key => $config ) {
            foreach ( $languages as $language ) {
                $language = $this->normalize_language( $language );
                $page_id  = isset( $pages[ $key ][ $language ] ) ? (int) $pages[ $key ][ $language ] : 0;

                $post    = $page_id ? \get_post( $page_id ) : null;

        if ( $post instanceof WP_Post && 'page' !== $post->post_type ) {
            $post = null;
        }
        $content = sprintf(
            '[%1$s lang="%2$s"]',
            $config['shortcode'],
            \esc_attr( $language )
        );

        if ( $post instanceof WP_Post && 'trash' === $post->post_status ) {
            $restored = \wp_untrash_post( $post->ID );

            if ( ! \is_wp_error( $restored ) && $restored ) {
                $post = \get_post( $post->ID );
            }
        }

                if ( $post instanceof WP_Post ) {
                    $current_content   = trim( (string) $post->post_content );
                    $expected_signature = \hash( 'sha256', $content );
                    $stored_signature   = (string) \get_post_meta( $post->ID, self::PAGE_MANAGED_META_KEY, true );
                    $current_signature  = '' !== $current_content ? \hash( 'sha256', $current_content ) : '';
                    $is_managed         = '' !== $stored_signature && $current_signature && \hash_equals( $stored_signature, $current_signature );

                    if ( $current_content === $content ) {
                        if ( $stored_signature !== $expected_signature ) {
                            \update_post_meta( $post->ID, self::PAGE_MANAGED_META_KEY, $expected_signature );
                        }

                        if ( 'publish' !== $post->post_status ) {
                            $result = \wp_update_post(
                                array(
                                    'ID'          => $post->ID,
                                    'post_status' => 'publish',
                                ),
                                true
                            );

                            if ( $result && ! \is_wp_error( $result ) ) {
                                $pages[ $key ][ $language ] = (int) $post->ID;
                                $updated                     = true;
                            }
                        }

                        continue;
                    }

                    if ( ! $is_managed ) {
                        if ( '' !== $stored_signature ) {
                            \delete_post_meta( $post->ID, self::PAGE_MANAGED_META_KEY );
                        }

                        continue;
                    }

                    $result = \wp_update_post(
                        array(
                            'ID'           => $post->ID,
                            'post_status'  => 'publish',
                            'post_type'    => 'page',
                            'post_content' => $content,
                        ),
                        true
                    );

                    if ( $result && ! \is_wp_error( $result ) ) {
                        \update_post_meta( $post->ID, self::PAGE_MANAGED_META_KEY, $expected_signature );
                        $pages[ $key ][ $language ] = (int) $post->ID;
                        $updated                     = true;
                        continue;
                    }
                }

                $title = $config['title'];
                if ( count( $languages ) > 1 ) {
                    $title = sprintf( /* translators: %s: language code */ \__( '%1$s (%2$s)', 'fp-privacy' ), $config['title'], $language );
                }

        $created = \wp_insert_post(
            array(
                'post_title'   => $title,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => $content,
            )
        );

                if ( $created && ! \is_wp_error( $created ) ) {
                    \update_post_meta( $created, self::PAGE_MANAGED_META_KEY, \hash( 'sha256', $content ) );
                    $pages[ $key ][ $language ] = (int) $created;
                    $updated                     = true;
                }
            }
        }

if ( $updated ) {
    $this->options['pages'] = $pages;
    \update_option( self::OPTION_KEY, $this->options, false );
}
}
}

