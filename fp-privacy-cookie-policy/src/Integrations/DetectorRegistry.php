<?php
/**
 * Service detector registry.
 *
 * @package FP\Privacy\Integrations
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Integrations;

/**
 * Detects third-party services for policy generation.
 */
class DetectorRegistry {
    const CACHE_OPTION = 'fp_privacy_detector_cache';

    const CACHE_TTL = 900;

    /**
     * Cached services for the current request.
     *
     * @var array<int, array<string, mixed>>|null
     */
    private $runtime_cache = null;

    /**
     * Timestamp of the runtime cache snapshot.
     *
     * @var int
     */
    private $cache_timestamp = 0;

    /**
     * Tracks whether the persisted cache has been hydrated.
     *
     * @var bool
     */
    private $hydrated = false;
/**
 * Get registry of services.
 *
 * @return array<string, array<string, mixed>>
 */
public function get_registry() {
$services = array(
'ga4'             => array(
'name'        => 'Google Analytics 4',
'category'    => 'statistics',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array( '_ga', '_ga_*', '_gid' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_ga4', 'Analytics measurement and reporting', \get_locale() ),
'retention'   => '26 months',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'google-analytics', 'enqueued' ) || \get_option( 'ga_dash_tracking' ) || defined( 'GA_MEASUREMENT_ID' );
},
),
'gtm'            => array(
'name'        => 'Google Tag Manager',
'category'    => 'marketing',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array( '_gtm', '_dc_gtm_*' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_gtm', 'Tag management and marketing integrations', \get_locale() ),
'retention'   => '14 months',
'data_location' => 'United States',
'detector'    => function () {
return \has_action( 'wp_head', 'gtm4wp_wp_head' ) || defined( 'GTM4WP_VERSION' );
},
),
'facebook_pixel' => array(
'name'        => 'Meta Pixel',
'category'    => 'marketing',
'provider'    => 'Meta Platforms, Inc.',
'policy_url'  => 'https://www.facebook.com/policy.php',
'cookies'     => array( '_fbp', 'fr' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_facebook_pixel', 'Advertising conversion tracking', \get_locale() ),
'retention'   => '3 months',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'FACEBOOK_PIXEL_ID' ) || \has_action( 'wp_head', 'facebook_pixel_head' );
},
),
'hotjar'         => array(
'name'        => 'Hotjar',
'category'    => 'statistics',
'provider'    => 'Hotjar Ltd.',
'policy_url'  => 'https://www.hotjar.com/legal/policies/privacy/',
'cookies'     => array( '_hjSession_*', '_hjFirstSeen' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_hotjar', 'User behavior analytics and feedback', \get_locale() ),
'retention'   => '1 year',
'data_location' => 'European Union',
'detector'    => function () {
return \wp_script_is( 'hotjar-tracking', 'enqueued' ) || defined( 'HOTJAR_SITE_ID' );
},
),
'clarity'        => array(
'name'        => 'Microsoft Clarity',
'category'    => 'statistics',
'provider'    => 'Microsoft Corporation',
'policy_url'  => 'https://privacy.microsoft.com/',
'cookies'     => array( '_clck', '_clsk' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_clarity', 'Session replays and heatmaps', \get_locale() ),
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'CLARITY_PROJECT_ID' ) || \has_action( 'wp_head', 'ms_clarity_tag' );
},
),
        'recaptcha'        => array(
'name'        => 'Google reCAPTCHA',
'category'    => 'necessary',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array( '_GRECAPTCHA' ),
'legal_basis' => 'Legitimate interest',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_recaptcha', 'Protect forms from spam and abuse', \get_locale() ),
'retention'   => 'Persistent',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'google-recaptcha', 'enqueued' ) || defined( 'RECAPTCHA_SITE_KEY' );
},
),
        'youtube'          => array(
'name'        => 'YouTube embeds',
'category'    => 'marketing',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array( 'VISITOR_INFO1_LIVE', 'YSC' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_youtube', 'Embedded video playback', \get_locale() ),
'retention'   => '6 months',
'data_location' => 'United States',
            'detector'    => array( $this, 'detect_youtube' ),
),
'vimeo'           => array(
'name'        => 'Vimeo embeds',
'category'    => 'marketing',
'provider'    => 'Vimeo Inc.',
'policy_url'  => 'https://vimeo.com/privacy',
'cookies'     => array( 'vuid' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_vimeo', 'Embedded video playback', \get_locale() ),
'retention'   => '2 years',
'data_location' => 'United States',
            'detector'    => array( $this, 'detect_vimeo' ),
),
'linkedin'        => array(
'name'        => 'LinkedIn Insight Tag',
'category'    => 'marketing',
'provider'    => 'LinkedIn Corporation',
'policy_url'  => 'https://www.linkedin.com/legal/privacy-policy',
'cookies'     => array( 'bcookie', 'li_sugr' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_linkedin', 'Advertising conversion tracking', \get_locale() ),
'retention'   => '6 months',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'LI_AJAX' ) || \has_action( 'wp_head', 'linkedin_insight' );
},
),
        'tiktok'           => array(
'name'        => 'TikTok Pixel',
'category'    => 'marketing',
'provider'    => 'TikTok Technology Limited',
'policy_url'  => 'https://www.tiktok.com/legal/privacy-policy',
'cookies'     => array( '_ttp' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_tiktok', 'Advertising conversion tracking', \get_locale() ),
'retention'   => '13 months',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'TIKTOK_PIXEL_ID' );
},
),
'matomo'         => array(
'name'        => 'Matomo Analytics',
'category'    => 'statistics',
'provider'    => 'InnoCraft Ltd.',
'policy_url'  => 'https://matomo.org/privacy-policy/',
'cookies'     => array( '_pk_id*', '_pk_ses*' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_matomo', 'Self-hosted analytics', \get_locale() ),
'retention'   => '13 months',
'data_location' => 'European Union',
'detector'    => function () {
return defined( 'MATOMO_VERSION' ) || \wp_script_is( 'matomo-tracking', 'enqueued' );
},
),
'pinterest'      => array(
'name'        => 'Pinterest Tag',
'category'    => 'marketing',
'provider'    => 'Pinterest Inc.',
'policy_url'  => 'https://policy.pinterest.com/privacy-policy',
'cookies'     => array( '_pinterest_ct_*' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_pinterest', 'Advertising conversion tracking', \get_locale() ),
'retention'   => '13 months',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'PINTEREST_TAG_ID' );
},
),
'hubspot'        => array(
'name'        => 'HubSpot',
'category'    => 'marketing',
'provider'    => 'HubSpot, Inc.',
'policy_url'  => 'https://legal.hubspot.com/privacy-policy',
'cookies'     => array( '__hstc', '__hssrc', '__hssc' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_hubspot', 'Marketing automation and CRM', \get_locale() ),
'retention'   => '13 months',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'HUBSPOT_API_KEY' ) || \wp_script_is( 'leadin-script-loader', 'enqueued' );
},
),
'woocommerce'    => array(
'name'        => 'WooCommerce',
'category'    => 'necessary',
'provider'    => 'Automattic Inc.',
'policy_url'  => 'https://automattic.com/privacy/',
'cookies'     => array( 'woocommerce_cart_hash', 'woocommerce_items_in_cart', 'wp_woocommerce_session_*' ),
'legal_basis' => 'Contract',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_woocommerce', 'E-commerce cart functionality', \get_locale() ),
'retention'   => 'Session',
'data_location' => 'European Union',
'detector'    => function () {
return class_exists( '\WooCommerce' );
},
),
);

return \apply_filters( 'fp_privacy_services_registry', $services );
}

    /**
     * Detect services using cached results when available.
     *
     * @param bool $force Force a fresh detection.
     *
     * @return array<int, array<string, mixed>>
     */
    public function detect_services( $force = false ) {
        if ( ! $force ) {
            $this->hydrate_cache();

            if ( null !== $this->runtime_cache && ! $this->is_cache_expired( $this->cache_timestamp ) ) {
                return $this->runtime_cache;
            }
        }

        $services              = $this->run_detectors();
        $this->runtime_cache   = $services;
        $this->cache_timestamp = time();

        $this->persist_cache();

        return $services;
    }

    /**
     * Detect YouTube embeds across persisted content.
     *
     * @return bool
     */
    private function detect_youtube() {
        return $this->detect_embed(
            array( 'youtube.com', 'youtu.be' ),
            array( 'youtube' ),
            array( 'core-embed/youtube' )
        );
    }

    /**
     * Detect Vimeo embeds across persisted content.
     *
     * @return bool
     */
    private function detect_vimeo() {
        return $this->detect_embed(
            array( 'vimeo.com' ),
            array( 'vimeo' ),
            array( 'core-embed/vimeo' )
        );
    }

    /**
     * Detect embeds by scanning current and persisted content when necessary.
     *
     * @param array<int, string> $strings   Raw string needles.
     * @param array<int, string> $shortcodes Shortcodes to inspect.
     * @param array<int, string> $blocks    Gutenberg block slugs.
     *
     * @return bool
     */
    private function detect_embed( array $strings, array $shortcodes = array(), array $blocks = array() ) {
        $post_id = function_exists( '\get_the_ID' ) ? \get_the_ID() : 0;

        if ( $post_id ) {
            $content = (string) \get_post_field( 'post_content', $post_id );

            if ( $this->content_matches_patterns( $content, $strings, $shortcodes, $blocks ) ) {
                return true;
            }
        }

        $doing_ajax = function_exists( '\wp_doing_ajax' ) ? \wp_doing_ajax() : ( defined( 'DOING_AJAX' ) && DOING_AJAX );
        $doing_cron = function_exists( '\wp_doing_cron' ) ? \wp_doing_cron() : ( defined( 'DOING_CRON' ) && DOING_CRON );
        $doing_rest = defined( 'REST_REQUEST' ) && REST_REQUEST;

        if ( function_exists( '\is_admin' ) && ! \is_admin() && ! defined( 'WP_CLI' ) && ! $doing_ajax && ! $doing_cron && ! $doing_rest ) {
            return false;
        }

        if ( ! class_exists( '\WP_Query' ) ) {
            return false;
        }

        $query = new \WP_Query(
            array(
                'post_type'      => 'any',
                'post_status'    => 'publish',
                'posts_per_page' => 10,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'orderby'        => 'modified',
                'order'          => 'DESC',
            )
        );

        if ( empty( $query->posts ) ) {
            return false;
        }

        foreach ( $query->posts as $id ) {
            $content = (string) \get_post_field( 'post_content', $id );

            if ( $this->content_matches_patterns( $content, $strings, $shortcodes, $blocks ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether content contains the provided patterns.
     *
     * @param string              $content    Post content.
     * @param array<int, string>  $strings    Raw string needles.
     * @param array<int, string>  $shortcodes Shortcode tags.
     * @param array<int, string>  $blocks     Block slugs.
     *
     * @return bool
     */
    private function content_matches_patterns( $content, array $strings, array $shortcodes, array $blocks ) {
        if ( '' === trim( (string) $content ) ) {
            return false;
        }

        foreach ( $strings as $needle ) {
            if ( '' !== $needle && false !== stripos( $content, $needle ) ) {
                return true;
            }
        }

        if ( function_exists( '\has_shortcode' ) ) {
            foreach ( $shortcodes as $shortcode ) {
                if ( '' !== $shortcode && \has_shortcode( $content, $shortcode ) ) {
                    return true;
                }
            }
        }

        if ( function_exists( '\has_block' ) ) {
            foreach ( $blocks as $block ) {
                if ( '' !== $block && \has_block( $block, $content ) ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Clear detector cache.
     *
     * @return void
     */
    public function invalidate_cache() {
        $this->runtime_cache  = null;
        $this->cache_timestamp = 0;
        $this->hydrated       = false;

        if ( function_exists( '\delete_option' ) ) {
            \delete_option( self::CACHE_OPTION );
        }
    }

    /**
     * Execute registry detectors.
     *
     * @return array<int, array<string, mixed>>
     */
    private function run_detectors() {
        $results  = array();
        $registry = $this->get_registry();

        foreach ( $registry as $slug => $service ) {
            $detector = isset( $service['detector'] ) ? $service['detector'] : null;
            $detected = false;

            if ( is_callable( $detector ) ) {
                try {
                    $detected = (bool) call_user_func( $detector );
                } catch ( \Throwable $e ) {
                    $detected = false;
                }
            }

            unset( $service['detector'] );
            $service['slug']     = $slug;
            $service['detected'] = (bool) $detected;
            $results[]           = $service;
        }

        return $results;
    }

    /**
     * Hydrate runtime cache from persisted snapshot.
     *
     * @return void
     */
    private function hydrate_cache() {
        if ( $this->hydrated ) {
            return;
        }

        $this->hydrated = true;

        if ( ! function_exists( '\get_option' ) ) {
            return;
        }

        $cache = \get_option( self::CACHE_OPTION );

        if ( ! is_array( $cache ) || ! isset( $cache['services'], $cache['timestamp'] ) ) {
            return;
        }

        $services  = is_array( $cache['services'] ) ? $cache['services'] : array();
        $timestamp = (int) $cache['timestamp'];

        if ( $this->is_cache_expired( $timestamp ) ) {
            return;
        }

        $this->runtime_cache  = $services;
        $this->cache_timestamp = $timestamp;
    }

    /**
     * Persist runtime cache.
     *
     * @return void
     */
    private function persist_cache() {
        if ( ! function_exists( '\update_option' ) ) {
            return;
        }

        \update_option(
            self::CACHE_OPTION,
            array(
                'services'  => is_array( $this->runtime_cache ) ? $this->runtime_cache : array(),
                'timestamp' => $this->cache_timestamp,
            ),
            false
        );
    }

    /**
     * Determine if cache has expired.
     *
     * @param int $timestamp Timestamp to evaluate.
     *
     * @return bool
     */
    private function is_cache_expired( $timestamp ) {
        if ( ! $timestamp ) {
            return true;
        }

        $ttl = $this->get_cache_ttl();

        if ( $ttl <= 0 ) {
            return false;
        }

        return ( time() - $timestamp ) > $ttl;
    }

    /**
     * Fetch cache TTL allowing filters.
     *
     * @return int
     */
    private function get_cache_ttl() {
        $ttl = self::CACHE_TTL;

        if ( function_exists( '\apply_filters' ) ) {
            $ttl = (int) \apply_filters( 'fp_privacy_detector_cache_ttl', $ttl );
        }

        return (int) $ttl;
    }

    /**
     * Provide default blocking presets for known services.
     *
     * @return array<string, array<string, array<int, string>>>
     */
    public static function get_blocking_presets() {
        return array(
            'ga4'             => array(
                'script_handles' => array( 'google-analytics', 'gtag' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'googletagmanager.com/gtag/js',
                    'google-analytics.com/analytics.js',
                ),
                'iframes'        => array(),
            ),
            'gtm'             => array(
                'script_handles' => array( 'google-tag-manager' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'googletagmanager.com/gtm.js',
                ),
                'iframes'        => array(
                    'googletagmanager.com/ns.html',
                ),
            ),
            'facebook_pixel'  => array(
                'script_handles' => array( 'facebook-pixel' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'connect.facebook.net/en_US/fbevents.js',
                ),
                'iframes'        => array(),
            ),
            'hotjar'          => array(
                'script_handles' => array( 'hotjar-tracking' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'static.hotjar.com',
                    'script.hotjar.com',
                ),
                'iframes'        => array(),
            ),
            'clarity'         => array(
                'script_handles' => array( 'ms-clarity' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'www.clarity.ms/tag',
                    'clarity.ms/tag',
                ),
                'iframes'        => array(),
            ),
            'recaptcha'       => array(
                'script_handles' => array( 'google-recaptcha' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'www.google.com/recaptcha',
                    'www.gstatic.com/recaptcha',
                ),
                'iframes'        => array(
                    'www.google.com/recaptcha',
                ),
            ),
            'youtube'         => array(
                'script_handles' => array(),
                'style_handles'  => array(),
                'patterns'       => array(
                    'youtube.com/iframe_api',
                    'ytimg.com/iframe_api',
                ),
                'iframes'        => array(
                    'youtube.com/embed',
                    'youtube-nocookie.com/embed',
                ),
            ),
            'vimeo'           => array(
                'script_handles' => array(),
                'style_handles'  => array(),
                'patterns'       => array(
                    'player.vimeo.com/api/player.js',
                ),
                'iframes'        => array(
                    'player.vimeo.com/video',
                ),
            ),
            'linkedin'        => array(
                'script_handles' => array( 'linkedin-insight' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'snap.licdn.com/li.lms-analytics/insight.min.js',
                ),
                'iframes'        => array(),
            ),
            'tiktok'          => array(
                'script_handles' => array( 'tiktok-pixel' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'analytics.tiktok.com/i18n/pixel/events.js',
                ),
                'iframes'        => array(),
            ),
            'matomo'          => array(
                'script_handles' => array( 'matomo-tracking' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'matomo.js',
                    'piwik.js',
                ),
                'iframes'        => array(),
            ),
            'pinterest'       => array(
                'script_handles' => array( 'pinterest-tag' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    's.pinimg.com/ct/core.js',
                    'ct.pinterest.com',
                ),
                'iframes'        => array(),
            ),
        );
    }
}
