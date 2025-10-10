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
