<?php
/**
 * Service detector registry.
 *
 * @package FP\Privacy\Integrations
 */

namespace FP\Privacy\Integrations;

/**
 * Detects third-party services for policy generation.
 */
class DetectorRegistry {
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
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_ga4', 'Analytics measurement and reporting', \\get_locale() ),
'retention'   => '26 months',
'data_location' => 'United States',
'detector'    => function () {
return \\wp_script_is( 'google-analytics', 'enqueued' ) || \\get_option( 'ga_dash_tracking' ) || defined( 'GA_MEASUREMENT_ID' );
},
),
'gtm'            => array(
'name'        => 'Google Tag Manager',
'category'    => 'marketing',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array( '_gtm', '_dc_gtm_*' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_gtm', 'Tag management and marketing integrations', \\get_locale() ),
'retention'   => '14 months',
'data_location' => 'United States',
'detector'    => function () {
return \\has_action( 'wp_head', 'gtm4wp_wp_head' ) || defined( 'GTM4WP_VERSION' );
},
),
'facebook_pixel' => array(
'name'        => 'Meta Pixel',
'category'    => 'marketing',
'provider'    => 'Meta Platforms, Inc.',
'policy_url'  => 'https://www.facebook.com/policy.php',
'cookies'     => array( '_fbp', 'fr' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_facebook_pixel', 'Advertising conversion tracking', \\get_locale() ),
'retention'   => '3 months',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'FACEBOOK_PIXEL_ID' ) || \\has_action( 'wp_head', 'facebook_pixel_head' );
},
),
'hotjar'         => array(
'name'        => 'Hotjar',
'category'    => 'statistics',
'provider'    => 'Hotjar Ltd.',
'policy_url'  => 'https://www.hotjar.com/legal/policies/privacy/',
'cookies'     => array( '_hjSession_*', '_hjFirstSeen' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_hotjar', 'User behavior analytics and feedback', \\get_locale() ),
'retention'   => '1 year',
'data_location' => 'European Union',
'detector'    => function () {
return \\wp_script_is( 'hotjar-tracking', 'enqueued' ) || defined( 'HOTJAR_SITE_ID' );
},
),
'clarity'        => array(
'name'        => 'Microsoft Clarity',
'category'    => 'statistics',
'provider'    => 'Microsoft Corporation',
'policy_url'  => 'https://privacy.microsoft.com/',
'cookies'     => array( '_clck', '_clsk' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_clarity', 'Session replays and heatmaps', \\get_locale() ),
'retention'   => '1 year',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'CLARITY_PROJECT_ID' ) || \\has_action( 'wp_head', 'ms_clarity_tag' );
},
),
recaptcha        => array(
'name'        => 'Google reCAPTCHA',
'category'    => 'necessary',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array( '_GRECAPTCHA' ),
'legal_basis' => 'Legitimate interest',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_recaptcha', 'Protect forms from spam and abuse', \\get_locale() ),
'retention'   => 'Persistent',
'data_location' => 'United States',
'detector'    => function () {
return \\wp_script_is( 'google-recaptcha', 'enqueued' ) || defined( 'RECAPTCHA_SITE_KEY' );
},
),
youtube          => array(
'name'        => 'YouTube embeds',
'category'    => 'marketing',
'provider'    => 'Google LLC',
'policy_url'  => 'https://policies.google.com/privacy',
'cookies'     => array( 'VISITOR_INFO1_LIVE', 'YSC' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_youtube', 'Embedded video playback', \\get_locale() ),
'retention'   => '6 months',
'data_location' => 'United States',
'detector'    => function () {
return \\has_shortcode( \\get_post_field( 'post_content', \\get_the_ID() ), 'youtube' ) || \\has_block( 'core-embed/youtube' );
},
),
'vimeo'           => array(
'name'        => 'Vimeo embeds',
'category'    => 'marketing',
'provider'    => 'Vimeo Inc.',
'policy_url'  => 'https://vimeo.com/privacy',
'cookies'     => array( 'vuid' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_vimeo', 'Embedded video playback', \\get_locale() ),
'retention'   => '2 years',
'data_location' => 'United States',
'detector'    => function () {
return \\has_block( 'core-embed/vimeo' );
},
),
'linkedin'        => array(
'name'        => 'LinkedIn Insight Tag',
'category'    => 'marketing',
'provider'    => 'LinkedIn Corporation',
'policy_url'  => 'https://www.linkedin.com/legal/privacy-policy',
'cookies'     => array( 'bcookie', 'li_sugr' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_linkedin', 'Advertising conversion tracking', \\get_locale() ),
'retention'   => '6 months',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'LI_AJAX' ) || \\has_action( 'wp_head', 'linkedin_insight' );
},
),
tiktok           => array(
'name'        => 'TikTok Pixel',
'category'    => 'marketing',
'provider'    => 'TikTok Technology Limited',
'policy_url'  => 'https://www.tiktok.com/legal/privacy-policy',
'cookies'     => array( '_ttp' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_tiktok', 'Advertising conversion tracking', \\get_locale() ),
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
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_matomo', 'Self-hosted analytics', \\get_locale() ),
'retention'   => '13 months',
'data_location' => 'European Union',
'detector'    => function () {
return defined( 'MATOMO_VERSION' ) || \\wp_script_is( 'matomo-tracking', 'enqueued' );
},
),
'pinterest'      => array(
'name'        => 'Pinterest Tag',
'category'    => 'marketing',
'provider'    => 'Pinterest Inc.',
'policy_url'  => 'https://policy.pinterest.com/privacy-policy',
'cookies'     => array( '_pinterest_ct_*' ),
'legal_basis' => 'Consent',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_pinterest', 'Advertising conversion tracking', \\get_locale() ),
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
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_hubspot', 'Marketing automation and CRM', \\get_locale() ),
'retention'   => '13 months',
'data_location' => 'United States',
'detector'    => function () {
return defined( 'HUBSPOT_API_KEY' ) || \\wp_script_is( 'leadin-script-loader', 'enqueued' );
},
),
'woocommerce'    => array(
'name'        => 'WooCommerce',
'category'    => 'necessary',
'provider'    => 'Automattic Inc.',
'policy_url'  => 'https://automattic.com/privacy/',
'cookies'     => array( 'woocommerce_cart_hash', 'woocommerce_items_in_cart', 'wp_woocommerce_session_*' ),
'legal_basis' => 'Contract',
'purpose'     => \apply_filters( 'fp_privacy_service_purpose_woocommerce', 'E-commerce cart functionality', \\get_locale() ),
'retention'   => 'Session',
'data_location' => 'European Union',
'detector'    => function () {
return class_exists( '\\WooCommerce' );
},
),
);

return \apply_filters( 'fp_privacy_services_registry', $services );
}

/**
 * Detect services and mark results.
 *
 * @return array<int, array<string, mixed>>
 */
public function detect_services() {
$registry = $this->get_registry();
$results  = array();

foreach ( $registry as $key => $service ) {
$detected = false;
if ( isset( $service['detector'] ) && \is_callable( $service['detector'] ) ) {
$detected = (bool) \\call_user_func( $service['detector'] );
}

$service['key']      = $key;
$service['detected'] = $detected;
unset( $service['detector'] );
$results[] = $service;
}

return $results;
}
}
