<?php
/**
 * Unknown service analyzer.
 *
 * @package FP\Privacy\Integrations
 * @author Francesco Passeri
 * @link https://francescopasseri.com
 */

namespace FP\Privacy\Integrations;

/**
 * Analyzes and detects unknown third-party services.
 */
class UnknownServiceAnalyzer {
	/**
	 * Constructor.
	 */
	public function __construct() {
		// Constructor can be empty or used for dependency injection if needed.
	}

	/**
	 * Detect unknown/custom third-party services not in the registry.
	 *
	 * @param array<int, string> $known_domains Optional list of known domains to exclude.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function detect_unknown_services( array $known_domains = array() ) {
		$unknown = array();

		// Detect external scripts from WordPress
		$external_scripts = UnknownServiceDetector::detect_external_scripts();

		// Detect external styles
		$external_styles = UnknownServiceDetector::detect_external_styles();

		// Scan HTML output for inline scripts and iframes
		$html_services = UnknownServiceDetector::scan_html_output();

		// Detect third-party iframes
		$iframe_domains = UnknownServiceDetector::detect_iframes();

		// Merge and deduplicate all detected domains
		$detected_domains = array_unique(
			array_merge(
				$external_scripts,
				$external_styles,
				$html_services,
				$iframe_domains
			)
		);

		foreach ( $detected_domains as $domain ) {
			if ( UnknownServiceUtils::is_known_domain( $domain, $known_domains ) ) {
				continue;
			}

			$service_info = UnknownServiceGuesser::analyze_service( $domain );

			$unknown[] = array(
				'slug'          => 'unknown_' . \sanitize_key( $domain ),
				'name'          => $service_info['name'],
				'category'      => $service_info['category'],
				'provider'      => $service_info['provider'],
				'policy_url'    => $service_info['policy_url'],
				'cookies'        => array(),
				'legal_basis'    => $service_info['legal_basis'],
				'purpose'        => $service_info['purpose'],
				'retention'      => 'Unknown',
				'data_location'  => $service_info['data_location'],
				'detected'       => true,
				'is_unknown'     => true,
				'domain'         => $domain,
			);
		}

		return $unknown;
	}
}
