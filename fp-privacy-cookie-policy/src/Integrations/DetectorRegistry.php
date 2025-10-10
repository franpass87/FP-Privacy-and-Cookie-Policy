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
// Google Services
'google_adsense' => array(
'name'        => 'Google AdSense',
'category'    => 'marketing',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array( '_gads', '_gac_*', 'id', 'DSID', 'FLC', 'AID', 'TAID' ),
'legal_basis' => 'Consent',
'purpose'     => 'Display advertising and revenue generation',
'retention'   => '13 months',
'data_location' => 'United States',
'detector'    => function () {
return \has_action( 'wp_head', 'adsense_head' ) || defined( 'ADSENSE_CLIENT_ID' ) || \wp_script_is( 'google-adsense', 'enqueued' );
},
),
'google_ads'     => array(
'name'        => 'Google Ads',
'category'    => 'marketing',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array( '_gcl_*', 'DSID', 'IDE' ),
'legal_basis' => 'Consent',
'purpose'     => 'Advertising conversion tracking and remarketing',
'retention'   => '90 days',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'GOOGLE_ADS_CONVERSION_ID' ) || \has_action( 'wp_head', 'google_ads_conversion' );
},
),
'doubleclick'    => array(
'name'        => 'DoubleClick',
'category'    => 'marketing',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array( 'IDE', 'test_cookie' ),
'legal_basis' => 'Consent',
'purpose'     => 'Ad serving and tracking',
'retention'   => '13 months',
'data_location' => 'United States',
'detector'    => function () {
return \has_action( 'wp_head', 'doubleclick_head' );
},
),
'firebase'       => array(
'name'        => 'Firebase',
'category'    => 'statistics',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array( '_fir_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'App analytics and performance monitoring',
'retention'   => '2 years',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'FIREBASE_CONFIG' ) || \wp_script_is( 'firebase-app', 'enqueued' );
},
),
'google_optimize' => array(
'name'        => 'Google Optimize',
'category'    => 'statistics',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array( '_gaexp', '_opt_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'A/B testing and personalization',
'retention'   => '10 seconds',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'GOOGLE_OPTIMIZE_ID' ) || \wp_script_is( 'google-optimize', 'enqueued' );
},
),
'google_fonts'   => array(
'name'        => 'Google Fonts',
'category'    => 'necessary',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Web font delivery',
'retention'   => 'N/A',
'data_location' => 'United States',
'detector'    => function () {
return \wp_style_is( 'google-fonts', 'enqueued' ) || \has_action( 'wp_head', 'google_fonts' );
},
),
'google_maps'    => array(
'name'        => 'Google Maps',
'category'    => 'marketing',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array( 'NID', 'CONSENT' ),
'legal_basis' => 'Consent',
'purpose'     => 'Interactive maps display',
'retention'   => '6 months',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'google-maps', 'enqueued' ) || defined( 'GOOGLE_MAPS_API_KEY' );
},
),
// Social Media Pixels
'twitter_pixel'  => array(
'name'        => 'Twitter Pixel',
'category'    => 'marketing',
'provider'    => 'X Corp.',
'policy_url'  => 'https://twitter.com/privacy',
'cookies'     => array( 'personalization_id', 'guest_id' ),
'legal_basis' => 'Consent',
'purpose'     => 'Advertising conversion tracking',
'retention'   => '2 years',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'TWITTER_PIXEL_ID' ) || \has_action( 'wp_head', 'twitter_pixel' );
},
),
'snapchat_pixel' => array(
'name'        => 'Snapchat Pixel',
'category'    => 'marketing',
'provider'    => 'Snap Inc.',
'policy_url'  => 'https://www.snap.com/privacy/privacy-policy',
'cookies'     => array( '_scid', '_sctr' ),
'legal_basis' => 'Consent',
'purpose'     => 'Advertising conversion tracking',
'retention'   => '13 months',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'SNAPCHAT_PIXEL_ID' );
},
),
'reddit_pixel'   => array(
'name'        => 'Reddit Pixel',
'category'    => 'marketing',
'provider'    => 'Reddit Inc.',
'policy_url'  => 'https://www.reddit.com/policies/privacy-policy',
'cookies'     => array( '_rdt_uuid' ),
'legal_basis' => 'Consent',
'purpose'     => 'Advertising conversion tracking',
'retention'   => '90 days',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'REDDIT_PIXEL_ID' );
},
),
'quora_pixel'    => array(
'name'        => 'Quora Pixel',
'category'    => 'marketing',
'provider'    => 'Quora Inc.',
'policy_url'  => 'https://www.quora.com/about/privacy',
'cookies'     => array( 'm-b', 'm-qpid' ),
'legal_basis' => 'Consent',
'purpose'     => 'Advertising conversion tracking',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'QUORA_PIXEL_ID' );
},
),
// Analytics Tools
'mixpanel'       => array(
'name'        => 'Mixpanel',
'category'    => 'statistics',
'provider'    => 'Mixpanel Inc.',
'policy_url'  => 'https://mixpanel.com/legal/privacy-policy/',
'cookies'     => array( 'mp_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Product analytics and user tracking',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'mixpanel', 'enqueued' ) || defined( 'MIXPANEL_TOKEN' );
},
),
'amplitude'      => array(
'name'        => 'Amplitude',
'category'    => 'statistics',
'provider'    => 'Amplitude Inc.',
'policy_url'  => 'https://amplitude.com/privacy',
'cookies'     => array( 'amp_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Product analytics',
'retention'   => '10 years',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'amplitude', 'enqueued' ) || defined( 'AMPLITUDE_API_KEY' );
},
),
'segment'        => array(
'name'        => 'Segment',
'category'    => 'statistics',
'provider'    => 'Twilio Inc.',
'policy_url'  => 'https://www.twilio.com/legal/privacy',
'cookies'     => array( 'ajs_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Customer data platform',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'analytics.js', 'enqueued' ) || defined( 'SEGMENT_WRITE_KEY' );
},
),
'heap'           => array(
'name'        => 'Heap Analytics',
'category'    => 'statistics',
'provider'    => 'Heap Inc.',
'policy_url'  => 'https://heap.io/privacy',
'cookies'     => array( '_hp2_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Automatic event tracking',
'retention'   => '2 years',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'heap', 'enqueued' ) || defined( 'HEAP_ID' );
},
),
'fullstory'      => array(
'name'        => 'FullStory',
'category'    => 'statistics',
'provider'    => 'FullStory Inc.',
'policy_url'  => 'https://www.fullstory.com/legal/privacy-policy/',
'cookies'     => array( 'fs_uid' ),
'legal_basis' => 'Consent',
'purpose'     => 'Session replay and analytics',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'fullstory', 'enqueued' ) || defined( 'FULLSTORY_ORG' );
},
),
'crazy_egg'      => array(
'name'        => 'Crazy Egg',
'category'    => 'statistics',
'provider'    => 'Crazy Egg Inc.',
'policy_url'  => 'https://www.crazyegg.com/privacy',
'cookies'     => array( '_ce.s', '_ce.cch' ),
'legal_basis' => 'Consent',
'purpose'     => 'Heatmaps and user behavior',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'crazyegg', 'enqueued' ) || defined( 'CRAZYEGG_ACCOUNT' );
},
),
// Chat Widgets
'intercom'       => array(
'name'        => 'Intercom',
'category'    => 'marketing',
'provider'    => 'Intercom Inc.',
'policy_url'  => 'https://www.intercom.com/legal/privacy',
'cookies'     => array( 'intercom-*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Customer messaging and support',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'intercom', 'enqueued' ) || defined( 'INTERCOM_APP_ID' );
},
),
'drift'          => array(
'name'        => 'Drift',
'category'    => 'marketing',
'provider'    => 'Drift.com Inc.',
'policy_url'  => 'https://www.drift.com/privacy-policy/',
'cookies'     => array( 'driftt_aid', 'drift_campaign_refresh' ),
'legal_basis' => 'Consent',
'purpose'     => 'Conversational marketing',
'retention'   => '2 years',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'drift', 'enqueued' ) || defined( 'DRIFT_APP_ID' );
},
),
'zendesk'        => array(
'name'        => 'Zendesk Chat',
'category'    => 'marketing',
'provider'    => 'Zendesk Inc.',
'policy_url'  => 'https://www.zendesk.com/company/privacy-and-data-protection/',
'cookies'     => array( 'ZD-*', '__zlcmid' ),
'legal_basis' => 'Consent',
'purpose'     => 'Customer support chat',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'zendesk', 'enqueued' ) || \wp_script_is( 'zopim', 'enqueued' );
},
),
'livechat'       => array(
'name'        => 'LiveChat',
'category'    => 'marketing',
'provider'    => 'LiveChat Inc.',
'policy_url'  => 'https://www.livechat.com/legal/privacy-policy/',
'cookies'     => array( '__lc_*', 'main_window_timestamp' ),
'legal_basis' => 'Consent',
'purpose'     => 'Live customer support',
'retention'   => '3 years',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'livechat', 'enqueued' ) || defined( 'LIVECHAT_LICENSE' );
},
),
'tawk_to'        => array(
'name'        => 'Tawk.to',
'category'    => 'marketing',
'provider'    => 'Tawk.to Inc.',
'policy_url'  => 'https://www.tawk.to/privacy-policy/',
'cookies'     => array( 'TawkConnectionTime', 'tawkUUID' ),
'legal_basis' => 'Consent',
'purpose'     => 'Free live chat',
'retention'   => '6 months',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'tawkto', 'enqueued' ) || \has_action( 'wp_footer', 'tawk_to' );
},
),
'crisp'          => array(
'name'        => 'Crisp',
'category'    => 'marketing',
'provider'    => 'Crisp IM SARL',
'policy_url'  => 'https://crisp.chat/en/privacy/',
'cookies'     => array( 'crisp-client/*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Customer messaging',
'retention'   => '6 months',
'data_location' => 'European Union',
'detector'    => function () {
return \wp_script_is( 'crisp', 'enqueued' ) || defined( 'CRISP_WEBSITE_ID' );
},
),
// Advertising Networks
'taboola'        => array(
'name'        => 'Taboola',
'category'    => 'marketing',
'provider'    => 'Taboola Inc.',
'policy_url'  => 'https://www.taboola.com/privacy-policy',
'cookies'     => array( 't_gid', 'taboola_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Content recommendation',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'taboola', 'enqueued' ) || \has_action( 'wp_footer', 'taboola_pixel' );
},
),
'outbrain'       => array(
'name'        => 'Outbrain',
'category'    => 'marketing',
'provider'    => 'Outbrain Inc.',
'policy_url'  => 'https://www.outbrain.com/privacy/',
'cookies'     => array( 'obuid', 'outbrain_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Content recommendation',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'outbrain', 'enqueued' );
},
),
'criteo'         => array(
'name'        => 'Criteo',
'category'    => 'marketing',
'provider'    => 'Criteo SA',
'policy_url'  => 'https://www.criteo.com/privacy/',
'cookies'     => array( 'cto_*', 'criteo' ),
'legal_basis' => 'Consent',
'purpose'     => 'Personalized retargeting',
'retention'   => '13 months',
'data_location' => 'European Union',
'detector'    => function () {
return \wp_script_is( 'criteo', 'enqueued' );
},
),
'adroll'         => array(
'name'        => 'AdRoll',
'category'    => 'marketing',
'provider'    => 'AdRoll Inc.',
'policy_url'  => 'https://www.adroll.com/privacy',
'cookies'     => array( '__adroll', '__ar_v4' ),
'legal_basis' => 'Consent',
'purpose'     => 'Retargeting and advertising',
'retention'   => '13 months',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'adroll', 'enqueued' ) || defined( 'ADROLL_ADV_ID' );
},
),
'amazon_ads'     => array(
'name'        => 'Amazon Advertising',
'category'    => 'marketing',
'provider'    => 'Amazon.com Inc.',
'policy_url'  => 'https://www.amazon.com/gp/help/customer/display.html?nodeId=468496',
'cookies'     => array( 'ad-id', 'ad-privacy' ),
'legal_basis' => 'Consent',
'purpose'     => 'Advertising and retargeting',
'retention'   => '13 months',
'data_location' => 'United States',
'detector'    => function () {
return \has_action( 'wp_head', 'amazon_ads' );
},
),
// Marketing Automation
'mailchimp'      => array(
'name'        => 'Mailchimp',
'category'    => 'marketing',
'provider'    => 'Intuit Inc.',
'policy_url'  => 'https://www.intuit.com/privacy/statement/',
'cookies'     => array( '_AVESTA_ENVIRONMENT', 'ak_bmsc' ),
'legal_basis' => 'Consent',
'purpose'     => 'Email marketing',
'retention'   => '2 years',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'mailchimp', 'enqueued' ) || defined( 'MC4WP_VERSION' );
},
),
'activecampaign' => array(
'name'        => 'ActiveCampaign',
'category'    => 'marketing',
'provider'    => 'ActiveCampaign LLC',
'policy_url'  => 'https://www.activecampaign.com/privacy-policy/',
'cookies'     => array(),
'legal_basis' => 'Consent',
'purpose'     => 'Marketing automation',
'retention'   => 'Persistent',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'activecampaign', 'enqueued' ) || defined( 'ACTIVECAMPAIGN_API_KEY' );
},
),
'convertkit'     => array(
'name'        => 'ConvertKit',
'category'    => 'marketing',
'provider'    => 'ConvertKit LLC',
'policy_url'  => 'https://convertkit.com/privacy/',
'cookies'     => array(),
'legal_basis' => 'Consent',
'purpose'     => 'Email marketing for creators',
'retention'   => 'N/A',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'convertkit', 'enqueued' ) || defined( 'CONVERTKIT_API_KEY' );
},
),
'klaviyo'        => array(
'name'        => 'Klaviyo',
'category'    => 'marketing',
'provider'    => 'Klaviyo Inc.',
'policy_url'  => 'https://www.klaviyo.com/privacy',
'cookies'     => array( '__kla_id' ),
'legal_basis' => 'Consent',
'purpose'     => 'Email and SMS marketing',
'retention'   => '2 years',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'klaviyo', 'enqueued' ) || defined( 'KLAVIYO_PUBLIC_KEY' );
},
),
// Video Platforms
'wistia'         => array(
'name'        => 'Wistia',
'category'    => 'marketing',
'provider'    => 'Wistia Inc.',
'policy_url'  => 'https://wistia.com/privacy',
'cookies'     => array( 'wistia' ),
'legal_basis' => 'Consent',
'purpose'     => 'Video hosting and analytics',
'retention'   => 'Persistent',
'data_location' => 'United States',
'detector'    => array( $this, 'detect_wistia' ),
),
'vidyard'        => array(
'name'        => 'Vidyard',
'category'    => 'marketing',
'provider'    => 'Vidyard Inc.',
'policy_url'  => 'https://www.vidyard.com/privacy/',
'cookies'     => array( 'visitorId' ),
'legal_basis' => 'Consent',
'purpose'     => 'Video hosting and analytics',
'retention'   => '1 year',
'data_location' => 'Canada',
'detector'    => array( $this, 'detect_vidyard' ),
),
// Payment Processors
'stripe'         => array(
'name'        => 'Stripe',
'category'    => 'necessary',
'provider'    => 'Stripe Inc.',
'policy_url'  => 'https://stripe.com/privacy',
'cookies'     => array( '__stripe_mid', '__stripe_sid' ),
'legal_basis' => 'Contract',
'purpose'     => 'Payment processing',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'stripe', 'enqueued' ) || defined( 'STRIPE_PUBLISHABLE_KEY' );
},
),
'paypal'         => array(
'name'        => 'PayPal',
'category'    => 'necessary',
'provider'    => 'PayPal Inc.',
'policy_url'  => 'https://www.paypal.com/privacy',
'cookies'     => array( 'LANG', 'tsrce', 'ts_c' ),
'legal_basis' => 'Contract',
'purpose'     => 'Payment processing',
'retention'   => '3 years',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'paypal', 'enqueued' ) || \has_action( 'wp_footer', 'paypal_button' );
},
),
// CDN & Performance
'cloudflare'     => array(
'name'        => 'Cloudflare',
'category'    => 'necessary',
'provider'    => 'Cloudflare Inc.',
'policy_url'  => 'https://www.cloudflare.com/privacypolicy/',
'cookies'     => array( '__cflb', '__cf_bm', 'cf_clearance' ),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'CDN and security',
'retention'   => '30 days',
'data_location' => 'United States',
'detector'    => function () {
return isset( $_SERVER['HTTP_CF_RAY'] ) || isset( $_SERVER['HTTP_CF_CONNECTING_IP'] );
},
),
// Others
'disqus'         => array(
'name'        => 'Disqus',
'category'    => 'marketing',
'provider'    => 'Disqus Inc.',
'policy_url'  => 'https://help.disqus.com/terms-and-policies/disqus-privacy-policy',
'cookies'     => array( 'disqus_unique' ),
'legal_basis' => 'Consent',
'purpose'     => 'Comment system',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'disqus', 'enqueued' ) || \has_action( 'wp_footer', 'disqus_embed' );
},
),
'gravatar'       => array(
'name'        => 'Gravatar',
'category'    => 'necessary',
'provider'    => 'Automattic Inc.',
'policy_url'  => 'https://automattic.com/privacy/',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Avatar images',
'retention'   => 'N/A',
'data_location' => 'United States',
'detector'    => function () {
return \get_option( 'show_avatars' );
},
),
// WordPress Plugins & Tools
'jetpack'        => array(
'name'        => 'Jetpack',
'category'    => 'statistics',
'provider'    => 'Automattic Inc.',
'policy_url'  => 'https://automattic.com/privacy/',
'cookies'     => array( 'tk_ai', 'tk_qs' ),
'legal_basis' => 'Consent',
'purpose'     => 'Site stats, CDN, and WordPress features',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'JETPACK__VERSION' ) || class_exists( 'Jetpack' );
},
),
'yoast_seo'      => array(
'name'        => 'Yoast SEO',
'category'    => 'necessary',
'provider'    => 'Yoast BV',
'policy_url'  => 'https://yoast.com/privacy-policy/',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'SEO optimization',
'retention'   => 'N/A',
'data_location' => 'European Union',
'detector'    => function () {
return defined( 'WPSEO_VERSION' );
},
),
'elementor'      => array(
'name'        => 'Elementor',
'category'    => 'necessary',
'provider'    => 'Elementor Ltd.',
'policy_url'  => 'https://elementor.com/privacy-policy/',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Page builder',
'retention'   => 'N/A',
'data_location' => 'European Union',
'detector'    => function () {
return defined( 'ELEMENTOR_VERSION' );
},
),
'contact_form_7' => array(
'name'        => 'Contact Form 7',
'category'    => 'necessary',
'provider'    => 'Takayuki Miyoshi',
'policy_url'  => 'https://contactform7.com/privacy-policy/',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Contact forms',
'retention'   => 'N/A',
'data_location' => 'N/A',
'detector'    => function () {
return defined( 'WPCF7_VERSION' );
},
),
'wpml'           => array(
'name'        => 'WPML',
'category'    => 'necessary',
'provider'    => 'OnTheGoSystems Ltd.',
'policy_url'  => 'https://wpml.org/privacy-policy/',
'cookies'     => array( 'wpml_*', '_icl_*' ),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Multilingual content',
'retention'   => 'Session',
'data_location' => 'European Union',
'detector'    => function () {
return defined( 'ICL_SITEPRESS_VERSION' ) || defined( 'WPML_PLUGIN_FOLDER' );
},
),
'polylang'       => array(
'name'        => 'Polylang',
'category'    => 'necessary',
'provider'    => 'WP SYNTEX',
'policy_url'  => 'https://polylang.pro/privacy-policy/',
'cookies'     => array( 'pll_language' ),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Multilingual content',
'retention'   => '1 year',
'data_location' => 'European Union',
'detector'    => function () {
return defined( 'POLYLANG_VERSION' );
},
),
// Analytics Tools (additional)
'adobe_analytics' => array(
'name'        => 'Adobe Analytics',
'category'    => 'statistics',
'provider'    => 'Adobe Inc.',
'policy_url'  => 'https://www.adobe.com/privacy.html',
'cookies'     => array( 's_cc', 's_sq', 's_vi' ),
'legal_basis' => 'Consent',
'purpose'     => 'Enterprise web analytics',
'retention'   => '2 years',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'adobe-analytics', 'enqueued' ) || \has_action( 'wp_head', 'adobe_analytics' );
},
),
'plausible'      => array(
'name'        => 'Plausible Analytics',
'category'    => 'statistics',
'provider'    => 'Plausible Insights OÜ',
'policy_url'  => 'https://plausible.io/privacy',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Privacy-friendly analytics',
'retention'   => 'N/A',
'data_location' => 'European Union',
'detector'    => function () {
return \wp_script_is( 'plausible', 'enqueued' ) || defined( 'PLAUSIBLE_DOMAIN' );
},
),
'fathom'         => array(
'name'        => 'Fathom Analytics',
'category'    => 'statistics',
'provider'    => 'Conva Ventures Inc.',
'policy_url'  => 'https://usefathom.com/privacy',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Privacy-first analytics',
'retention'   => 'N/A',
'data_location' => 'Canada',
'detector'    => function () {
return \wp_script_is( 'fathom', 'enqueued' ) || defined( 'FATHOM_SITE_ID' );
},
),
'statcounter'    => array(
'name'        => 'StatCounter',
'category'    => 'statistics',
'provider'    => 'StatCounter',
'policy_url'  => 'https://statcounter.com/about/legal/',
'cookies'     => array( 'sc_is_visitor_unique' ),
'legal_basis' => 'Consent',
'purpose'     => 'Web analytics',
'retention'   => '13 months',
'data_location' => 'Ireland',
'detector'    => function () {
return \wp_script_is( 'statcounter', 'enqueued' ) || defined( 'STATCOUNTER_PROJECT_ID' );
},
),
// Social Embeds
'instagram'      => array(
'name'        => 'Instagram Embeds',
'category'    => 'marketing',
'provider'    => 'Meta Platforms, Inc.',
'policy_url'  => 'https://www.facebook.com/policy.php',
'cookies'     => array( 'ig_did', 'csrftoken' ),
'legal_basis' => 'Consent',
'purpose'     => 'Embedded social content',
'retention'   => '90 days',
'data_location' => 'United States',
'detector'    => array( $this, 'detect_instagram' ),
),
'twitter_embed'  => array(
'name'        => 'Twitter/X Embeds',
'category'    => 'marketing',
'provider'    => 'X Corp.',
'policy_url'  => 'https://twitter.com/privacy',
'cookies'     => array( 'personalization_id', 'guest_id' ),
'legal_basis' => 'Consent',
'purpose'     => 'Embedded tweets',
'retention'   => '2 years',
'data_location' => 'United States',
'detector'    => array( $this, 'detect_twitter_embed' ),
),
'spotify'        => array(
'name'        => 'Spotify Embeds',
'category'    => 'marketing',
'provider'    => 'Spotify AB',
'policy_url'  => 'https://www.spotify.com/privacy',
'cookies'     => array( 'sp_t', 'sp_landing' ),
'legal_basis' => 'Consent',
'purpose'     => 'Embedded music player',
'retention'   => '1 year',
'data_location' => 'Sweden',
'detector'    => array( $this, 'detect_spotify' ),
),
'soundcloud'     => array(
'name'        => 'SoundCloud Embeds',
'category'    => 'marketing',
'provider'    => 'SoundCloud Global Limited',
'policy_url'  => 'https://soundcloud.com/pages/privacy',
'cookies'     => array( 'sc_anonymous_id' ),
'legal_basis' => 'Consent',
'purpose'     => 'Embedded audio player',
'retention'   => '2 years',
'data_location' => 'Germany',
'detector'    => array( $this, 'detect_soundcloud' ),
),
// A/B Testing Tools
'vwo'            => array(
'name'        => 'VWO (Visual Website Optimizer)',
'category'    => 'statistics',
'provider'    => 'Wingify Software Pvt. Ltd.',
'policy_url'  => 'https://vwo.com/privacy-policy/',
'cookies'     => array( '_vis_opt_*', '_vwo_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'A/B testing and conversion optimization',
'retention'   => '100 days',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'vwo', 'enqueued' ) || defined( 'VWO_ACCOUNT_ID' );
},
),
'optimizely'     => array(
'name'        => 'Optimizely',
'category'    => 'statistics',
'provider'    => 'Optimizely Inc.',
'policy_url'  => 'https://www.optimizely.com/privacy/',
'cookies'     => array( 'optimizelyEndUserId', 'optimizelyBuckets' ),
'legal_basis' => 'Consent',
'purpose'     => 'A/B testing and experimentation',
'retention'   => '6 months',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'optimizely', 'enqueued' ) || defined( 'OPTIMIZELY_PROJECT_ID' );
},
),
'ab_tasty'       => array(
'name'        => 'AB Tasty',
'category'    => 'statistics',
'provider'    => 'AB Tasty SAS',
'policy_url'  => 'https://www.abtasty.com/privacy-policy/',
'cookies'     => array( 'ABTasty', 'ABTastySession' ),
'legal_basis' => 'Consent',
'purpose'     => 'A/B testing and personalization',
'retention'   => '13 months',
'data_location' => 'European Union',
'detector'    => function () {
return \wp_script_is( 'abtasty', 'enqueued' ) || defined( 'ABTASTY_ACCOUNT_ID' );
},
),
// Forms & Feedback
'typeform'       => array(
'name'        => 'Typeform',
'category'    => 'marketing',
'provider'    => 'TYPEFORM SL',
'policy_url'  => 'https://www.typeform.com/privacy-policy/',
'cookies'     => array( 'tf_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Interactive forms and surveys',
'retention'   => '1 year',
'data_location' => 'European Union',
'detector'    => array( $this, 'detect_typeform' ),
),
'surveymonkey'   => array(
'name'        => 'SurveyMonkey',
'category'    => 'marketing',
'provider'    => 'Momentive Inc.',
'policy_url'  => 'https://www.surveymonkey.com/mp/legal/privacy/',
'cookies'     => array( 'ep*', 'sm_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Online surveys',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => array( $this, 'detect_surveymonkey' ),
),
'google_forms'   => array(
'name'        => 'Google Forms',
'category'    => 'marketing',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array( 'NID', '1P_JAR' ),
'legal_basis' => 'Consent',
'purpose'     => 'Form creation and responses',
'retention'   => '6 months',
'data_location' => 'United States',
'detector'    => array( $this, 'detect_google_forms' ),
),
'jotform'        => array(
'name'        => 'JotForm',
'category'    => 'marketing',
'provider'    => 'JotForm Inc.',
'policy_url'  => 'https://www.jotform.com/privacy/',
'cookies'     => array( 'JOTFORM_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Online form builder',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => array( $this, 'detect_jotform' ),
),
// Error Tracking & Monitoring
'sentry'         => array(
'name'        => 'Sentry',
'category'    => 'necessary',
'provider'    => 'Functional Software Inc.',
'policy_url'  => 'https://sentry.io/privacy/',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Error tracking and monitoring',
'retention'   => '90 days',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'sentry', 'enqueued' ) || defined( 'SENTRY_DSN' );
},
),
'rollbar'        => array(
'name'        => 'Rollbar',
'category'    => 'necessary',
'provider'    => 'Rollbar Inc.',
'policy_url'  => 'https://rollbar.com/privacy/',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Error tracking',
'retention'   => '30 days',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'rollbar', 'enqueued' ) || defined( 'ROLLBAR_ACCESS_TOKEN' );
},
),
'bugsnag'        => array(
'name'        => 'Bugsnag',
'category'    => 'necessary',
'provider'    => 'SmartBear Software',
'policy_url'  => 'https://www.bugsnag.com/privacy-policy',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Error monitoring',
'retention'   => '30 days',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'bugsnag', 'enqueued' ) || defined( 'BUGSNAG_API_KEY' );
},
),
// Additional Advertising
'bing_ads'       => array(
'name'        => 'Microsoft Advertising (Bing Ads)',
'category'    => 'marketing',
'provider'    => 'Microsoft Corporation',
'policy_url'  => 'https://privacy.microsoft.com/',
'cookies'     => array( 'MUID', 'MR', 'MUIDB' ),
'legal_basis' => 'Consent',
'purpose'     => 'Search advertising and conversion tracking',
'retention'   => '13 months',
'data_location' => 'United States',
'detector'    => function () {
return \has_action( 'wp_head', 'bing_ads' ) || defined( 'BING_UET_TAG_ID' );
},
),
'yahoo_ads'      => array(
'name'        => 'Yahoo Advertising',
'category'    => 'marketing',
'provider'    => 'Yahoo Inc.',
'policy_url'  => 'https://legal.yahoo.com/privacy.html',
'cookies'     => array( 'B', 'A1', 'A3' ),
'legal_basis' => 'Consent',
'purpose'     => 'Display advertising',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \has_action( 'wp_head', 'yahoo_dot' );
},
),
// Additional Payments
'square'         => array(
'name'        => 'Square',
'category'    => 'necessary',
'provider'    => 'Block Inc.',
'policy_url'  => 'https://squareup.com/legal/privacy',
'cookies'     => array( '__cf_bm' ),
'legal_basis' => 'Contract',
'purpose'     => 'Payment processing',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'square', 'enqueued' ) || defined( 'SQUARE_APPLICATION_ID' );
},
),
'braintree'      => array(
'name'        => 'Braintree',
'category'    => 'necessary',
'provider'    => 'PayPal Inc.',
'policy_url'  => 'https://www.braintreepayments.com/legal/braintree-privacy-policy',
'cookies'     => array(),
'legal_basis' => 'Contract',
'purpose'     => 'Payment processing',
'retention'   => 'N/A',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'braintree', 'enqueued' ) || defined( 'BRAINTREE_MERCHANT_ID' );
},
),
// CDN & Performance
'cloudinary'     => array(
'name'        => 'Cloudinary',
'category'    => 'necessary',
'provider'    => 'Cloudinary Ltd.',
'policy_url'  => 'https://cloudinary.com/privacy',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Image and video CDN',
'retention'   => 'N/A',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'CLOUDINARY_URL' ) || \has_action( 'wp_head', 'cloudinary' );
},
),
'imgix'          => array(
'name'        => 'imgix',
'category'    => 'necessary',
'provider'    => 'imgix Inc.',
'policy_url'  => 'https://imgix.com/privacy',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Image optimization CDN',
'retention'   => 'N/A',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'IMGIX_DOMAIN' );
},
),
// Additional E-commerce
'shopify'        => array(
'name'        => 'Shopify',
'category'    => 'necessary',
'provider'    => 'Shopify Inc.',
'policy_url'  => 'https://www.shopify.com/legal/privacy',
'cookies'     => array( '_shopify_*', 'cart', 'secure_session_id' ),
'legal_basis' => 'Contract',
'purpose'     => 'E-commerce platform',
'retention'   => '2 weeks',
'data_location' => 'Canada',
'detector'    => function () {
return defined( 'SHOPIFY_API_KEY' ) || \has_action( 'wp_head', 'shopify_buy_button' );
},
),
// CRM & Sales Tools
'salesforce'     => array(
'name'        => 'Salesforce',
'category'    => 'marketing',
'provider'    => 'Salesforce Inc.',
'policy_url'  => 'https://www.salesforce.com/company/privacy/',
'cookies'     => array( 'sfdc-stream', 'CookieConsentPolicy' ),
'legal_basis' => 'Consent',
'purpose'     => 'Customer relationship management',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'salesforce', 'enqueued' ) || defined( 'SALESFORCE_API_KEY' );
},
),
'pipedrive'      => array(
'name'        => 'Pipedrive',
'category'    => 'marketing',
'provider'    => 'Pipedrive OÜ',
'policy_url'  => 'https://www.pipedrive.com/privacy',
'cookies'     => array( 'pipe_session_token' ),
'legal_basis' => 'Consent',
'purpose'     => 'Sales CRM',
'retention'   => '1 year',
'data_location' => 'European Union',
'detector'    => function () {
return \wp_script_is( 'pipedrive', 'enqueued' ) || defined( 'PIPEDRIVE_API_TOKEN' );
},
),
'zoho_crm'       => array(
'name'        => 'Zoho CRM',
'category'    => 'marketing',
'provider'    => 'Zoho Corporation',
'policy_url'  => 'https://www.zoho.com/privacy.html',
'cookies'     => array( 'JSESSIONID', 'zld*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Customer relationship management',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'zoho', 'enqueued' ) || defined( 'ZOHO_CRM_API_KEY' );
},
),
'freshsales'     => array(
'name'        => 'Freshsales',
'category'    => 'marketing',
'provider'    => 'Freshworks Inc.',
'policy_url'  => 'https://www.freshworks.com/privacy/',
'cookies'     => array( '_fw_crm_v' ),
'legal_basis' => 'Consent',
'purpose'     => 'Sales CRM and automation',
'retention'   => '2 years',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'freshsales', 'enqueued' ) || defined( 'FRESHSALES_API_KEY' );
},
),
// Email Service Providers
'sendgrid'       => array(
'name'        => 'SendGrid',
'category'    => 'marketing',
'provider'    => 'Twilio Inc.',
'policy_url'  => 'https://www.twilio.com/legal/privacy',
'cookies'     => array(),
'legal_basis' => 'Consent',
'purpose'     => 'Transactional email delivery',
'retention'   => 'N/A',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'SENDGRID_API_KEY' ) || class_exists( 'SendGrid' );
},
),
'mailgun'        => array(
'name'        => 'Mailgun',
'category'    => 'marketing',
'provider'    => 'Mailgun Technologies Inc.',
'policy_url'  => 'https://www.mailgun.com/privacy-policy/',
'cookies'     => array(),
'legal_basis' => 'Consent',
'purpose'     => 'Email delivery service',
'retention'   => 'N/A',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'MAILGUN_API_KEY' ) || class_exists( 'Mailgun' );
},
),
'sendinblue'     => array(
'name'        => 'Brevo (Sendinblue)',
'category'    => 'marketing',
'provider'    => 'Sendinblue SAS',
'policy_url'  => 'https://www.brevo.com/legal/privacypolicy/',
'cookies'     => array( 'sib_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Email marketing and automation',
'retention'   => '13 months',
'data_location' => 'European Union',
'detector'    => function () {
return \wp_script_is( 'sendinblue', 'enqueued' ) || defined( 'SENDINBLUE_API_KEY' );
},
),
'amazon_ses'     => array(
'name'        => 'Amazon SES',
'category'    => 'marketing',
'provider'    => 'Amazon Web Services Inc.',
'policy_url'  => 'https://aws.amazon.com/privacy/',
'cookies'     => array(),
'legal_basis' => 'Consent',
'purpose'     => 'Email sending service',
'retention'   => 'N/A',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'AWS_SES_ACCESS_KEY' ) || class_exists( 'Aws\Ses\SesClient' );
},
),
// Booking & Scheduling
'calendly'       => array(
'name'        => 'Calendly',
'category'    => 'marketing',
'provider'    => 'Calendly LLC',
'policy_url'  => 'https://calendly.com/privacy',
'cookies'     => array( '_calendly_session' ),
'legal_basis' => 'Consent',
'purpose'     => 'Meeting scheduling',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => array( $this, 'detect_calendly' ),
),
'acuity'         => array(
'name'        => 'Acuity Scheduling',
'category'    => 'marketing',
'provider'    => 'Squarespace Inc.',
'policy_url'  => 'https://acuityscheduling.com/privacy.php',
'cookies'     => array( 'acuity_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Appointment scheduling',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => array( $this, 'detect_acuity' ),
),
'bookly'         => array(
'name'        => 'Bookly',
'category'    => 'necessary',
'provider'    => 'Bookly',
'policy_url'  => 'https://www.booking-wp-plugin.com/privacy-policy/',
'cookies'     => array(),
'legal_basis' => 'Contract',
'purpose'     => 'WordPress booking system',
'retention'   => 'N/A',
'data_location' => 'N/A',
'detector'    => function () {
return defined( 'BOOKLY_VERSION' );
},
),
'cal_com'        => array(
'name'        => 'Cal.com',
'category'    => 'marketing',
'provider'    => 'Cal.com Inc.',
'policy_url'  => 'https://cal.com/privacy',
'cookies'     => array(),
'legal_basis' => 'Consent',
'purpose'     => 'Open source scheduling',
'retention'   => 'N/A',
'data_location' => 'United States',
'detector'    => array( $this, 'detect_cal_com' ),
),
// Popup & Lead Generation
'optinmonster'   => array(
'name'        => 'OptinMonster',
'category'    => 'marketing',
'provider'    => 'Retyp LLC',
'policy_url'  => 'https://optinmonster.com/privacy/',
'cookies'     => array( 'om-*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Lead generation popups',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'optinmonster', 'enqueued' ) || defined( 'OPTINMONSTER_VERSION' );
},
),
'sumo'           => array(
'name'        => 'Sumo',
'category'    => 'marketing',
'provider'    => 'AppSumo LLC',
'policy_url'  => 'https://sumo.com/privacy',
'cookies'     => array( 'sumo_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'List building and analytics',
'retention'   => '2 years',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'sumo', 'enqueued' ) || \has_action( 'wp_footer', 'sumo' );
},
),
'privy'          => array(
'name'        => 'Privy',
'category'    => 'marketing',
'provider'    => 'Privy Inc.',
'policy_url'  => 'https://www.privy.com/privacy-policy',
'cookies'     => array( '_privy_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Email popups and automation',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'privy', 'enqueued' );
},
),
'hello_bar'      => array(
'name'        => 'Hello Bar',
'category'    => 'marketing',
'provider'    => 'Hello Bar Inc.',
'policy_url'  => 'https://www.hellobar.com/privacy-policy',
'cookies'     => array( '_hellobar_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Popup notifications',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'hellobar', 'enqueued' );
},
),
// WordPress Caching Plugins
'wp_rocket'      => array(
'name'        => 'WP Rocket',
'category'    => 'necessary',
'provider'    => 'WP Media',
'policy_url'  => 'https://wp-rocket.me/privacy-policy/',
'cookies'     => array( 'wp-rocket-*' ),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Performance and caching',
'retention'   => 'Session',
'data_location' => 'European Union',
'detector'    => function () {
return defined( 'WP_ROCKET_VERSION' );
},
),
'w3_total_cache' => array(
'name'        => 'W3 Total Cache',
'category'    => 'necessary',
'provider'    => 'BoldGrid',
'policy_url'  => 'https://www.boldgrid.com/privacy-policy/',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'WordPress caching',
'retention'   => 'N/A',
'data_location' => 'N/A',
'detector'    => function () {
return defined( 'W3TC_VERSION' );
},
),
'wp_super_cache' => array(
'name'        => 'WP Super Cache',
'category'    => 'necessary',
'provider'    => 'Automattic Inc.',
'policy_url'  => 'https://automattic.com/privacy/',
'cookies'     => array( 'wordpress_test_cookie' ),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Page caching',
'retention'   => 'Session',
'data_location' => 'N/A',
'detector'    => function () {
return defined( 'WPCACHEHOME' );
},
),
'litespeed_cache' => array(
'name'        => 'LiteSpeed Cache',
'category'    => 'necessary',
'provider'    => 'LiteSpeed Technologies',
'policy_url'  => 'https://www.litespeedtech.com/privacy-policy',
'cookies'     => array( 'LSCACHE_VARY_COOKIE' ),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Performance optimization',
'retention'   => 'Session',
'data_location' => 'N/A',
'detector'    => function () {
return defined( 'LSCWP_V' );
},
),
// Security & Firewall
'wordfence'      => array(
'name'        => 'Wordfence Security',
'category'    => 'necessary',
'provider'    => 'Defiant Inc.',
'policy_url'  => 'https://www.wordfence.com/privacy-policy/',
'cookies'     => array( 'wfvt_*', 'wordfence_verifiedHuman' ),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Website security and firewall',
'retention'   => '30 minutes',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'WORDFENCE_VERSION' );
},
),
'sucuri'         => array(
'name'        => 'Sucuri Security',
'category'    => 'necessary',
'provider'    => 'Sucuri Inc.',
'policy_url'  => 'https://sucuri.net/privacy',
'cookies'     => array( 'sucuri_cloudproxy_uuid_*' ),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Security and malware protection',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'SUCURISCAN_VERSION' ) || isset( $_SERVER['HTTP_X_SUCURI_ID'] );
},
),
'ithemes_security' => array(
'name'        => 'iThemes Security',
'category'    => 'necessary',
'provider'    => 'iThemes Media LLC',
'policy_url'  => 'https://ithemes.com/privacy-policy/',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'WordPress security',
'retention'   => 'N/A',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'ITSEC_VERSION' );
},
),
'all_in_one_security' => array(
'name'        => 'All In One WP Security',
'category'    => 'necessary',
'provider'    => 'UpdraftPlus',
'policy_url'  => 'https://updraftplus.com/data-protection-and-privacy-centre/',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Security hardening',
'retention'   => 'N/A',
'data_location' => 'N/A',
'detector'    => function () {
return defined( 'AIO_WP_SECURITY_VERSION' );
},
),
// Social Proof & Reviews
'trustpilot'     => array(
'name'        => 'Trustpilot',
'category'    => 'marketing',
'provider'    => 'Trustpilot A/S',
'policy_url'  => 'https://legal.trustpilot.com/privacy-policy',
'cookies'     => array( '_tp_', '__tp_uuid' ),
'legal_basis' => 'Consent',
'purpose'     => 'Customer reviews widget',
'retention'   => '13 months',
'data_location' => 'European Union',
'detector'    => function () {
return \wp_script_is( 'trustpilot', 'enqueued' ) || \has_action( 'wp_footer', 'trustpilot_widget' );
},
),
'yotpo'          => array(
'name'        => 'Yotpo',
'category'    => 'marketing',
'provider'    => 'Yotpo Ltd.',
'policy_url'  => 'https://www.yotpo.com/privacy-policy/',
'cookies'     => array( 'yotpo_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Reviews and user-generated content',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'yotpo', 'enqueued' ) || defined( 'YOTPO_APP_KEY' );
},
),
'feefo'          => array(
'name'        => 'Feefo',
'category'    => 'marketing',
'provider'    => 'Feefo Holdings Ltd.',
'policy_url'  => 'https://www.feefo.com/en_GB/privacy-policy',
'cookies'     => array(),
'legal_basis' => 'Consent',
'purpose'     => 'Customer feedback and reviews',
'retention'   => 'N/A',
'data_location' => 'United Kingdom',
'detector'    => function () {
return \wp_script_is( 'feefo', 'enqueued' );
},
),
'proof_factor'   => array(
'name'        => 'Proof (Social Proof)',
'category'    => 'marketing',
'provider'    => 'Proof Experiences Inc.',
'policy_url'  => 'https://useproof.com/privacy',
'cookies'     => array( 'proof_*' ),
'legal_basis' => 'Consent',
'purpose'     => 'Social proof notifications',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'proof', 'enqueued' ) || \wp_script_is( 'useproof', 'enqueued' );
},
),
// Additional Services
'trustpay'       => array(
'name'        => 'TrustPay',
'category'    => 'necessary',
'provider'    => 'TrustPay a.s.',
'policy_url'  => 'https://www.trustpay.eu/privacy-policy',
'cookies'     => array(),
'legal_basis' => 'Contract',
'purpose'     => 'Payment processing',
'retention'   => 'N/A',
'data_location' => 'European Union',
'detector'    => function () {
return \wp_script_is( 'trustpay', 'enqueued' );
},
),
'userway'        => array(
'name'        => 'UserWay',
'category'    => 'necessary',
'provider'    => 'UserWay Inc.',
'policy_url'  => 'https://userway.org/privacy',
'cookies'     => array( 'USERWAYWIDGETAPP' ),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Web accessibility widget',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'userway', 'enqueued' );
},
),
'accessibe'      => array(
'name'        => 'accessiBe',
'category'    => 'necessary',
'provider'    => 'accessiBe Ltd.',
'policy_url'  => 'https://accessibe.com/privacy-policy',
'cookies'     => array( 'acsbJS' ),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'AI-powered accessibility',
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return \wp_script_is( 'accessibe', 'enqueued' );
},
),
'cdn77'          => array(
'name'        => 'CDN77',
'category'    => 'necessary',
'provider'    => 'DataCamp Limited',
'policy_url'  => 'https://www.cdn77.com/privacy-policy',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Content delivery network',
'retention'   => 'N/A',
'data_location' => 'European Union',
'detector'    => function () {
return isset( $_SERVER['HTTP_CDN77'] );
},
),
'bunnycdn'       => array(
'name'        => 'BunnyCDN',
'category'    => 'necessary',
'provider'    => 'BunnyWay d.o.o.',
'policy_url'  => 'https://bunny.net/privacy',
'cookies'     => array(),
'legal_basis' => 'Legitimate interest',
'purpose'     => 'Content delivery network',
'retention'   => 'N/A',
'data_location' => 'European Union',
'detector'    => function () {
return isset( $_SERVER['HTTP_CDN_PULL_ZONE'] ) || isset( $_SERVER['HTTP_CDN_REQUEST_ID'] );
},
),
);

// Allow developers to add custom services
$custom_services = \apply_filters( 'fp_privacy_custom_services', array() );

if ( is_array( $custom_services ) && ! empty( $custom_services ) ) {
$services = array_merge( $services, $custom_services );
}

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
        $unknown_services      = $this->detect_unknown_services();
        $services              = array_merge( $services, $unknown_services );
        $this->runtime_cache   = $services;
        $this->cache_timestamp = time();

        $this->persist_cache();

        return $services;
    }

    /**
     * Detect unknown/custom third-party services not in the registry.
     *
     * @return array<int, array<string, mixed>>
     */
    private function detect_unknown_services() {
        $unknown = array();

        // Detect external scripts
        $external_scripts = $this->detect_external_scripts();
        
        // Detect third-party cookies
        $third_party_cookies = $this->detect_third_party_cookies();

        // Merge and deduplicate
        $detected_domains = array_unique( array_merge( $external_scripts, $third_party_cookies ) );

        foreach ( $detected_domains as $domain ) {
            if ( $this->is_known_domain( $domain ) ) {
                continue;
            }

            $service_name = $this->guess_service_name( $domain );
            
            $unknown[] = array(
                'slug'         => 'unknown_' . sanitize_key( $domain ),
                'name'         => $service_name,
                'category'     => 'marketing',
                'provider'     => $this->extract_company_name( $domain ),
                'policy_url'   => 'https://' . $domain,
                'cookies'      => array(),
                'legal_basis'  => 'Consent',
                'purpose'      => 'Third-party service (auto-detected)',
                'retention'    => 'Unknown',
                'data_location' => 'Unknown',
                'detected'     => true,
                'is_unknown'   => true,
                'domain'       => $domain,
            );
        }

        return $unknown;
    }

    /**
     * Detect external scripts loaded on the page.
     *
     * @return array<int, string> Array of domains.
     */
    private function detect_external_scripts() {
        $domains = array();

        if ( ! function_exists( '\wp_scripts' ) ) {
            return $domains;
        }

        $wp_scripts = \wp_scripts();
        
        if ( empty( $wp_scripts->registered ) || ! is_array( $wp_scripts->registered ) ) {
            return $domains;
        }

        foreach ( $wp_scripts->registered as $handle => $script ) {
            if ( empty( $script->src ) ) {
                continue;
            }

            $src = (string) $script->src;
            
            // Skip local scripts
            if ( $this->is_local_url( $src ) ) {
                continue;
            }

            $domain = $this->extract_domain( $src );
            
            if ( $domain && ! in_array( $domain, $domains, true ) ) {
                $domains[] = $domain;
            }
        }

        return $domains;
    }

    /**
     * Detect third-party cookies (placeholder - requires client-side detection).
     *
     * @return array<int, string> Array of domains.
     */
    private function detect_third_party_cookies() {
        // Note: Server-side PHP cannot directly access browser cookies set by JavaScript.
        // This would need to be implemented via JavaScript that sends data to the server.
        // For now, return empty array. Can be extended with AJAX detection.
        return array();
    }

    /**
     * Check if a domain is already known in the registry.
     *
     * @param string $domain Domain to check.
     *
     * @return bool
     */
    private function is_known_domain( $domain ) {
        $known_domains = array(
            'google.com', 'googleapis.com', 'googletagmanager.com', 'google-analytics.com',
            'doubleclick.net', 'googlesyndication.com', 'gstatic.com', 'googleadservices.com',
            'facebook.com', 'facebook.net', 'connect.facebook.net',
            'twitter.com', 'x.com', 't.co', 'ads-twitter.com',
            'youtube.com', 'youtu.be', 'ytimg.com',
            'vimeo.com', 'player.vimeo.com',
            'instagram.com', 'instagr.am', 'cdninstagram.com',
            'linkedin.com', 'licdn.com',
            'tiktok.com', 'byteoversea.com',
            'pinterest.com', 'pinimg.com',
            'snapchat.com', 'sc-static.net',
            'reddit.com', 'redd.it',
            'hotjar.com', 'hotjar.io',
            'clarity.ms', 'microsoft.com',
            'matomo.org',
            'mixpanel.com', 'mxpnl.com',
            'amplitude.com',
            'segment.com', 'segment.io',
            'intercom.io', 'intercom.com', 'intercomcdn.com',
            'drift.com', 'driftt.com',
            'zendesk.com', 'zdassets.com', 'zopim.com',
            'stripe.com',
            'paypal.com',
            'cloudflare.com', 'cloudflare.net',
            'wistia.com', 'wistia.net',
            'vidyard.com',
            'spotify.com',
            'soundcloud.com',
            'typeform.com',
            'calendly.com',
            'hubspot.com', 'hubspot.net', 'hs-scripts.com',
            'mailchimp.com',
            'sendgrid.com', 'sendgrid.net',
            'taboola.com',
            'outbrain.com',
            'criteo.com', 'criteo.net',
            'amazon-adsystem.com', 'amazonaws.com',
        );

        foreach ( $known_domains as $known ) {
            if ( false !== strpos( $domain, $known ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract domain from URL.
     *
     * @param string $url URL to parse.
     *
     * @return string|null
     */
    private function extract_domain( $url ) {
        $parsed = parse_url( $url );
        
        if ( empty( $parsed['host'] ) ) {
            return null;
        }

        return (string) $parsed['host'];
    }

    /**
     * Check if URL is local/same-site.
     *
     * @param string $url URL to check.
     *
     * @return bool
     */
    private function is_local_url( $url ) {
        if ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) {
            return true;
        }

        if ( ! function_exists( '\site_url' ) ) {
            return false;
        }

        $site_domain = $this->extract_domain( \site_url() );
        $url_domain  = $this->extract_domain( $url );

        return $site_domain === $url_domain;
    }

    /**
     * Guess service name from domain.
     *
     * @param string $domain Domain name.
     *
     * @return string
     */
    private function guess_service_name( $domain ) {
        // Remove www. and common TLDs
        $name = preg_replace( '/^www\./', '', $domain );
        $name = preg_replace( '/\.(com|net|org|io|co|ai|app|dev)$/', '', $name );
        
        // Convert to title case
        $name = ucwords( str_replace( array( '-', '_', '.' ), ' ', $name ) );

        return $name;
    }

    /**
     * Extract company name from domain.
     *
     * @param string $domain Domain name.
     *
     * @return string
     */
    private function extract_company_name( $domain ) {
        $name = $this->guess_service_name( $domain );
        
        return $name . ' (Unknown)';
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
     * Detect Wistia embeds across persisted content.
     *
     * @return bool
     */
    private function detect_wistia() {
        return $this->detect_embed(
            array( 'wistia.com', 'wistia.net', 'wi.st' ),
            array( 'wistia' ),
            array()
        );
    }

    /**
     * Detect Vidyard embeds across persisted content.
     *
     * @return bool
     */
    private function detect_vidyard() {
        return $this->detect_embed(
            array( 'vidyard.com', 'play.vidyard.com' ),
            array( 'vidyard' ),
            array()
        );
    }

    /**
     * Detect Instagram embeds across persisted content.
     *
     * @return bool
     */
    private function detect_instagram() {
        return $this->detect_embed(
            array( 'instagram.com', 'instagr.am' ),
            array( 'instagram' ),
            array( 'core-embed/instagram' )
        );
    }

    /**
     * Detect Twitter/X embeds across persisted content.
     *
     * @return bool
     */
    private function detect_twitter_embed() {
        return $this->detect_embed(
            array( 'twitter.com', 'x.com', 't.co' ),
            array( 'twitter', 'tweet' ),
            array( 'core-embed/twitter' )
        );
    }

    /**
     * Detect Spotify embeds across persisted content.
     *
     * @return bool
     */
    private function detect_spotify() {
        return $this->detect_embed(
            array( 'open.spotify.com', 'spotify.com/embed' ),
            array( 'spotify' ),
            array( 'core-embed/spotify' )
        );
    }

    /**
     * Detect SoundCloud embeds across persisted content.
     *
     * @return bool
     */
    private function detect_soundcloud() {
        return $this->detect_embed(
            array( 'soundcloud.com', 'w.soundcloud.com' ),
            array( 'soundcloud' ),
            array( 'core-embed/soundcloud' )
        );
    }

    /**
     * Detect Typeform embeds across persisted content.
     *
     * @return bool
     */
    private function detect_typeform() {
        return $this->detect_embed(
            array( 'typeform.com/to/', 'form.typeform.com' ),
            array( 'typeform' ),
            array()
        );
    }

    /**
     * Detect SurveyMonkey embeds across persisted content.
     *
     * @return bool
     */
    private function detect_surveymonkey() {
        return $this->detect_embed(
            array( 'surveymonkey.com', 'www.surveymonkey.com/r/' ),
            array( 'surveymonkey' ),
            array()
        );
    }

    /**
     * Detect Google Forms embeds across persisted content.
     *
     * @return bool
     */
    private function detect_google_forms() {
        return $this->detect_embed(
            array( 'docs.google.com/forms' ),
            array(),
            array()
        );
    }

    /**
     * Detect JotForm embeds across persisted content.
     *
     * @return bool
     */
    private function detect_jotform() {
        return $this->detect_embed(
            array( 'jotform.com', 'form.jotform.com' ),
            array( 'jotform' ),
            array()
        );
    }

    /**
     * Detect Calendly embeds across persisted content.
     *
     * @return bool
     */
    private function detect_calendly() {
        return $this->detect_embed(
            array( 'calendly.com' ),
            array( 'calendly' ),
            array()
        );
    }

    /**
     * Detect Acuity Scheduling embeds across persisted content.
     *
     * @return bool
     */
    private function detect_acuity() {
        return $this->detect_embed(
            array( 'acuityscheduling.com' ),
            array( 'acuity' ),
            array()
        );
    }

    /**
     * Detect Cal.com embeds across persisted content.
     *
     * @return bool
     */
    private function detect_cal_com() {
        return $this->detect_embed(
            array( 'cal.com' ),
            array( 'cal' ),
            array()
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
            'google_adsense'  => array(
                'script_handles' => array( 'google-adsense' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'pagead2.googlesyndication.com',
                    'googleads.g.doubleclick.net',
                ),
                'iframes'        => array(),
            ),
            'google_ads'      => array(
                'script_handles' => array( 'google-ads' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'googleadservices.com',
                    'google.com/ads',
                ),
                'iframes'        => array(),
            ),
            'twitter_pixel'   => array(
                'script_handles' => array( 'twitter-pixel' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'static.ads-twitter.com/uwt.js',
                    'analytics.twitter.com',
                ),
                'iframes'        => array(),
            ),
            'snapchat_pixel'  => array(
                'script_handles' => array( 'snapchat-pixel' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'sc-static.net/scevent.min.js',
                ),
                'iframes'        => array(),
            ),
            'mixpanel'        => array(
                'script_handles' => array( 'mixpanel' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'cdn.mxpnl.com/libs/mixpanel',
                ),
                'iframes'        => array(),
            ),
            'intercom'        => array(
                'script_handles' => array( 'intercom' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'widget.intercom.io',
                    'js.intercomcdn.com',
                ),
                'iframes'        => array(
                    'intercom-frame',
                ),
            ),
            'drift'           => array(
                'script_handles' => array( 'drift' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'js.driftt.com',
                ),
                'iframes'        => array(),
            ),
            'zendesk'         => array(
                'script_handles' => array( 'zendesk', 'zopim' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'static.zdassets.com',
                    'v2.zopim.com',
                ),
                'iframes'        => array(),
            ),
            'taboola'         => array(
                'script_handles' => array( 'taboola' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'cdn.taboola.com',
                ),
                'iframes'        => array(),
            ),
            'outbrain'        => array(
                'script_handles' => array( 'outbrain' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'widgets.outbrain.com',
                ),
                'iframes'        => array(),
            ),
            'wistia'          => array(
                'script_handles' => array(),
                'style_handles'  => array(),
                'patterns'       => array(
                    'fast.wistia.com',
                    'fast.wistia.net',
                ),
                'iframes'        => array(
                    'fast.wistia.net/embed/iframe',
                ),
            ),
            'stripe'          => array(
                'script_handles' => array( 'stripe' ),
                'style_handles'  => array(),
                'patterns'       => array(
                    'js.stripe.com',
                ),
                'iframes'        => array(
                    'js.stripe.com/v3',
                ),
            ),
        );
    }
}
