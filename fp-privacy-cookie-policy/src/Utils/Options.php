<?php
/**
 * Options handler.
 *
 * @package FP\Privacy\Utils
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Utils;

use FP\Privacy\Integrations\DetectorRegistry;
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
     * Automatic translator utility.
     *
     * @var Translator
     */
    private $translator;

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
        $this->blog_id    = function_exists( 'get_current_blog_id' ) ? (int) get_current_blog_id() : 0;
        $this->options    = $this->load();
        $this->translator = new Translator();
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

$script_defaults = array(
    $default_locale => $this->build_script_language_defaults( $category_defaults ),
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
                'analytics_storage'       => 'denied',
                'ad_storage'              => 'denied',
                'ad_user_data'            => 'denied',
                'ad_personalization'      => 'denied',
                'functionality_storage'   => 'granted',
                'personalization_storage' => 'denied',
                'security_storage'        => 'granted',
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
            'scripts'               => $script_defaults,
            'detector_alert'        => $this->get_default_detector_alert(),
            'detector_notifications' => $this->get_default_detector_notifications(),
            'auto_translations'     => array(),
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
        $scripts_raw     = isset( $value['scripts'] ) && \is_array( $value['scripts'] ) ? $value['scripts'] : array();
        $alert_raw       = isset( $value['detector_alert'] ) && \is_array( $value['detector_alert'] ) ? $value['detector_alert'] : array();
        $notifications_raw = isset( $value['detector_notifications'] ) && \is_array( $value['detector_notifications'] ) ? $value['detector_notifications'] : array();
        $existing_scripts  = isset( $this->options['scripts'] ) && \is_array( $this->options['scripts'] ) ? $this->options['scripts'] : array();

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
            'scripts'               => $this->sanitize_script_rules( $scripts_raw, $languages, $categories, $existing_scripts ),
            'detector_alert'        => $this->sanitize_detector_alert( $alert_raw ),
            'detector_notifications' => $this->sanitize_detector_notifications( $notifications_raw, $this->get_detector_notifications() ),
            'auto_translations'     => Validator::sanitize_auto_translations( isset( $value['auto_translations'] ) && \is_array( $value['auto_translations'] ) ? $value['auto_translations'] : array(), $banner_defaults ),
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
     * Sanitize script blocking rules for active languages and categories.
     *
     * @param array<string, mixed> $rules      Raw rules payload.
     * @param array<int, string>   $languages  Active languages.
     * @param array<string, mixed> $categories Sanitized categories.
     *
     * @return array<string, array<string, array<string, array<int, string>>>>
     */
    private function sanitize_script_rules( array $rules, array $languages, array $categories, array $existing ) {
        $sanitized = array();

        foreach ( $languages as $language ) {
            $language = $this->normalize_language( $language );
            $raw      = isset( $rules[ $language ] ) && \is_array( $rules[ $language ] ) ? $rules[ $language ] : array();
            $sanitized[ $language ] = array();

            foreach ( $categories as $slug => $meta ) {
                $entry            = isset( $raw[ $slug ] ) && \is_array( $raw[ $slug ] ) ? $raw[ $slug ] : array();
                $previous_entry   = isset( $existing[ $language ][ $slug ] ) && \is_array( $existing[ $language ][ $slug ] ) ? $existing[ $language ][ $slug ] : array();
                $previous_managed = isset( $previous_entry['managed'] ) ? Validator::bool( $previous_entry['managed'] ) : false;
                $previous_rules   = $this->normalize_script_rule_entry( $previous_entry );
                $normalized       = $this->normalize_script_rule_entry( $entry );

                $managed = false;

                if ( isset( $entry['managed'] ) ) {
                    $managed = Validator::bool( $entry['managed'] );
                } elseif ( $previous_managed && $this->script_rules_equal( $previous_rules, $normalized ) ) {
                    $managed = true;
                }

                $normalized['managed'] = $managed;

                $sanitized[ $language ][ $slug ] = $normalized;
            }
        }

        return $sanitized;
    }

    /**
     * Normalize a list of handles.
     *
     * @param mixed $handles Raw handles.
     *
     * @return array<int, string>
     */
    private function sanitize_handle_list( $handles ) {
        if ( \is_string( $handles ) ) {
            $handles = \preg_split( '/[\r\n,]+/', $handles ) ?: array();
        }

        if ( ! \is_array( $handles ) ) {
            return array();
        }

        $normalized = array();

        foreach ( $handles as $handle ) {
            $clean = \sanitize_key( (string) $handle );

            if ( '' === $clean ) {
                continue;
            }

            if ( ! in_array( $clean, $normalized, true ) ) {
                $normalized[] = $clean;
            }
        }

        return $normalized;
    }

    /**
     * Normalize substring patterns used to match sources.
     *
     * @param mixed $patterns Raw patterns list.
     *
     * @return array<int, string>
     */
    private function sanitize_pattern_list( $patterns ) {
        if ( \is_string( $patterns ) ) {
            $patterns = \preg_split( '/[\r\n]+/', $patterns ) ?: array();
        }

        if ( ! \is_array( $patterns ) ) {
            return array();
        }

        $normalized = array();

        foreach ( $patterns as $pattern ) {
            $clean = Validator::text( $pattern );

            if ( '' === $clean ) {
                continue;
            }

            if ( ! in_array( $clean, $normalized, true ) ) {
                $normalized[] = $clean;
            }
        }

        return $normalized;
    }

    /**
     * Normalize script rule entry ensuring consistent structure.
     *
     * @param array<string, mixed> $entry Raw entry.
     *
     * @return array<string, mixed>
     */
    private function normalize_script_rule_entry( array $entry ) {
        return array(
            'script_handles' => $this->sanitize_handle_list( $entry['script_handles'] ?? array() ),
            'style_handles'  => $this->sanitize_handle_list( $entry['style_handles'] ?? array() ),
            'patterns'       => $this->sanitize_pattern_list( $entry['patterns'] ?? array() ),
            'iframes'        => $this->sanitize_pattern_list( $entry['iframes'] ?? array() ),
            'managed'        => isset( $entry['managed'] ) ? Validator::bool( $entry['managed'] ) : false,
        );
    }

    /**
     * Merge preset suggestions into an existing rule set.
     *
     * @param array<string, mixed> $current Current entry.
     * @param array<string, mixed> $preset  Preset entry.
     *
     * @return array<string, mixed>
     */
    private function merge_script_rule_defaults( array $current, array $preset ) {
        $current_normalized = $this->normalize_script_rule_entry( $current );
        $preset_normalized  = $this->normalize_script_rule_entry( $preset );

        $merged = array(
            'script_handles' => $this->merge_unique_list( $current_normalized['script_handles'], $preset_normalized['script_handles'] ),
            'style_handles'  => $this->merge_unique_list( $current_normalized['style_handles'], $preset_normalized['style_handles'] ),
            'patterns'       => $this->merge_unique_list( $current_normalized['patterns'], $preset_normalized['patterns'] ),
            'iframes'        => $this->merge_unique_list( $current_normalized['iframes'], $preset_normalized['iframes'] ),
            'managed'        => false,
        );

        $has_current = $this->has_script_rule_values( $current_normalized );
        $has_preset  = $this->has_script_rule_values( $preset_normalized );

        if ( $current_normalized['managed'] && $has_current ) {
            $merged['managed'] = true;
        } elseif ( ! $has_current && $has_preset ) {
            $merged['managed'] = true;
        }

        if ( ! $merged['managed'] && $has_current && ! $this->has_custom_script_rules( $current_normalized ) && $has_preset ) {
            $merged['managed'] = true;
        }

        return $merged;
    }

    /**
     * Merge unique values preserving order.
     *
     * @param array<int, string> $base       Base list.
     * @param array<int, string> $additional Additional values.
     *
     * @return array<int, string>
     */
    private function merge_unique_list( array $base, array $additional ) {
        foreach ( $additional as $value ) {
            if ( '' === $value ) {
                continue;
            }

            if ( ! in_array( $value, $base, true ) ) {
                $base[] = $value;
            }
        }

        return $base;
    }

    /**
     * Determine whether an entry contains meaningful values.
     *
     * @param array<string, mixed> $entry Entry to evaluate.
     *
     * @return bool
     */
    private function has_script_rule_values( array $entry ) {
        return ! empty( $entry['script_handles'] )
            || ! empty( $entry['style_handles'] )
            || ! empty( $entry['patterns'] )
            || ! empty( $entry['iframes'] );
    }

    /**
     * Determine whether the entry was manually customized.
     *
     * @param array<string, mixed> $entry Entry to evaluate.
     *
     * @return bool
     */
    private function has_custom_script_rules( array $entry ) {
        $managed = isset( $entry['managed'] ) ? Validator::bool( $entry['managed'] ) : false;

        return ! $managed && $this->has_script_rule_values( $entry );
    }

    /**
     * Compare script rule lists ignoring management metadata.
     *
     * @param array<string, mixed> $a First entry.
     * @param array<string, mixed> $b Second entry.
     *
     * @return bool
     */
    private function script_rules_equal( array $a, array $b ) {
        return $a['script_handles'] === $b['script_handles']
            && $a['style_handles'] === $b['style_handles']
            && $a['patterns'] === $b['patterns']
            && $a['iframes'] === $b['iframes'];
    }

    /**
     * Sanitize detector alert payload.
     *
     * @param array<string, mixed> $alert Raw alert payload.
     *
     * @return array<string, mixed>
     */
    private function sanitize_detector_alert( array $alert ) {
        return array(
            'active'       => Validator::bool( $alert['active'] ?? false ),
            'detected_at'  => Validator::int( $alert['detected_at'] ?? 0, 0, 0 ),
            'last_checked' => Validator::int( $alert['last_checked'] ?? 0, 0, 0 ),
            'added'        => $this->sanitize_service_summaries( $alert['added'] ?? array() ),
            'removed'      => $this->sanitize_service_summaries( $alert['removed'] ?? array() ),
        );
    }

    /**
     * Normalize service summaries stored alongside detector alerts.
     *
     * @param mixed $services Raw services list.
     *
     * @return array<int, array<string, string>>
     */
    private function sanitize_service_summaries( $services ) {
        if ( ! \is_array( $services ) ) {
            return array();
        }

        $normalized = array();

        foreach ( $services as $service ) {
            if ( ! \is_array( $service ) ) {
                continue;
            }

            $normalized[] = array(
                'slug'     => \sanitize_key( $service['slug'] ?? '' ),
                'name'     => Validator::text( $service['name'] ?? '' ),
                'category' => \sanitize_key( $service['category'] ?? '' ),
                'provider' => Validator::text( $service['provider'] ?? '' ),
            );
        }

        return $normalized;
    }

    /**
     * Sanitize detector notification settings.
     *
     * @param array<string, mixed> $settings Raw settings.
     * @param array<string, mixed> $defaults Existing defaults.
     *
     * @return array<string, mixed>
     */
    private function sanitize_detector_notifications( array $settings, array $defaults ) {
        $defaults = empty( $defaults ) ? $this->get_default_detector_notifications() : $defaults;

        $email      = isset( $settings['email'] ) ? Validator::bool( $settings['email'] ) : $defaults['email'];
        $recipients = isset( $settings['recipients'] ) ? $settings['recipients'] : $defaults['recipients'];
        $last_sent  = isset( $settings['last_sent'] ) ? Validator::int( $settings['last_sent'], (int) $defaults['last_sent'], 0 ) : $defaults['last_sent'];

        return array(
            'email'      => $email,
            'recipients' => $this->sanitize_email_list( $recipients ),
            'last_sent'  => $last_sent,
        );
    }

    /**
     * Normalize list of email recipients.
     *
     * @param mixed $emails Raw emails.
     *
     * @return array<int, string>
     */
    private function sanitize_email_list( $emails ) {
        if ( \is_string( $emails ) ) {
            $emails = \preg_split( '/[\s,;]+/', $emails ) ?: array();
        }

        if ( ! \is_array( $emails ) ) {
            return array();
        }

        $normalized = array();

        foreach ( $emails as $email ) {
            $clean = Validator::email( $email );

            if ( '' === $clean || in_array( $clean, $normalized, true ) ) {
                continue;
            }

            $normalized[] = $clean;
        }

        return $normalized;
    }

    /**
     * Get default detector notification settings.
     *
     * @return array<string, mixed>
     */
    public function get_default_detector_notifications() {
        return array(
            'email'      => true,
            'recipients' => array(),
            'last_sent'  => 0,
        );
    }

    /**
     * Retrieve detector notification settings.
     *
     * @return array<string, mixed>
     */
    public function get_detector_notifications() {
        if ( isset( $this->options['detector_notifications'] ) && \is_array( $this->options['detector_notifications'] ) ) {
            return \array_merge( $this->get_default_detector_notifications(), $this->options['detector_notifications'] );
        }

        return $this->get_default_detector_notifications();
    }

    /**
     * Persist detector notification settings.
     *
     * @param array<string, mixed> $settings Settings to merge.
     *
     * @return void
     */
    public function update_detector_notifications( array $settings ) {
        $current = $this->get_detector_notifications();
        $merged  = \array_merge( $current, $settings );

        $this->set(
            array(
                'detector_notifications' => $merged,
            )
        );
    }

    /**
     * Persist preset script rules when new services are detected.
     *
     * @param array<int, array<string, mixed>> $services Services detected.
     *
     * @return void
     */
    public function prime_script_rules_from_services( array $services ) {
        $presets_by_category = $this->collect_presets_by_category( $services );

        if ( empty( $presets_by_category ) ) {
            return;
        }

        $languages = $this->get_languages();

        if ( empty( $languages ) ) {
            return;
        }

        $scripts = isset( $this->options['scripts'] ) && \is_array( $this->options['scripts'] ) ? $this->options['scripts'] : array();
        $updated = false;

        foreach ( $languages as $language ) {
            $language   = $this->normalize_language( $language );
            $categories = $this->get_categories_for_language( $language );

            if ( ! isset( $scripts[ $language ] ) || ! \is_array( $scripts[ $language ] ) ) {
                $scripts[ $language ] = array();
            }

            foreach ( $categories as $slug => $meta ) {
                $current_entry      = isset( $scripts[ $language ][ $slug ] ) && \is_array( $scripts[ $language ][ $slug ] ) ? $scripts[ $language ][ $slug ] : array();
                $normalized_current = $this->normalize_script_rule_entry( $current_entry );

                if ( $this->has_custom_script_rules( $normalized_current ) ) {
                    continue;
                }

                if ( ! isset( $presets_by_category[ $slug ] ) ) {
                    continue;
                }

                $merged = $this->merge_script_rule_defaults( $normalized_current, $presets_by_category[ $slug ] );

                if ( $this->script_rules_equal( $normalized_current, $merged ) && $normalized_current['managed'] === $merged['managed'] ) {
                    continue;
                }

                $scripts[ $language ][ $slug ] = $merged;
                $updated                        = true;
            }
        }

        if ( $updated ) {
            $this->set(
                array(
                    'scripts' => $scripts,
                )
            );
        }
    }

    /**
     * Collect preset entries indexed by consent category.
     *
     * @param array<int, array<string, mixed>> $services Services list.
     *
     * @return array<string, array<string, mixed>>
     */
    private function collect_presets_by_category( array $services ) {
        $presets        = DetectorRegistry::get_blocking_presets();
        $category_rules = array();

        if ( empty( $presets ) ) {
            return $category_rules;
        }

        foreach ( $services as $service ) {
            if ( ! \is_array( $service ) ) {
                continue;
            }

            if ( isset( $service['detected'] ) && ! $service['detected'] ) {
                continue;
            }

            $slug = isset( $service['slug'] ) ? \sanitize_key( $service['slug'] ) : '';

            if ( '' === $slug || ! isset( $presets[ $slug ] ) ) {
                continue;
            }

            $category = isset( $service['category'] ) ? \sanitize_key( $service['category'] ) : '';

            if ( '' === $category ) {
                continue;
            }

            if ( ! isset( $category_rules[ $category ] ) ) {
                $category_rules[ $category ] = $this->normalize_script_rule_entry( array() );
            }

            $category_rules[ $category ] = $this->merge_script_rule_defaults( $category_rules[ $category ], $presets[ $slug ] );
        }

        return $category_rules;
    }

    /**
     * Retrieve detected services from stored snapshots.
     *
     * @return array<int, array<string, mixed>>
     */
    private function get_snapshot_services() {
        if ( isset( $this->options['snapshots']['services']['detected'] ) && \is_array( $this->options['snapshots']['services']['detected'] ) ) {
            return $this->options['snapshots']['services']['detected'];
        }

        return array();
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
     * Retrieve script rules for a given language.
     *
     * @param string $lang Language code.
     *
     * @return array<string, array<string, array<int, string>>>
     */
    public function get_script_rules_for_language( $lang ) {
        $lang       = $this->normalize_language( $lang );
        $categories = $this->get_categories_for_language( $lang );
        $defaults   = $this->build_script_language_defaults( $categories );
        $stored     = array();

        if ( isset( $this->options['scripts'][ $lang ] ) && \is_array( $this->options['scripts'][ $lang ] ) ) {
            $stored = $this->options['scripts'][ $lang ];
        }

        $normalized_stored = array();

        foreach ( $stored as $slug => $entry ) {
            if ( ! \is_array( $entry ) ) {
                $entry = array();
            }

            $normalized_stored[ $slug ] = $this->normalize_script_rule_entry( $entry );
        }

        $rules = array();

        foreach ( $categories as $slug => $meta ) {
            $rules[ $slug ] = isset( $defaults[ $slug ] ) ? $defaults[ $slug ] : $this->normalize_script_rule_entry( array() );

            if ( isset( $normalized_stored[ $slug ] ) ) {
                $entry = $normalized_stored[ $slug ];

                if ( $this->has_custom_script_rules( $entry ) ) {
                    $rules[ $slug ] = $entry;
                } elseif ( $this->has_script_rule_values( $entry ) ) {
                    $rules[ $slug ] = $this->merge_script_rule_defaults( $rules[ $slug ], $entry );
                }
            }
        }

        return $rules;
    }

    /**
     * Get the detector alert payload.
     *
     * @return array<string, mixed>
     */
    public function get_detector_alert() {
        if ( isset( $this->options['detector_alert'] ) && \is_array( $this->options['detector_alert'] ) ) {
            return $this->options['detector_alert'];
        }

        return $this->get_default_detector_alert();
    }

    /**
     * Persist detector alert payload.
     *
     * @param array<string, mixed> $payload Alert payload.
     *
     * @return void
     */
    public function set_detector_alert( array $payload ) {
        $this->set(
            array(
                'detector_alert' => $payload,
            )
        );
    }

    /**
     * Reset detector alert to defaults.
     *
     * @return void
     */
    public function clear_detector_alert() {
        $this->set_detector_alert( $this->get_default_detector_alert() );
    }

    /**
     * Get default detector alert payload.
     *
     * @return array<string, mixed>
     */
    public function get_default_detector_alert() {
        return array(
            'active'       => false,
            'detected_at'  => 0,
            'last_checked' => 0,
            'added'        => array(),
            'removed'      => array(),
        );
    }

    /**
     * Build empty script rule set for a language based on categories metadata.
     *
     * @param array<string, mixed> $categories Categories metadata.
     *
     * @return array<string, array<string, array<int, string>>>
     */
    private function build_script_language_defaults( $categories ) {
        $defaults        = array();
        $snapshot_rules  = $this->collect_presets_by_category( $this->get_snapshot_services() );

        foreach ( $categories as $slug => $meta ) {
            $defaults[ $slug ] = $this->normalize_script_rule_entry( array() );

            if ( isset( $snapshot_rules[ $slug ] ) ) {
                $defaults[ $slug ] = $this->merge_script_rule_defaults( $defaults[ $slug ], $snapshot_rules[ $slug ] );
            }
        }

        return $defaults;
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
        $languages = $this->get_languages();
        $primary   = $languages[0] ?? 'en_US';
        $requested = Validator::locale( $lang, $primary );
        $texts     = $this->options['banner_texts'];

        if ( isset( $texts[ $requested ] ) && \is_array( $texts[ $requested ] ) ) {
            return $texts[ $requested ];
        }

        $normalized = $this->normalize_language( $requested );

        if ( isset( $texts[ $normalized ] ) && $normalized !== $requested ) {
            return $this->maybe_auto_translate_banner_text( $texts[ $normalized ], $normalized, $requested );
        }

        $result = $texts[ $normalized ] ?? reset( $texts );

        return \is_array( $result ) ? $result : array();
    }

    /**
     * Maybe translate banner texts to a requested language.
     *
     * @param array<string, string> $source      Source banner texts.
     * @param string                $source_lang Source language code.
     * @param string                $target_lang Target language code.
     *
     * @return array<string, string>
     */
    private function maybe_auto_translate_banner_text( array $source, $source_lang, $target_lang ) {
        $source_lang = Validator::locale( $source_lang, 'en_US' );
        $target_lang = Validator::locale( $target_lang, $source_lang );

        if ( $source_lang === $target_lang ) {
            return $source;
        }

        $cache       = isset( $this->options['auto_translations'] ) && \is_array( $this->options['auto_translations'] ) ? $this->options['auto_translations'] : array();
        $banner_cache = isset( $cache['banner'] ) && \is_array( $cache['banner'] ) ? $cache['banner'] : array();
        $hash         = \md5( (string) \wp_json_encode( $source ) );

        if ( isset( $banner_cache[ $target_lang ] ) && \is_array( $banner_cache[ $target_lang ] ) ) {
            $cached = $banner_cache[ $target_lang ];

            if ( isset( $cached['hash'], $cached['texts'] ) && $cached['hash'] === $hash && \is_array( $cached['texts'] ) ) {
                return $cached['texts'];
            }
        }

        if ( ! $this->translator ) {
            return $source;
        }

        $translated = $this->translator->translate_banner_texts( $source, $source_lang, $target_lang );

        if ( empty( $translated ) ) {
            return $source;
        }

        $sanitized = Validator::sanitize_banner_texts( array( $target_lang => $translated ), array( $target_lang ), $source );
        $result    = $sanitized[ $target_lang ] ?? array();

        if ( empty( $result ) ) {
            return $source;
        }

        $banner_cache[ $target_lang ] = array(
            'hash'  => $hash,
            'texts' => $result,
        );

        $cache['banner'] = $banner_cache;

        $this->set(
            array(
                'auto_translations' => $cache,
            )
        );

        return $result;
    }

    /**
     * Translate categories metadata when needed.
     *
     * @param array<string, array<string, mixed>> $categories Categories metadata.
     * @param string                              $source_lang Source language.
     * @param string                              $target_lang Target language.
     *
     * @return array<string, array<string, mixed>>
     */
    private function maybe_auto_translate_categories( array $categories, $source_lang, $target_lang ) {
        $source_lang = Validator::locale( $source_lang, 'en_US' );
        $target_lang = Validator::locale( $target_lang, $source_lang );

        if ( $source_lang === $target_lang || empty( $categories ) ) {
            return $categories;
        }

        $cache            = isset( $this->options['auto_translations'] ) && \is_array( $this->options['auto_translations'] ) ? $this->options['auto_translations'] : array();
        $categories_cache = isset( $cache['categories'] ) && \is_array( $cache['categories'] ) ? $cache['categories'] : array();
        $hash_payload     = array();

        foreach ( $categories as $slug => $meta ) {
            $key = \sanitize_key( $slug );

            if ( '' === $key ) {
                continue;
            }

            $hash_payload[ $key ] = array(
                'label'       => isset( $meta['label'] ) ? (string) $meta['label'] : '',
                'description' => isset( $meta['description'] ) ? (string) $meta['description'] : '',
            );
        }

        if ( empty( $hash_payload ) ) {
            return $categories;
        }

        $hash = \md5( (string) \wp_json_encode( $hash_payload ) );

        if ( isset( $categories_cache[ $target_lang ] ) && \is_array( $categories_cache[ $target_lang ] ) ) {
            $cached = $categories_cache[ $target_lang ];

            if ( isset( $cached['hash'], $cached['items'] ) && $cached['hash'] === $hash && \is_array( $cached['items'] ) ) {
                foreach ( $categories as $slug => $meta ) {
                    $key = \sanitize_key( $slug );

                    if ( '' === $key || ! isset( $cached['items'][ $key ] ) ) {
                        continue;
                    }

                    $entry = $cached['items'][ $key ];

                    if ( isset( $entry['label'] ) ) {
                        $categories[ $slug ]['label'] = $entry['label'];
                    }

                    if ( isset( $entry['description'] ) ) {
                        $categories[ $slug ]['description'] = $entry['description'];
                    }
                }

                return $categories;
            }
        }

        if ( ! $this->translator ) {
            return $categories;
        }

        $cache_items = array();

        foreach ( $categories as $slug => $meta ) {
            $label       = isset( $meta['label'] ) ? (string) $meta['label'] : '';
            $description = isset( $meta['description'] ) ? (string) $meta['description'] : '';

            if ( '' !== \trim( $label ) ) {
                $label = $this->translator->translate_string( $label, $source_lang, $target_lang );
            }

            if ( '' !== \trim( $description ) ) {
                $description = $this->translator->translate_string( $description, $source_lang, $target_lang );
            }

            $label       = Validator::text( $label );
            $description = Validator::textarea( $description );

            $categories[ $slug ]['label']       = $label;
            $categories[ $slug ]['description'] = $description;

            $key = \sanitize_key( $slug );

            if ( '' === $key ) {
                continue;
            }

            $cache_items[ $key ] = array(
                'label'       => $label,
                'description' => $description,
            );
        }

        $categories_cache[ $target_lang ] = array(
            'hash'  => $hash,
            'items' => $cache_items,
        );

        $cache['categories'] = $categories_cache;

        $this->set(
            array(
                'auto_translations' => $cache,
            )
        );

        return $categories;
    }

/**
 * Get categories for the requested language.
 *
 * @param string $lang Locale.
 *
 * @return array<string, array<string, mixed>>
 */
    public function get_categories_for_language( $lang ) {
        $languages = $this->get_languages();
        $primary   = $languages[0] ?? 'en_US';
        $requested = Validator::locale( $lang, $primary );
        $lang      = $this->normalize_language( $requested );
        $fallback  = $primary;
        $result    = array();

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

        if ( $requested !== $lang ) {
            return $this->maybe_auto_translate_categories( $result, $lang, $requested );
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

