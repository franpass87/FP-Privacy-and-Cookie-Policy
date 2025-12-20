<?php
/**
 * Service registry.
 *
 * @package FP\Privacy\Domain\Services
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Domain\Services;

/**
 * Provides the registry of third-party services.
 *
 * This class contains the base registry of service definitions.
 * DetectorRegistry::get_registry() uses this as a base and adds additional services.
 */
class ServiceRegistry {

	/**
	 * Get the base registry of services.
	 *
	 * Returns the complete array of third-party services that can be detected.
	 * This array was extracted from DetectorRegistry to improve modularity.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_base_registry() {
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
            'detector'    => 'detect_youtube',
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
            'detector'    => 'detect_vimeo',
),
);

		return $services;
	}

	/**
	 * Get the complete registry of services.
	 *
	 * This method returns the full registry with all services.
	 * For now, it returns the base registry. In the future, this will
	 * contain the complete registry extracted from DetectorRegistry.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_registry() {
		return self::get_base_registry();
	}
}

