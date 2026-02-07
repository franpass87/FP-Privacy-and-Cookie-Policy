<?php
/**
 * Service detector registry.
 *
 * @package FP\Privacy\Integrations
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Integrations;

use FP\Privacy\Services\Cache\CacheInterface;

/**
 * Detects third-party services for policy generation.
 */
class DetectorRegistry {
    /**
     * Cache manager instance.
     *
     * @var DetectorCache
     */
    private $cache;

    /**
     * Service detector instance.
     *
     * @var ServiceDetector
     */
    private $service_detector;

    /**
     * Unknown service analyzer instance.
     *
     * @var UnknownServiceAnalyzer
     */
    private $unknown_analyzer;

    /**
     * Constructor.
     *
     * @param CacheInterface|null $cache Cache interface for persistence. If null, DetectorCache will use fallback.
     */
    public function __construct( ?CacheInterface $cache = null ) {
        $this->cache            = new DetectorCache( $cache );
        // Use new Domain\Services\ServiceRegistry, fallback to old for compatibility.
        if ( class_exists( '\\FP\\Privacy\\Domain\\Services\\ServiceRegistry' ) ) {
            $service_registry = new \FP\Privacy\Domain\Services\ServiceRegistry();
        } else {
            $service_registry = new ServiceRegistry();
        }
        $this->service_detector = new ServiceDetector( $service_registry );
        $this->unknown_analyzer  = new UnknownServiceAnalyzer();
    }
/**
 * Get registry of services.
 *
 * @return array<string, array<string, mixed>>
 */
public function get_registry() {
// Start with base registry from ServiceRegistry to avoid duplication.
        // Use new Domain\Services\ServiceRegistry if available, fallback to old for compatibility.
        if ( class_exists( '\\FP\\Privacy\\Domain\\Services\\ServiceRegistry' ) ) {
            $services = \FP\Privacy\Domain\Services\ServiceRegistry::get_base_registry();
        } else {
            $services = ServiceRegistry::get_base_registry();
        }

// Add additional services that are not in the base registry.
// Note: ga4, gtm, facebook_pixel, hotjar, clarity, recaptcha, youtube, and vimeo
// are already in ServiceRegistry::get_base_registry(), so they are excluded here.
// Load additional services from configuration file if available.
$additional_services = $this->load_additional_services();

// Merge additional services with base registry.
// Additional services will override base registry entries if keys match.
$services = array_merge( $services, $additional_services );

// Allow developers to add custom services
$custom_services = \apply_filters( 'fp_privacy_custom_services', array() );

if ( is_array( $custom_services ) && ! empty( $custom_services ) ) {
	$services = array_merge( $services, $custom_services );
}

// Replace embed detectors with ServiceDetector references.
$services = $this->replace_embed_detectors( $services );

return \apply_filters( 'fp_privacy_services_registry', $services );
}

/**
 * Replace array( $this, 'detect_*' ) detectors with ServiceDetector references.
 *
 * @param array<string, array<string, mixed>> $services Services array.
 *
 * @return array<string, array<string, mixed>>
 */
private function replace_embed_detectors( array $services ) {
	$embed_detectors = array(
		'youtube'       => 'detect_youtube',
		'vimeo'         => 'detect_vimeo',
		'wistia'        => 'detect_wistia',
		'vidyard'       => 'detect_vidyard',
		'instagram'     => 'detect_instagram',
		'twitter_embed' => 'detect_twitter_embed',
		'spotify'       => 'detect_spotify',
		'soundcloud'    => 'detect_soundcloud',
		'typeform'      => 'detect_typeform',
		'surveymonkey'  => 'detect_surveymonkey',
		'google_forms'  => 'detect_google_forms',
		'jotform'       => 'detect_jotform',
		'calendly'      => 'detect_calendly',
		'acuity'        => 'detect_acuity',
		'cal_com'       => 'detect_cal_com',
	);

	foreach ( $embed_detectors as $slug => $method ) {
		if ( isset( $services[ $slug ]['detector'] ) ) {
			$detector = $services[ $slug ]['detector'];
			// Check if it's an array( $this, 'detect_*' ) callable.
			if ( is_array( $detector ) && count( $detector ) === 2 && isset( $detector[1] ) && $detector[1] === $method ) {
				$services[ $slug ]['detector'] = array( $this->service_detector, $method );
			} elseif ( is_string( $detector ) && $detector === $method ) {
				// Handle string detectors from ServiceRegistry.
				$services[ $slug ]['detector'] = array( $this->service_detector, $method );
			}
		}
	}

	return $services;
}

/**
 * Load additional services from configuration file or return hardcoded array.
 *
 * @return array<string, array<string, mixed>>
 */
private function load_additional_services() {
	// Try to load from configuration file if available (path is junction-safe: __DIR__ is the actual plugin dir).
	$config_file = __DIR__ . \DIRECTORY_SEPARATOR . 'Config' . \DIRECTORY_SEPARATOR . 'AdditionalServicesConfig.php';
	if ( ! \is_readable( $config_file ) ) {
		return $this->get_hardcoded_additional_services();
	}
	try {
		$loader = require $config_file;
		if ( \is_callable( $loader ) ) {
			$additional = $loader( $this->service_detector );
			return \is_array( $additional ) ? $additional : $this->get_hardcoded_additional_services();
		}
	} catch ( \Throwable $e ) {
		if ( \defined( 'WP_DEBUG' ) && \WP_DEBUG && \function_exists( 'error_log' ) ) {
			\error_log( 'FP Privacy: Failed to load additional services config: ' . $e->getMessage() );
		}
	}
	return $this->get_hardcoded_additional_services();
}

/**
 * Get hardcoded additional services array.
 *
 * This method contains the full list of additional services.
 * In the future, this can be moved to a configuration file.
 *
 * @return array<string, array<string, mixed>>
 */
private function get_hardcoded_additional_services() {
	// Note: ga4, gtm, facebook_pixel, hotjar, clarity, recaptcha, youtube, and vimeo
	// are already in ServiceRegistry::get_base_registry(), so they are excluded here.
	$additional_services = array(
		// Services will be added here by get_hardcoded_additional_services method
		// This is a placeholder - the actual services are defined elsewhere
	);

	return $additional_services;
}

	/**
	 * Detect services by running all detectors.
	 * Public method that wraps run_detectors().
	 *
	 * @param bool $force When true, bypass any cache and run detection (default). Currently cache is not used; parameter reserved for future use.
	 * @return array<int, array<string, mixed>>
	 */
	public function detect_services( $force = true ) {
		return $this->run_detectors();
	}

	/**
	 * Get list of known domains from the registry.
	 *
	 * Extracts domains from policy URLs in the registry services.
	 * This can be used by UnknownServiceAnalyzer to identify known services.
	 *
	 * @return array<int, string>
	 */
	private function get_known_domains() {
		$domains = array();
		$registry = $this->get_registry();

		foreach ( $registry as $service_key => $service_data ) {
			if ( isset( $service_data['policy_url'] ) && ! empty( $service_data['policy_url'] ) ) {
				$parsed_url = \wp_parse_url( $service_data['policy_url'] );
				if ( isset( $parsed_url['host'] ) ) {
					$domain = $parsed_url['host'];
					// Remove 'www.' prefix if present for consistency.
					$domain = \preg_replace( '/^www\./', '', $domain );
					if ( ! \in_array( $domain, $domains, true ) ) {
						$domains[] = $domain;
					}
				}
			}
		}

		\sort( $domains );
		return $domains;
	}

	/**
	 * Clear detector cache.
	 *
	 * @return void
	 */
	public function invalidate_cache() {
		$this->cache->invalidate_cache();
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
		);
	}
}
