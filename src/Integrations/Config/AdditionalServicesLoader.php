<?php
/**
 * Additional services loader.
 *
 * @package FP\Privacy\Integrations\Config
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Integrations\Config;

/**
 * Loads additional services configuration.
 *
 * This class provides a way to load additional services that are not in the base registry.
 * It returns a function that accepts a ServiceDetector instance to resolve detectors that use $this.
 */
class AdditionalServicesLoader {

	/**
	 * Get additional services array.
	 *
	 * Returns a function that accepts a ServiceDetector instance and returns the additional services array.
	 * This allows detectors that use $this to be resolved properly.
	 *
	 * @return callable Function that accepts ServiceDetector and returns array<string, array<string, mixed>>
	 */
	public static function get_loader() {
		return function( $service_detector ) {
			// Load the additional services array.
			// Detectors that use $this will be replaced with ServiceDetector references.
			return self::get_additional_services( $service_detector );
		};
	}

	/**
	 * Get additional services array.
	 *
	 * @param object $service_detector ServiceDetector instance.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function get_additional_services( $service_detector ) {
		// Note: ga4, gtm, facebook_pixel, hotjar, clarity, recaptcha, youtube, and vimeo
		// are already in ServiceRegistry::get_base_registry(), so they are excluded here.
		$services = array(
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
					$primary = defined( 'LI_AJAX' ) || \has_action( 'wp_head', 'linkedin_insight' );
					if ( $primary ) {
						return true;
					}
					return \FP\Privacy\Integrations\TrackingPatternScanner::site_contains_any( array(
						'snap.licdn.com',
						'linkedin.com/li.lms-analytics',
						'linkedin.com/insight',
					) );
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
					$primary = defined( 'TIKTOK_PIXEL_ID' );
					if ( $primary ) {
						return true;
					}
					return \FP\Privacy\Integrations\TrackingPatternScanner::site_contains_any( array(
						'analytics.tiktok.com',
						'ttq.load',
						'tiktok pixel',
					) );
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
					$primary = defined( 'MATOMO_VERSION' ) || \wp_script_is( 'matomo-tracking', 'enqueued' );
					if ( $primary ) {
						return true;
					}
					return \FP\Privacy\Integrations\TrackingPatternScanner::site_contains_any( array(
						'matomo.js',
						'piwik.js',
						'_paq',
						'matomo.org',
					) );
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
					$primary = defined( 'PINTEREST_TAG_ID' );
					if ( $primary ) {
						return true;
					}
					return \FP\Privacy\Integrations\TrackingPatternScanner::site_contains_any( array(
						'pintrk(',
						'pinterest.com/ct',
						's.pinimg.com/ct',
					) );
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
					$primary = defined( 'HUBSPOT_API_KEY' ) || \wp_script_is( 'leadin-script-loader', 'enqueued' );
					if ( $primary ) {
						return true;
					}
					return \FP\Privacy\Integrations\TrackingPatternScanner::site_contains_any( array(
						'js.hs-scripts.com',
						'js.hsforms.net',
						'hubspot',
						'hs-script-loader',
					) );
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

		// Services that use ServiceDetector methods.
		$embed_services = array(
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
				'detector'    => array( $service_detector, 'detect_wistia' ),
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
				'detector'    => array( $service_detector, 'detect_vidyard' ),
			),
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
				'detector'    => array( $service_detector, 'detect_instagram' ),
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
				'detector'    => array( $service_detector, 'detect_twitter_embed' ),
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
				'detector'    => array( $service_detector, 'detect_spotify' ),
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
				'detector'    => array( $service_detector, 'detect_soundcloud' ),
			),
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
				'detector'    => array( $service_detector, 'detect_typeform' ),
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
				'detector'    => array( $service_detector, 'detect_surveymonkey' ),
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
				'detector'    => array( $service_detector, 'detect_google_forms' ),
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
				'detector'    => array( $service_detector, 'detect_jotform' ),
			),
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
				'detector'    => array( $service_detector, 'detect_calendly' ),
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
				'detector'    => array( $service_detector, 'detect_acuity' ),
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
				'detector'    => array( $service_detector, 'detect_cal_com' ),
			),
		);

		// Merge embed services with regular services.
		return array_merge( $services, $embed_services );
	}
}








